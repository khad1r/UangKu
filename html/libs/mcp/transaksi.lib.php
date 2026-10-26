<?php

namespace App\libs;

use Mcp\Capability\Attribute\{McpTool, Schema};
use Mcp\Exception\ToolCallException;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\ToolAnnotations;
use App\models\Transaksi as ModelsTransaksi;

class transaksi
{
  /**
   * Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku)
   */
  #[McpTool(
    name: 'catat_transaksi',
    description: 'Mencatat transaksi keuangan baru (Pemasukan, Pengeluaran, atau Pindah Buku) ke sistem UangKu.

    ⚠️ RESTRICTION & MANDATORY WORKFLOW (WAJIB DIBACA & DIIKUTI):
    1. WAJIB MEMAHAMI SYSTEM PROMPT: AI wajib selalu mengetahui dan mengikuti panduan lengkap dari resource system_prompt (uangku://system_prompt).
    2. DILARANG EKSEKUSI LANGSUNG: DILARANG memanggil tool ini pada giliran pertama saat user baru memberikan rincian/struk belanja atau sebelum konfirmasi user.
    3. TAHAPAN WAJIB SEBELUM EKSEKUSI:
      a. Panggil "get_rekening" & "get_kelompok" secara fresh pada setiap turn (DILARANG pakai ID dari ingatan/turn sebelumnya).
      b. Susun data transaksi dan TAMPILKAN FORMAT REKAP ke user dalam markdown code block.
      c. TUNGGU KONFIRMASI EKSPLISIT dari user (misal: "oke", "ya", "gas", "simpan", dsb.) atau koreksi dari user. DILARANG bertanya sebelum menampilkan rekap.
      d. Baru panggil tool "catat_transaksi" ini SETELAH user memberikan konfirmasi persetujuan.

    FORMAT REKAP (SEBELUM EXECUTION) YANG WAJIB DITAMPILKAN KE USER:
    Tampilkan rekap dalam markdown code block (```markdown ... ```) dengan template berikut:
    ```markdown
    | # | Barang            | Jenis       | Nominal | Qty | Rekening                          | Kelompok  | Rutin | Tanggal    | Keterangan                                  | Relate | Attachment |
    |---|-------------------|-------------|---------|-----|-----------------------------------|-----------|-------|------------|---------------------------------------------|--------|------------|
    | 1 | Burger            | Pengeluaran | 79.500  | 1   | ▲ Gopay (ID: 9)                   | Konsumsi  | X     | 2026-10-18 | Hugos by SPOONIFY Cideng (ID: F-3396314512) | -      | -          |
    | 2 | [$] Mini Gold 1g  | Pengeluaran | 1.300K  | 1   | ▲ Bri Tampung (1)                 | Emas      | X     | 2026-10-19 | Antam Fine Gold                             | -      | -          |
    | 3 | Topup             | Pindah Buku | 100.000 | 1   | ▲ Bri Tampung (1) ▼ ShopeePay (8) | Topup     | ✓     | 2026-10-19 | Topup ShopeePay                             | -      | -          |
    - Auto-Relate: [✓/x]
    ```
    Aturan Tampilan Kolom Rekap:
    - Barang:
      - Tambahkan prefix "[$]" jika harta: true.
      - Generalisasikan nama barang tetapi tetap terdiversifikasi & teridentifikasi (misal: "Nasi Padang", "Ayam Geprek", "Mouse Wireless", bukan terlalu abstrak seperti "Makanan"/"Makan siang", dan bukan berbelit merek/SKU). Detail lengkap masukkan ke Keterangan.
    - Rekening:
      - Gunakan simbol "▲" untuk Pengeluaran (rekening sumber).
      - Gunakan simbol "▼" untuk Pemasukan (rekening tujuan).
      - Untuk Pindah Buku: tampilkan kedua rekening: "▲ [Sumber] (ID: X) ▼ [Tujuan] (ID: Y)".
    - Rutin: Gunakan "✓" untuk true dan "X" untuk false.
    - Relate & Attachment: Kolom opsional (dapat dihapus jika tidak digunakan).
    - Tuliskan catatan asumsi singkat di bawah code block jika ada (misal: prorata diskon atau default account).

    ATURAN BISNIS PENTING DARI SYSTEM PROMPT (uangku://system_prompt):
    1. DEFAULT REKENING (jika user tidak menyebutkan secara eksplisit):
      - Makanan / minimarket / kantin / obat → ShopeePay
      - Ojek / GoFood / Gojek → GoPay
      - Tunai / pasar / warung tanpa QRIS → Dompet
      - Topup / kos / transfer / tiket → BRI Tampung
      - QRIS ShopeePay pada struk → ShopeePay
      - Pembelian aset → Harta Benda (harta=true)
    2. STRUK BELANJA & RELASI INTRA-BATCH:
      - Pecah per item dalam array "data", JANGAN digabung menjadi satu total.
      - Struk belanja tunggal: Cukup gunakan autoRelate: true (semua item otomatis dihubungkan ke baris #1).
      - Multi-struk atau multi-pasangan aset dalam 1 batch: Gunakan penanda nomor baris string pada "relasi_transaksi" (contoh: "#1", "#3") untuk mengaitkan item ke baris induknya masing-masing.
      - Transaksi lama di DB: Isi "relasi_transaksi" dengan integer ID database dari hasil search_Transaksi.
    3. ATURAN HARTA (ASET):
      - Pembelian: Baris pertama Pengeluaran (rekening uang, harta=false) + Baris kedua Pemasukan (rekening Harta, harta=true, penyusutan_bunga default 48 bulan jika tidak ditentukan, relasi_transaksi="#1" atau merujuk nomor baris pengeluaran terkait).
      - Penjualan: Pengeluaran (rekening Harta, harta=true) + Pemasukan (rekening uang).
      - DILARANG: harta=true pada Pengeluaran rekening uang biasa. DILARANG: Pindah Buku untuk Harta.
    4. ATURAN ATTACHMENT:
      - FALSE (default / x): Pengeluaran rutin/kecil (makanan, ojek, topup, belanja harian).
      - TRUE (✓): Barang eksklusif/bernilai signifikan (gadget, elektronik, furniture, aset mahal, faktur/nota resmi) untuk bukti/garansi.
    5. NOMINAL & DISKON:
      - Nominal adalah harga PER UNIT (kuantitas dikalikan otomatis oleh sistem).
      - Diskon total di akhir struk: bagi PRORATA ke setiap item secara proporsional.
      - Diskon per item: catat harga NETTO.
      - Cashback: JANGAN dicatat (abaikan). Tips: Gabungkan ke nominal transaksi utama.
    6. RUTIN vs NON-RUTIN (Urutan prioritas strictly):
      1. Kelompok bernilai NULL (belanja barang tidak biasa / insidentil) → FALSE (wajib: pengeluaran rutin harus memiliki kelompok; tanpa kelompok berarti non-rutin).
      2. Event / Perjadin / Mudik → FALSE (selalu).
      3. Tagihan bulanan tetap & langganan berkala (Kos, Listrik, Admin Rekening, paket data, Gojek Plus, Bilibili, game monthly card, Spotify, Netflix) → TRUE (walau dibayar hari Minggu).
      4. Hiburan sekali beli / one-time entertainment (tiket bioskop, wisata, topup game non-langganan), pembelian aset/gadget/furnitur, atau GoFood/delivery spontan → FALSE.
      5. Transaksi hari Minggu → FALSE.
      6. Kelompok harian (Konsumsi, Transportasi, Sedekah, Topup) Senin–Sabtu → TRUE.
      7. Cek sinyal dominan get_kelompok() & Memory AI: Ikuti histori dominan. Namun, AI dapat menilai berdasarkan memory/riwayat interaksi: jika suatu aktivitas sebelumnya sering tercatat false tetapi riwayat memory menunjukkan aktivitas tersebut kini sudah sering berulang, AI dapat berinisiatif mengajukannya sebagai TRUE pada tabel rekap untuk dikonfirmasi user.
      8. Ragu / ambigu → FALSE.',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: false,
      idempotentHint: false,
      openWorldHint: false
    )
  )]
  #[Schema(
    properties: [
      'data' => [
        'type' => 'array',
        'description' => 'Daftar transaksi yang akan dicatat. Berupa array of objects berisi detail transaksi. WAJIB pastikan user sudah melihat rekap dan memberikan konfirmasi sebelum mengeksekusi tool ini.',
        'items' => [
          'type' => 'object',
          'properties' => [
            'jenis_transaksi'   => [
              'type' => 'string',
              'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
              'description' => 'Tipe transaksi'
            ],
            'harta' => ['type' => 'boolean', 'description' => '
                Set TRUE jika rekening yang digunakan adalah rekening Harta (baik Pemasukan maupun Pengeluaran).
                DILARANG set TRUE pada Pengeluaran dari rekening uang biasa.
              '],
            'barang' => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat. Harus digeneralisasikan tetapi tetap terdiversifikasi dan dapat dibedakan (contoh: "Nasi Padang", "Ayam Geprek", "Kopi Susu", "Mouse Wireless" — JANGAN terlalu abstrak seperti "Makan siang" / "Makanan" / "Barang", dan JANGAN terlalu spesifik seperti menyertakan nama warung/gramasi/SKU). Gunakan field Keterangan untuk detail merek/toko/SKU lengkap.'],
            'rekening_sumber'   => [
              'anyOf' => [['type' => 'integer'], ['type' => 'null']],
              'description' => 'ID Rekening asal. WAJIB ambil dari hasil pemanggilan get_rekening PALING BARU (turn ini) — JANGAN pernah pakai ID dari ingatan/percakapan sebelumnya. (Wajib jika Pengeluaran/Pindah Buku)
                  ATURAN PINDAH BUKU:
                  1. Tidak boleh sama dengan rekening_masuk.
                  2. Dilarang menggunakan rekening tipe HARTA (Aset).
                  3. Jika rekening asal adalah mata uang ASING (Emas/USD), maka rekening tujuan HARUS memiliki jenis mata uang asing yang sama.'
            ],
            'rekening_masuk'    => [
              'anyOf' => [['type' => 'integer'], ['type' => 'null']],
              'description' => 'ID Rekening tujuan. WAJIB ambil dari hasil pemanggilan get_rekening PALING BARU (turn ini) — JANGAN pernah pakai ID dari ingatan/percakapan sebelumnya. (Wajib jika Pemasukan/Pindah Buku)
                ATURAN PINDAH BUKU:
                1. Tidak boleh sama dengan rekening_sumber.
                2. Dilarang menggunakan rekening tipe HARTA (Aset).
                3. Jika rekening tujuan adalah mata uang ASING (Emas/USD), maka rekening sumber HARUS memiliki jenis mata uang asing yang sama.'
            ],
            'nominal'           => [
              'type' => 'number',
              'description' => 'Jumlah Per Kuantitas dalam Rupiah. Untuk selisih kurs: isi selisih nilainya di sini, set nominal_asing = 0.'
            ],
            'nominal_asing'     => [
              'anyOf' => [['type' => 'number'], ['type' => 'null']],
              'description' => 'Wajib diisi jika rekening menggunakan mata uang asing (Emas/USD). Masukkan nilai dalam satuan aslinya (misal: 0.1 untuk emas gram, bukan nilai rupiahnya).'
            ],
            'kuantitas'         => ['type' => 'number', 'default' => 1],
            'penyusutan_bunga'  => [
              'type' => 'number',
              'default' => 0,
              'description' => 'Nominal Penyusutan / Bunga buku per bulan dalam Rupiah. Hanya diisi jika jenis_transaksi adalah Pemasukan dan harta = true dan Jenis Rekening adalah Harta, untuk mencatat penyusutan atau bunga aset (misal: bunga deposito).'
            ],
            'rutin'             => [
              'type' => 'boolean',
              'default' => false,
              'description' => 'KLASIFIKASI RUTINITAS. Tentukan dengan urutan prioritas ini (hentikan di aturan pertama yang cocok — lihat system_prompt bagian RUTIN vs NON-RUTIN untuk detail & contoh):
                1. Bagian dari Event/Perjadin/Mudik -> FALSE, selalu.
                2. Tagihan bulanan tetap & langganan berkala (Kos, Listrik, Admin Rekening, paket data, Gojek Plus, Bilibili, game monthly card, Spotify, Netflix) -> TRUE, walau dibayar hari Minggu.
                3. Hiburan sekali beli / one-time entertainment (tiket bioskop, wisata, topup game non-langganan), pembelian aset/gadget/furnitur, atau GoFood/delivery spontan -> FALSE.
                4. Transaksi hari Minggu -> FALSE.
                5. Kelompok harian (Konsumsi, Transportasi, Sedekah, Topup) di hari Senin-Sabtu -> TRUE.
                6. Masih ragu -> cek breakdown rutin/count per kelompok dari get_kelompok() & Memory AI: ikuti nilai historis yang dominan. Namun jika memory/riwayat interaksi menunjukkan aktivitas yang sebelumnya sering false kini sudah sering berulang, AI dapat berinisiatif mengajukannya sebagai TRUE pada rekap untuk dikonfirmasi user.
                7. Masih ragu juga -> FALSE (default non-rutin).'
            ],
            'kelompok'          => [
              'anyOf' => [['type' => 'string'], ['type' => 'null']],
              'default' => null,
              'description' => 'Kategori transaksi. Gunakan get_kelompok untuk referensi, boleh buat baru sesuai kebutuhan. Aturan penentuan kelompok:
                1. OPERASIONAL (Rutin): Gunakan kategori umum (contoh: "Konsumsi", "Transportasi", "Listrik") jika dilakukan untuk kebutuhan dasar harian.
                2. LIFESTYLE (Non-Rutin Umum): Gunakan kategori umum jika terjadi di hari Minggu atau bersifat insidentil (bukan kebutuhan kerja harian).
                3. PROYEK/EVENT: Wajib buat/gunakan satu nama kelompok unik (contoh: "Liburan Bali 2026", "Perjadin 7 April") untuk SEMUA item (makan, tiket, dll) jika transaksi adalah bagian dari agenda khusus tersebut.
                DILARANG ASAL PILIH: Analisis konteks waktu dan tujuan transaksi sebelum menentukan kelompok.'
            ],
            'tanggal'           => [
              'anyOf' => [['type' => 'string'], ['type' => 'null']],
              'format' => 'date',
              'description' => 'Format: YYYY-MM-DD'
            ],
            'relasi_transaksi'  => [
              'anyOf' => [['type' => 'string'], ['type' => 'integer'], ['type' => 'null']],
              'description' =>
              'ID transaksi utama untuk menghubungkan beberapa item dalam satu struk/kejadian/aset. Pilihan format:
                - Referensi baris batch lokal: String dengan awalan "#" (misal: "#1", "#2") untuk menghubungkan ke baris transaksi sebelumnya dalam batch ini (1-based index). Sangat berguna untuk multi-struk atau pasangan aset (harta).
                - ID Database riil: Integer ID transaksi lama (misal: 84) yang didapat dari search_Transaksi atau get_transaksi.
                - null: Jika transaksi mandiri (tanpa relasi) atau jika mengandalkan parameter autoRelate: true.'
            ],
            'keterangan'        => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'default' => '', 'description' => 'Informasi tambahan tentang transaksi. Gunakan untuk detail spesifik yang tidak tercakup di field lain (contoh: nama toko, metode pembayaran, alasan pembelian).'],
            'attachment' => [
              'type' => 'boolean',
              'description' => 'Set TRUE jika transaksi memerlukan/mempunyai struk/nota/bukti transaksi (attachment) yang ingin diunggah oleh user.'
            ]
          ],
          'required' => ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal']
        ]
      ],
      'autoRelate' => [
        'type' => 'boolean',
        'default' => false,
        'description' => 'Jika TRUE, semua item setelah baris pertama yang relasi_transaksi-nya bernilai null akan otomatis dihubungkan ke ID baris pertama (#1). Cocok untuk struk belanja tunggal.'
      ]
    ],
    required: ['data']
  )]
  public function catatTransaksi(array $data, bool $autoRelate = false): string
  {
    $transaksi = new ModelsTransaksi();
    $results = [];
    $insertedIds = [];
    $attachmentLinks = [];

    try {
      foreach ($data as $index => $item) {
        $rowNum = $index + 1;

        // 1. Manual verification of required fields
        $requiredFields = ['jenis_transaksi', 'barang', 'nominal', 'kuantitas', 'tanggal'];
        foreach ($requiredFields as $field) {
          if (!array_key_exists($field, $item)) {
            throw new ToolCallException("❌ Validasi Gagal pada item ke-{$index}: Parameter '{$field}' wajib diisi.");
          }
        }

        // 2. Handle File Attachment (Will be uploaded via link later if requested)
        $finalFileName = !empty($item['attachment']) ? 'uploading' : null;

        // 3. Resolve transaction relation (Intra-batch relative index or existing DB ID)
        $rawRelate = $item['relasi_transaksi'] ?? null;
        $relasiTransaksi = null;

        if ($rawRelate !== null && $rawRelate !== '') {
          // a. Referensi nomor baris batch lokal dengan prefix '#' atau '@' (misal: "#1", "@1", "#2")
          if (is_string($rawRelate) && preg_match('/^[#@](\d+)$/', trim($rawRelate), $matches)) {
            $targetRow = (int) $matches[1];
            if (!isset($insertedIds[$targetRow])) {
              throw new ToolCallException("❌ Validasi Gagal pada baris #{$rowNum}: Referensi relasi '{$rawRelate}' merujuk ke baris yang belum tersimpan atau tidak ditemukan dalam batch.");
            }
            $relasiTransaksi = $insertedIds[$targetRow];
          }
          // b. Relative offset negatif (misal: -1 atau "-1" untuk merujuk baris tepat sebelumnya)
          elseif (is_numeric($rawRelate) && (int) $rawRelate < 0) {
            $targetRow = $rowNum + (int) $rawRelate;
            if (!isset($insertedIds[$targetRow])) {
              throw new ToolCallException("❌ Validasi Gagal pada baris #{$rowNum}: Relative offset '{$rawRelate}' tidak valid.");
            }
            $relasiTransaksi = $insertedIds[$targetRow];
          }
          // c. Integer / numeric: bisa ID database lama atau baris batch sebelumnya
          elseif (is_numeric($rawRelate)) {
            $num = (int) $rawRelate;
            if ($num > 0 && $num < $rowNum && isset($insertedIds[$num])) {
              $relasiTransaksi = $insertedIds[$num];
            } elseif ($num > 0) {
              $relasiTransaksi = $num;
            }
          }
        }
        // d. Fallback autoRelate jika relasi tidak diisi eksplisit
        elseif ($autoRelate && $index > 0 && isset($insertedIds[1])) {
          $relasiTransaksi = $insertedIds[1];
        }

        // 4. Map and normalize data payload
        $insertData = [
          'jenis_transaksi'   => $item['jenis_transaksi'],
          'harta'             => $item['harta'] ?? false,
          'barang'            => $item['barang'],
          'rekening_sumber'   => $item['rekening_sumber'] ?? null,
          'rekening_masuk'    => $item['rekening_masuk'] ?? null,
          'nominal'           => (float) $item['nominal'],
          'nominal_asing'     => isset($item['nominal_asing']) ? (float) $item['nominal_asing'] : 0,
          'kuantitas'         => (float) $item['kuantitas'],
          'penyusutan_bunga'  => isset($item['penyusutan_bunga']) ? (float) $item['penyusutan_bunga'] : 0,
          'rutin'             => $item['rutin'] ?? false,
          'kelompok'          => $item['kelompok'] ?? null,
          'tanggal'           => $item['tanggal'],
          'relasi_transaksi'  => $relasiTransaksi,
          'attachment'        => $finalFileName,
          'keterangan'        => $item['keterangan'] ?? null,
        ];

        // 5. Execute DB Insert
        $result = $transaksi->insertTransaksi($insertData);

        if ($result > 0) {
          $lastId = $transaksi->lastInsertId();
          $insertedIds[$rowNum] = $lastId;

          $results[] = "#{$lastId} ({$item['barang']})";

          // If the user requested an attachment for this transaction, generate a link
          if (!empty($item['attachment'])) {
            $attachmentLinks[] = "🔗 Harap unggah lampiran (attachment) untuk transaksi #{$lastId} ({$item['barang']}) di sini: " . BASEURL . "/Record/attachment/" . $lastId;
          }
        } else {
          throw new ToolCallException("❌ Gagal menyimpan item '{$item['barang']}' ke database.");
        }
      }

      $linkSuffix = !empty($attachmentLinks) ? "\n\n" . implode("\n", $attachmentLinks) : "";
      return "✅ Berhasil! " . count($results) . " Transaksi dicatat: " . implode(";\n ", $results) . $linkSuffix;
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Update transaksi Masal
   */
  #[McpTool(
    name: 'update_transaksi',
    description: 'Mendukung update massal hingga 15 transaksi sekaligus berdasarkan ID. Input berupa array of objects dengan format yang sama seperti catat_transaksi, namun wajib menyertakan field "id" untuk setiap item yang ingin diupdate. Contoh penggunaan:
    [
      {
        "id": 123,
        "jenis_transaksi": "Pengeluaran",
        "barang": "Makan Siang",
        "nominal": 50000,
        "kuantitas": 1,
        "tanggal": "2024-08-01",
        "keterangan": "Update: Tambah keterangan detail"
      },
      {
        "id": 124,
        "jenis_transaksi": "Pemasukan",
        "barang": "Gaji Bulanan",
        "nominal": 5000000,
        "kuantitas": 1,
        "tanggal": "2024-08-01",
        "keterangan": "Update: Ganti nominal sesuai slip gaji"
      }
    ]
    ATURAN PENTING:
      1. Batas Maksimal: Hanya mendukung update massal hingga 15 transaksi sekaligus untuk mencegah beban server yang berlebihan. Jika input melebihi batas ini, hanya 15 transaksi pertama yang akan diproses.
      2. Identifikasi Transaksi: Setiap objek dalam array input harus menyertakan field "id" yang valid untuk mengidentifikasi transaksi yang akan diupdate. Transaksi tanpa "id" atau dengan "id" yang tidak ditemukan di database akan diabaikan.
      3. Format Input: Format data untuk setiap transaksi yang akan diupdate sama seperti format yang digunakan dalam catat_transaksi, namun dengan tambahan field "id" yang wajib disertakan. Pastikan semua field yang diperlukan untuk update sudah benar dan sesuai dengan format yang ditentukan.
      ',
    annotations: new ToolAnnotations(
      readOnlyHint: false,
      destructiveHint: true,
      idempotentHint: true,
      openWorldHint: false
    )
  )]
  #[Schema(
    properties: [
      'data' => [
        'type' => 'array',
        'description' => 'Daftar transaksi yang akan diupdate. Berupa array of objects berisi detail transaksi.',
        'items' => [
          'type' => 'object',
          'properties' => [
            'id' => [
              'type' => 'integer',
              'description' => 'ID transaksi yang akan diupdate'
            ],
            'jenis_transaksi'   => [
              'type' => 'string',
              'enum' => ['Pengeluaran', 'Pemasukan', 'Pindah Buku'],
              'description' => 'Tipe transaksi'
            ],
            'harta'             => ['type' => 'boolean', 'description' => 'Set TRUE untuk aset permanen (HP, Motor, Emas). Set FALSE untuk habis pakai.'],
            'barang'            => ['type' => 'string', 'description' => 'Nama barang atau deskripsi singkat.'],
            'rekening_sumber'   => ['anyOf' => [['type' => 'integer'], ['type' => 'null']], 'description' => 'ID Rekening asal. WAJIB panggil get_rekening ulang dan pakai hasil terbaru bila mengubah field ini — jangan pakai ID dari ingatan.'],
            'rekening_masuk'    => ['anyOf' => [['type' => 'integer'], ['type' => 'null']], 'description' => 'ID Rekening tujuan. WAJIB panggil get_rekening ulang dan pakai hasil terbaru bila mengubah field ini — jangan pakai ID dari ingatan.'],
            'nominal'           => ['type' => 'number', 'description' => 'Jumlah dalam Rupiah.'],
            'nominal_asing'     => ['anyOf' => [['type' => 'number'], ['type' => 'null']], 'description' => 'Wajib diisi jika mata uang asing (Emas/USD).'],
            'kuantitas'         => ['type' => 'number', 'default' => 1],
            'penyusutan_bunga'  => ['type' => 'number', 'default' => 0],
            'rutin'             => ['type' => 'boolean', 'default' => false],
            'kelompok'          => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'default' => null],
            'tanggal'           => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'format' => 'date'],
            'relasi_transaksi'  => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
            'keterangan'        => ['anyOf' => [['type' => 'string'], ['type' => 'null']], 'default' => ''],
            'attachment'        => [
              'type' => 'boolean',
              'description' => 'Set TRUE jika ingin mengunggah/mengganti struk/nota/bukti transaksi (attachment) baru.'
            ]
          ],
          'required' => ['id', 'jenis_transaksi', 'harta', 'barang', 'rekening_sumber', 'rekening_masuk', 'nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga', 'rutin', 'kelompok', 'tanggal', 'relasi_transaksi', 'keterangan']
        ]
      ]
    ],
    required: ['data']
  )]
  public function updateTransaksi(array $data): string
  {
    $transaksi = new ModelsTransaksi();
    $results = [];
    $attachmentLinks = [];
    try {
      foreach ($data as $index => $item) {
        if ($index >= 15) break; // Batas maksimal 15 transaksi per update massal
        // 1. Manual verification of required fields
        $requiredFields = ['id', 'jenis_transaksi', 'harta', 'barang', 'rekening_sumber', 'rekening_masuk', 'nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga', 'rutin', 'kelompok', 'tanggal', 'relasi_transaksi', 'keterangan'];

        foreach ($requiredFields as $field) {
          if (!array_key_exists($field, $item)) {
            throw new ToolCallException("❌ Validasi Gagal pada item ke-{$index}: Parameter '{$field}' wajib diisi.");
          }
        }
        $targetId = $item['id'] ?? null;

        if ($targetId) {
          $updateData = $item;
          unset($updateData['id']);

          // Check if user wants to upload an attachment for this updated transaction
          $wantsAttachment = !empty($updateData['attachment']);
          unset($updateData['attachment']);
          if ($wantsAttachment) {
            $updateData['attachment'] = 'uploading';
          }

          // This now happens in memory, making it lightning fast
          $transaksi->updateTransaksi($updateData, ['id' => $targetId]);
          $results[] = "#{$targetId} ({$item['barang']})";

          if ($wantsAttachment) {
            $attachmentLinks[] = "🔗 Harap unggah lampiran (attachment) untuk transaksi #{$targetId} ({$item['barang']}) di sini: " . BASEURL . "/Record/attachment/" . $targetId;
          }
        } else {
          throw new ToolCallException("❌ Gagal Update item '{$item['barang']}' ke database.");
        }
      }

      $linkSuffix = !empty($attachmentLinks) ? "\n\n" . implode("\n", $attachmentLinks) : "";
      return "✅ Berhasil! " . count($results) . " Transaksi diperbarui: " . implode(";\n", $results) . $linkSuffix;
    } catch (\Exception $e) {
      throw new ToolCallException("⚠️ Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar kategori/kelompok transaksi yang sudah ada
   * Gunakan tool ini untuk referensi saat mengisi field "kelompok" di catat_transaksi.
   */
  #[McpTool(
    name: 'get_kelompok',
    description: 'Mendapatkan daftar kategori/kelompok transaksi yang sudah ada, dipecah per status rutin. Format data [kelompok,rutin,count].
    Setiap kelompok bisa muncul hingga 2 baris (satu untuk rutin=true, satu untuk rutin=false) — count menunjukkan berapa kali kelompok itu tercatat dengan status rutin tersebut.
    GUNAKAN INI SEBAGAI SINYAL TAMBAHAN untuk menentukan field "rutin" saat mencatat transaksi: jika suatu kelompok historisnya dominan rutin=true (count rutin=true jauh lebih besar), item baru di kelompok yang sama kemungkinan besar rutin=true juga, dan sebaliknya. Sinyal historis ini TIDAK menggantikan urutan prioritas di system_prompt (bagian RUTIN vs NON-RUTIN) — pakai untuk menajamkan keputusan pada kasus ambigu di rule 5/6, bukan untuk membatalkan rule 1-4 (event, tagihan bulanan esensial, langganan non-esensial, hari Minggu).',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'kelompok' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'rutin'    => ['type' => 'boolean', 'description' => 'Status rutin untuk baris hitungan ini'],
              'count'    => ['type' => 'integer']
            ]
          ]
        ]
      ]
    ]
  )]
  public function getKelompok(): array
  {
    try {
      $rows = new ModelsTransaksi()->getKelompok();
      return [
        'data' => array_map(
          fn($row) => [
            'kelompok' => $row['kelompok'] ?? null,
            'rutin'    => $row['rutin'] == 1,
            'count'    => (int) $row['count']
          ],
          $rows
        )
      ];
    } catch (\Exception $e) {
      throw new ResourceReadException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mendapatkan daftar transaksi dalam rentang tanggal tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'get_transaksi',
    description: 'Mendapatkan daftar riwayat transaksi dalam rentang tanggal tertentu (default: tanggal 1 bulan berjalan s/d hari ini). Gunakan untuk analisis riwayat belanja, audit mutasi, atau mencari transaksi masa lalu.',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id'                   => ['type' => 'integer'],
              'jenis_transaksi'      => ['type' => 'string'],
              'harta'                => ['type' => 'boolean'],
              'barang'               => ['type' => 'string'],
              'rekening_sumber'      => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'rekening_masuk'       => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'nominal'              => ['type' => 'number'],
              'nominal_asing'        => ['type' => 'number'],
              'kuantitas'            => ['type' => 'number'],
              'penyusutan_bunga'     => ['type' => 'number'],
              'rutin'                => ['type' => 'boolean'],
              'kelompok'             => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'tanggal'              => ['type' => 'string', 'format' => 'date'],
              'relasi_transaksi'     => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
              'attachment'           => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'keterangan'           => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'review'               => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'created_at'           => ['type' => 'string', 'format' => 'date'],
              'nama_rekening_sumber' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'nama_rekening_masuk'  => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'jenis_budget_sumber'  => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'jenis_budget_masuk'   => ['anyOf' => [['type' => 'string'], ['type' => 'null']]]
            ]
          ]
        ]
      ]
    ]
  )]
  #[Schema(
    properties: [
      'startDate'           => [
        'anyOf' => [['type' => 'string'], ['type' => 'null']],
        'format' => 'date',
        'description' => 'Tanggal awal rentang pencarian (Format: YYYY-MM-DD). Default: tanggal 1 bulan berjalan.'
      ],
      'endDate'           => [
        'anyOf' => [['type' => 'string'], ['type' => 'null']],
        'format' => 'date',
        'description' => 'Tanggal akhir rentang pencarian (Format: YYYY-MM-DD). Default: hari ini.'
      ],
    ]
  )]
  public function getTransaksi(
    ?string $startDate = null,
    ?string $endDate = null,
  ): array {
    $startDate = $startDate ?? date('Y-m-01'); // Default ke tanggal 1
    $endDate = $endDate ?? date('Y-m-d'); // Default ke hari ini
    try {
      $rows = (new ModelsTransaksi())->getInRange($startDate, $endDate);
      return [
        'data' => array_map(fn($row) => [
          'id'                   => (int) $row['id'],
          'jenis_transaksi'      => (string) $row['jenis_transaksi'],
          'harta'                => !empty($row['harta']),
          'barang'               => (string) $row['barang'],
          'rekening_sumber'      => isset($row['rekening_sumber']) && $row['rekening_sumber'] !== '' ? (int) $row['rekening_sumber'] : null,
          'rekening_masuk'       => isset($row['rekening_masuk']) && $row['rekening_masuk'] !== '' ? (int) $row['rekening_masuk'] : null,
          'nominal'              => (float) $row['nominal'],
          'nominal_asing'        => isset($row['nominal_asing']) ? (float) $row['nominal_asing'] : 0.0,
          'kuantitas'            => isset($row['kuantitas']) ? (float) $row['kuantitas'] : 1.0,
          'penyusutan_bunga'     => isset($row['penyusutan_bunga']) ? (float) $row['penyusutan_bunga'] : 0.0,
          'rutin'                => !empty($row['rutin']),
          'kelompok'             => $row['kelompok'] ?? null,
          'tanggal'              => (string) $row['tanggal'],
          'relasi_transaksi'     => isset($row['relasi_transaksi']) && $row['relasi_transaksi'] !== '' ? (int) $row['relasi_transaksi'] : null,
          'attachment'           => $row['attachment'] ?? null,
          'keterangan'           => $row['keterangan'] ?? null,
          'review'               => $row['review'] ?? null,
          'created_at'           => !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d'),
          'nama_rekening_sumber' => $row['nama_rekening_sumber'] ?? null,
          'nama_rekening_masuk'  => $row['nama_rekening_masuk'] ?? null,
          'jenis_budget_sumber'  => $row['jenis_budget_sumber'] ?? null,
          'jenis_budget_masuk'   => $row['jenis_budget_masuk'] ?? null,
        ], $rows)
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
  /**
   * Mencari transaksi berdasarkan kriteria tertentu
   * Gunakan tool ini untuk mendapatkan data transaksi dalam format yang mudah dipahami untuk analisis
   */
  #[McpTool(
    name: 'search_Transaksi',
    description: 'Mencari transaksi berdasarkan kata kunci spesifik (ID transaksi, nama barang, atau nama kelompok). Gunakan untuk menemukan ID transaksi lama sebelum melakukan update_transaksi atau menghubungkan relasi_transaksi.',
    annotations: new ToolAnnotations(
      readOnlyHint: true,
      openWorldHint: false
    ),
    outputSchema: [
      'type' => 'object',
      'properties' => [
        'data' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id'              => ['type' => 'string'],
              'barang'          => ['type' => 'string'],
              'nominal'         => ['type' => 'number'],
              'kelompok'        => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'rekening_sumber' => ['anyOf' => [['type' => 'string'], ['type' => 'null']]],
              'rekening_masuk'  => ['anyOf' => [['type' => 'string'], ['type' => 'null']]]
            ]
          ]
        ]
      ]
    ]
  )]
  #[Schema(
    properties: [
      'search'           => [
        'type' => 'string',
        'description' => 'Kata kunci pencarian: ID transaksi (angka), nama barang, atau nama kelompok/kategori.'
      ],
    ]
  )]
  public function searchTransaksi(
    string $search,
  ): array {
    try {
      $rows = (new ModelsTransaksi())->find($search);
      return [
        'data' => array_map(fn($row) => [
          'id'              => (string) $row['id'],
          'barang'          => (string) $row['barang'],
          'nominal'         => (float) $row['nominal'],
          'kelompok'        => $row['kelompok'] ?? null,
          'rekening_sumber' => $row['rekening_sumber'] ?? null,
          'rekening_masuk'  => $row['rekening_masuk'] ?? null,
        ], $rows)
      ];
    } catch (\Exception $e) {
      throw new ToolCallException("Error: " . $e->getMessage());
    }
  }
}
