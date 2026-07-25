<?php

/**
 * WebMCP (Web Model Context Protocol) Component
 * Exposes UangKu transaction recording tools, options, and system prompt guidelines to AI Agents.
 * References backend MCP definitions from html/libs/mcp (tools.lib.php & resources.lib.php).
 */
?>
<script>
  (function () {
    /* ==========================================================================
       WebMCP System Prompt & Guidelines (Reference: html/libs/mcp/resources.lib.php)
       ========================================================================== */
    const WEBMCP_SYSTEM_PROMPT = `
You are a personal finance recording assistant connected to the Uangku app via WebMCP tools.
Your job: convert short user input (text or receipt photo) into recorded transactions — accurately and fast, with minimal back-and-forth.

=== MANDATORY WORKFLOW ===
1. Receive user input
2. Call get_rekening() — MANDATORY every session, to get current account list and IDs
3. Call get_kelompok() — MANDATORY every session, to get current category list
4. Build recap table
5. Show recap to user → wait for "oke" (or correction)
6. Execute catat_transaksi / record_transaction
7. Brief confirmation

NEVER record anything before user confirms.
NEVER ask questions before showing the recap — decide everything yourself using the rules below.

=== ACCOUNT RULES ===
Always use get_rekening() result as reference for account names and IDs — never hardcode.

DEFAULT ACCOUNT if user does not specify:
- Food / minimarket / canteen / medicine → ShopeePay
- Ojek / GoFood / Gojek → GoPay
- Cash purchase / market / warung without QRIS → Dompet
- Topup / kos / transfer / tickets → BRI Tampung
- Receipt photo shows ShopeePay QRIS → ShopeePay
- Asset purchase → Harta Benda (harta=true)

If user explicitly mentions an account → use that, ignore default.

=== KELOMPOK (CATEGORY) RULES ===
STEP 1: Always call get_kelompok() first. Use the result as the reference list.
STEP 2: Prefer existing kelompok if context matches. Only create new ones if truly nothing fits.

Known fixed kelompok (spelling matters):
- Konsumsi, Transportasi, Topup, Pendapatan, Sedekah, Infaq, Langganan, Rumah Tangga, Listrik, Kotak P3k, Bodycare, Kos, Kirim Keluarga, Cukur, Olahraga, Admin Rekening, Balancing Saldo, Uang Darurat, Pajak, Motor.

=== RUTIN vs NON-RUTIN ===
rutin: true → daily operational spending, recurring every weekday (Mon–Sat):
- Ojek to office, canteen meals, sedekah, internet package, laundry, kos, electricity, e-wallet topups.
rutin: false → Sunday transactions, events, non-routine purchases (gadgets, assets), GoFood, monthly subscriptions.

=== NOMINAL & DISCOUNT RULES ===
- nominal = price PER UNIT (not total). System multiplies by quantity automatically.
- Discount at receipt total → distribute PRORATA across all items.
- Tips → MERGE into main transaction nominal.

=== ASSET (HARTA) RULES ===
- Asset purchase: Pengeluaran (money account, harta: false) + Pemasukan (Harta Benda, harta: true, penyusutan_bunga).
- PROHIBITED: harta=true on Pengeluaran from regular money account.
- PROHIBITED: Pindah Buku for Harta transactions.
`.trim();

    /* ==========================================================================
       Helper Functions
       ========================================================================== */
    const findAccount = (val) => {
      if (!val || typeof ARGS === 'undefined' || !ARGS?.Rekening) return null;
      const strVal = String(val).toLowerCase().trim();
      return ARGS.Rekening.find(r =>
        String(r.id) === String(val) ||
        r.nama.toLowerCase() === strVal ||
        r.nama.toLowerCase().includes(strVal)
      );
    };

    /* ==========================================================================
       WebMCP Tools Definitions (Reference: html/libs/mcp/tools.lib.php)
       ========================================================================== */

    // 1. catat_transaksi / record_transaction
    const WebMCPToolRecordTransaction = {
      name: 'catat_transaksi',
      description: 'Catat atau isi formulir transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku / Operasi) di UangKu.',
      inputSchema: {
        type: 'object',
        properties: {
          jenis_transaksi: {
            type: 'string',
            enum: typeof J_TRANS !== 'undefined' ? J_TRANS : ['Pengeluaran', 'Pemasukan', 'Operasi'],
            description: 'Jenis transaksi: "Pengeluaran", "Pemasukan", atau "Operasi" (Pindah Buku)'
          },
          barang: {
            type: 'string',
            description: 'Judul, nama barang, atau deskripsi singkat transaksi (digeneralkan, misal "Makan Siang")'
          },
          nominal: {
            type: 'number',
            description: 'Nominal transaksi per unit dalam Rupiah (IDR)'
          },
          nominal_asing: {
            type: 'number',
            description: 'Nominal dalam mata uang / satuan asing (misal: gram emas atau USD)'
          },
          rekening_sumber: {
            type: 'string',
            description: 'Nama atau ID rekening sumber / asal (diperlukan untuk Pengeluaran atau Operasi)'
          },
          rekening_masuk: {
            type: 'string',
            description: 'Nama atau ID rekening masuk / tujuan (diperlukan untuk Pemasukan atau Operasi)'
          },
          kuantitas: {
            type: 'number',
            description: 'Jumlah / kuantitas unit (default: 1)'
          },
          penyusutan_bunga: {
            type: 'number',
            description: 'Nominal penyusutan atau bunga buku per bulan dalam Rupiah (untuk Aset/Harta)'
          },
          tanggal: {
            type: 'string',
            description: 'Tanggal transaksi dalam format YYYY-MM-DD (default: hari ini)'
          },
          kelompok: {
            type: 'string',
            description: 'Kelompok atau kategori transaksi (contoh: Konsumsi, Transportasi, Gaji, dll.)'
          },
          rutin: {
            type: 'boolean',
            description: 'Set true jika transaksi ini bersifat rutin/berkala'
          },
          harta: {
            type: 'boolean',
            description: 'Set true jika transaksi ini terkait Aset Harta'
          },
          relasi_transaksi: {
            type: 'string',
            description: 'ID transaksi utama untuk menghubungkan beberapa item dalam satu struk/kejadian'
          },
          keterangan: {
            type: 'string',
            description: 'Catatan tambahan / keterangan detail transaksi'
          },
          submit: {
            type: 'boolean',
            description: 'Set true untuk langsung mengirim/submit formulir setelah diisi'
          }
        },
        required: ['jenis_transaksi', 'barang', 'nominal']
      },
      execute: async (params) => {
        try {
          if (typeof FORM === 'undefined' || !FORM) {
            throw new Error('Formulir transaksi (FORM) tidak ditemukan pada halaman ini.');
          }

          const {
            jenis_transaksi,
            barang,
            nominal,
            nominal_asing,
            rekening_sumber,
            rekening_masuk,
            kuantitas,
            penyusutan_bunga,
            tanggal,
            kelompok,
            rutin,
            harta,
            relasi_transaksi,
            keterangan,
            submit
          } = params;

          // 1. Set Jenis Transaksi
          if (jenis_transaksi) {
            const normalizedJenis = jenis_transaksi === 'Pindah Buku' ? 'Operasi' : jenis_transaksi;
            if (typeof FORM.jenis_transaksi !== 'undefined' && FORM.jenis_transaksi.SlimSelect) {
              FORM.jenis_transaksi.SlimSelect.setSelected(normalizedJenis);
            }
            if (typeof formState === 'function') {
              await formState({ target: { value: normalizedJenis } });
            }
          }

          // 2. Set Barang / Judul
          if (barang !== undefined && FORM.barang) {
            FORM.barang.value = barang;
          }

          // 3. Set Harta
          if (harta !== undefined && FORM.harta) {
            FORM.harta.checked = !!harta;
            if (typeof FORM.harta.switchState === 'function') {
              FORM.harta.switchState(!!harta);
            }
          }

          // 4. Set Rekening Sumber
          if (rekening_sumber && FORM.rekening_sumber) {
            const acc = findAccount(rekening_sumber);
            if (acc && FORM.rekening_sumber.SlimSelect) {
              FORM.rekening_sumber.SlimSelect.setSelected(acc.id);
              FORM.rekening_sumber.rekening = acc;
            }
          }

          // 5. Set Rekening Masuk
          if (rekening_masuk && FORM.rekening_masuk) {
            const acc = findAccount(rekening_masuk);
            if (acc && FORM.rekening_masuk.SlimSelect) {
              FORM.rekening_masuk.SlimSelect.setSelected(acc.id);
              FORM.rekening_masuk.rekening = acc;
            }
          }

          // 6. Set Nominal & Nominal Asing
          if (nominal !== undefined && nominal > 0 && FORM.nominal) {
            if (typeof formatID === 'function') {
              FORM.nominal.value = formatID(nominal.toString());
            } else {
              FORM.nominal.value = nominal;
            }
          }
          if (nominal_asing !== undefined && FORM.nominal_asing) {
            FORM.nominal_asing.value = nominal_asing;
          }

          // 7. Set Kuantitas & Penyusutan
          if (kuantitas !== undefined && kuantitas > 0 && FORM.kuantitas) {
            FORM.kuantitas.value = kuantitas;
          }
          if (penyusutan_bunga !== undefined && FORM.penyusutan_bunga) {
            FORM.penyusutan_bunga.value = penyusutan_bunga;
          }

          // 8. Set Tanggal
          if (tanggal && FORM.tanggal) {
            FORM.tanggal.value = tanggal;
          }

          // 9. Set Rutin
          if (rutin !== undefined && FORM.rutin) {
            FORM.rutin.checked = !!rutin;
            if (typeof FORM.rutin.switchState === 'function') {
              FORM.rutin.switchState(!!rutin);
            }
          }

          // 10. Set Kelompok
          if (kelompok && FORM.kelompok) {
            let kelValue = kelompok;
            if (FORM.kelompok.SlimSelect) {
              const existingData = FORM.kelompok.SlimSelect.getData();
              if (!existingData.some(opt => opt.value === kelValue)) {
                FORM.kelompok.SlimSelect.setData([...existingData, { text: kelValue, value: kelValue }]);
              }
              FORM.kelompok.SlimSelect.setSelected(kelValue);
            } else {
              FORM.kelompok.value = kelValue;
            }
          }

          // 11. Set Relasi Transaksi
          if (relasi_transaksi && FORM.relasi_transaksi) {
            if (FORM.relasi_transaksi.SlimSelect) {
              FORM.relasi_transaksi.SlimSelect.setSelected(relasi_transaksi);
            } else {
              FORM.relasi_transaksi.value = relasi_transaksi;
            }
          }

          // 12. Set Keterangan
          if (keterangan !== undefined && FORM.keterangan) {
            FORM.keterangan.value = keterangan;
          }

          // Recalculate totals and previews if hitung is available
          if (typeof hitung === 'function') {
            hitung();
          }

          if (submit) {
            if (typeof FORM.requestSubmit === 'function') {
              FORM.requestSubmit();
            } else {
              FORM.submit();
            }
            return { success: true, message: 'Formulir transaksi berhasil diisi dan dikirim.', data: params };
          }

          return { success: true, message: 'Formulir transaksi berhasil diisi.', data: params };
        } catch (err) {
          return { success: false, error: err.message };
        }
      }
    };

    // Alias tool record_transaction for compatibility
    const WebMCPToolRecordTransactionAlias = {
      ...WebMCPToolRecordTransaction,
      name: 'record_transaction'
    };

    // 2. get_rekening (Reference: html/libs/mcp/resources.lib.php)
    const WebMCPToolGetRekening = {
      name: 'get_rekening',
      description: 'Mendapatkan daftar rekening dan ID untuk input transaksi (termasuk saldo dan status harta).',
      inputSchema: { type: 'object', properties: {} },
      execute: async () => {
        const list = (typeof ARGS !== 'undefined' && ARGS?.Rekening) ? ARGS.Rekening : [];
        return {
          data: list.map(r => ({
            rekening_id: r.id,
            nama_rekening: r.nama,
            saldo: r.saldo,
            saldo_asing: r.saldo_asing || 0,
            aktif: !!r.aktif,
            harta: !!r.harta,
            isAsing: !!r.isAsing
          }))
        };
      }
    };

    // 3. get_kelompok (Reference: html/libs/mcp/resources.lib.php)
    const WebMCPToolGetKelompok = {
      name: 'get_kelompok',
      description: 'Mendapatkan daftar kategori/kelompok transaksi yang tersedia di UangKu.',
      inputSchema: { type: 'object', properties: {} },
      execute: async () => {
        let options = [];
        if (typeof FORM !== 'undefined' && FORM?.kelompok?.SlimSelect) {
          options = FORM.kelompok.SlimSelect.getData().map(opt => opt.value).filter(Boolean);
        }
        return {
          data: options.map(k => ({ kelompok: k, count: 0 }))
        };
      }
    };

    // 4. get_harta (Reference: html/libs/mcp/resources.lib.php)
    const WebMCPToolGetHarta = {
      name: 'get_harta',
      description: 'Mendapatkan daftar harta/aset yang sudah ada dan saldo pembukuannya.',
      inputSchema: { type: 'object', properties: {} },
      execute: async () => {
        const list = (typeof ARGS !== 'undefined' && ARGS?.Rekening) ? ARGS.Rekening : [];
        return {
          data: list.filter(r => r.harta).map(r => ({
            id: r.id,
            nama: r.nama,
            saldo: r.saldo,
            harta: true
          }))
        };
      }
    };

    // 5. get_transaction_options
    const WebMCPToolGetOptions = {
      name: 'get_transaction_options',
      description: 'Dapatkan daftar jenis transaksi, daftar rekening, dan opsi formulir yang tersedia di UangKu.',
      inputSchema: { type: 'object', properties: {} },
      execute: async () => {
        const rekeningList = (typeof ARGS !== 'undefined' && ARGS?.Rekening) ? ARGS.Rekening : [];
        let kelompokList = [];
        if (typeof FORM !== 'undefined' && FORM?.kelompok?.SlimSelect) {
          kelompokList = FORM.kelompok.SlimSelect.getData().map(opt => opt.value).filter(Boolean);
        }
        return {
          jenis_transaksi: typeof J_TRANS !== 'undefined' ? J_TRANS : ['Pengeluaran', 'Pemasukan', 'Operasi'],
          rekening: rekeningList.map(r => ({
            id: r.id,
            nama: r.nama,
            saldo: r.saldo,
            isAsing: r.isAsing,
            harta: r.harta
          })),
          kelompok: kelompokList
        };
      }
    };

    /* ==========================================================================
       WebMCP Registration & Global Export
       ========================================================================== */
    function registerWebMCP() {
      const tools = [
        WebMCPToolRecordTransaction,
        WebMCPToolRecordTransactionAlias,
        WebMCPToolGetRekening,
        WebMCPToolGetKelompok,
        WebMCPToolGetHarta,
        WebMCPToolGetOptions
      ];

      const resources = [
        {
          uri: 'uangku://system_prompt',
          name: 'system_prompt',
          description: 'Prompt sistem dan aturan bisnis pencatatan keuangan UangKu untuk AI Assistant.',
          mimeType: 'text/plain',
          read: () => WEBMCP_SYSTEM_PROMPT
        }
      ];

      // 1. Standard Browser WebMCP API (navigator.modelContext or document.modelContext)
      const mc = navigator.modelContext || document.modelContext;
      if (mc) {
        tools.forEach(tool => {
          if (typeof mc.registerTool === 'function') {
            try { mc.registerTool(tool); } catch (e) { console.warn('WebMCP tool reg failed:', e); }
          }
        });
        resources.forEach(res => {
          if (typeof mc.registerResource === 'function') {
            try { mc.registerResource(res); } catch (e) { console.warn('WebMCP resource reg failed:', e); }
          }
        });
      }

      // 2. Global window.webmcp Interface for extensions, polyfills, or custom agents
      window.webmcp = window.webmcp || {
        tools: new Map(),
        resources: new Map(),
        registerTool(tool) {
          this.tools.set(tool.name, tool);
        },
        registerResource(res) {
          this.resources.set(res.uri, res);
        },
        getTools() {
          return Array.from(this.tools.values());
        },
        getResources() {
          return Array.from(this.resources.values());
        },
        async executeTool(name, params) {
          const tool = this.tools.get(name);
          if (!tool) throw new Error(`WebMCP Tool '${name}' not found.`);
          return await tool.execute(params);
        },
        readResource(uri) {
          const res = this.resources.get(uri);
          if (!res) throw new Error(`WebMCP Resource '${uri}' not found.`);
          return typeof res.read === 'function' ? res.read() : res;
        },
        getSystemPrompt() {
          return WEBMCP_SYSTEM_PROMPT;
        }
      };

      tools.forEach(tool => window.webmcp.registerTool(tool));
      resources.forEach(res => window.webmcp.registerResource(res));

      // Dispatch WebMCP ready event
      document.dispatchEvent(new CustomEvent('webmcp:ready', {
        detail: {
          tools: window.webmcp.getTools(),
          resources: window.webmcp.getResources()
        }
      }));
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', registerWebMCP);
    } else {
      registerWebMCP();
    }
  })();
</script>
