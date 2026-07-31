<div class="container-fluid px-md-5">
  <div class="container mb-3">
    <hr>
    <div class="row">
      <div class="col-md-12 mb-2">
        <div class="date-range input-group">
          <input class="form-control" id="startDate" type="text" placeholder="Mulai">
          <span class="input-group-text">s/d</span>
          <input class="form-control" id="endDate" type="text" placeholder="Akhir">
        </div>
      </div>
      <div class="col-md-12 card">
        <div class="card-body">
          <h6 class="text-center card-title">Chart</h6>
          <div id="charts-container">
            <div id="chart"></div>
          </div>
        </div>
      </div>
      <div class="col-md-12 card mt-2">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="fas fa-robot"></i>&nbsp; Status AI (WebMCP)</h6>
            <button type="button" class="btn btn-sm btn-link" id="toggle-ai-log">Lihat Kode Transform</button>
          </div>
          <div class="small mt-2" id="ai-status">Menunggu AI menganalisis data...</div>
          <pre class="small bg-body-tertiary p-2 rounded mt-2" id="ai-last-code" style="display:none; white-space:pre-wrap;">Belum ada transformasi yang dijalankan.</pre>
          <div class="d-flex align-items-center gap-2 mt-2">
            <select class="form-select form-select-sm" id="preset-select" style="max-width: 220px;">
              <option value="">Preset tersimpan...</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="preset-load" title="Muat preset" disabled><i class="fas fa-play"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="preset-delete" title="Hapus preset" disabled><i class="fas fa-trash"></i></button>
            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="preset-save" title="Simpan grafik saat ini sebagai preset" disabled><i class="fas fa-save"></i> Simpan</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/highcharts@11/highcharts.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/highcharts@11/modules/treemap.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/highcharts@11/modules/series-label.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/highcharts@11/modules/exporting.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/highcharts@11/modules/accessibility.js" crossorigin="anonymous"></script>

<script>
  document.querySelector("#list-tab")?.classList.add("active");
  Highcharts.setOptions({
    chart: {
      backgroundColor: 'transparent',
      plotBackgroundColor: 'transparent',
    }
  });

  const DEFAULT_CHART_TARGET = 'chart';
  const chartsContainer = document.querySelector('#charts-container');
  let charts = {}; // containerId -> { target, code, options, instance }

  const containerIdFor = (target) => {
    const t = (target && String(target).trim()) || DEFAULT_CHART_TARGET;
    if (t === DEFAULT_CHART_TARGET) return DEFAULT_CHART_TARGET;
    const safe = t.toLowerCase().replace(/[^a-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
    return 'chart-' + (safe || 'extra');
  };

  const getOrCreateChartContainer = (target) => {
    const id = containerIdFor(target);
    let el = document.getElementById(id);
    if (!el) {
      el = document.createElement('div');
      el.id = id;
      el.className = 'mt-3';
      chartsContainer.appendChild(el);
    }
    return id;
  };

  const removeChartContainer = (id) => {
    document.getElementById(id)?.remove();
  };

  var dateRange = dateRange || [
    new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    new Date()
  ];

  const startInput = document.querySelector('#startDate');
  const endInput = document.querySelector('#endDate');
  const statusEl = document.querySelector('#ai-status');
  const logEl = document.querySelector('#ai-last-code');
  const toggleLogBtn = document.querySelector('#toggle-ai-log');
  const presetSelect = document.querySelector('#preset-select');
  const presetLoadBtn = document.querySelector('#preset-load');
  const presetDeleteBtn = document.querySelector('#preset-delete');
  const presetSaveBtn = document.querySelector('#preset-save');

  toggleLogBtn.addEventListener('click', () => {
    const hidden = logEl.style.display === 'none';
    logEl.style.display = hidden ? 'block' : 'none';
    toggleLogBtn.textContent = hidden ? 'Sembunyikan Kode Transform' : 'Lihat Kode Transform';
  });

  /* ==========================================================================
     Local presets (Step 2 + Step 3 bundled, all active charts)
     Lets the user save every currently rendered chart (transform code + chart
     options, keyed by target) under a name, then reapply the whole set
     instantly without asking the AI again.
     ========================================================================== */
  const PRESET_KEY = 'analisis_presets_v1';

  const getPresets = () => {
    try {
      return JSON.parse(localStorage.getItem(PRESET_KEY)) || {};
    } catch (e) {
      return {};
    }
  };

  const savePresets = (presets) => localStorage.setItem(PRESET_KEY, JSON.stringify(presets));

  const refreshPresetSelect = () => {
    const presets = getPresets();
    const names = Object.keys(presets);
    presetSelect.innerHTML = '<option value="">Preset tersimpan...</option>' +
      names.map(n => `<option value="${n.replace(/"/g, '&quot;')}">${n}</option>`).join('');
    const hasSelection = !!presetSelect.value;
    presetLoadBtn.disabled = !hasSelection;
    presetDeleteBtn.disabled = !hasSelection;
  };

  presetSelect.addEventListener('change', () => {
    const hasSelection = !!presetSelect.value;
    presetLoadBtn.disabled = !hasSelection;
    presetDeleteBtn.disabled = !hasSelection;
  });

  presetSaveBtn.addEventListener('click', () => {
    const ids = Object.keys(charts);
    if (!ids.length) return;
    const name = prompt('Nama preset:');
    if (!name) return;
    const presets = getPresets();
    presets[name] = {
      charts: ids.map(id => ({
        target: charts[id].target,
        code: charts[id].code,
        optionsBuilder: charts[id].optionsBuilder,
        options: charts[id].options
      }))
    };
    savePresets(presets);
    refreshPresetSelect();
    presetSelect.value = name;
    presetSelect.dispatchEvent(new Event('change'));
  });

  presetLoadBtn.addEventListener('click', () => {
    const preset = getPresets()[presetSelect.value];
    if (!preset) return;
    // Backward-compatible with older single-chart presets ({code, options}).
    const chartList = preset.charts || (preset.options ? [{
      target: DEFAULT_CHART_TARGET,
      code: preset.code,
      options: preset.options
    }] : []);
    if (!chartList.length) return;
    try {
      Object.keys(charts).forEach(id => {
        charts[id].instance?.destroy();
        removeChartContainer(id);
      });
      charts = {};
      chartList.forEach(c => {
        let graphData = null;
        if (currentRawData.length && c.code) {
          try {
            graphData = new Function('rawData', c.code)(currentRawData);
            currentGraphData = graphData;
          } catch (e) {}
        }
        let options = c.options;
        if (c.optionsBuilder) {
          try {
            options = new Function('graphData', 'rawData', c.optionsBuilder)(graphData, currentRawData);
          } catch (e) {
            options = c.options; // fall back to the last-known-good baked options
          }
        }
        const id = getOrCreateChartContainer(c.target);
        const instance = Highcharts.chart(id, options);
        charts[id] = {
          target: c.target || DEFAULT_CHART_TARGET,
          code: c.code,
          optionsBuilder: c.optionsBuilder || null,
          options,
          instance
        };
        if (c.code) lastTransformCode = c.code;
      });
      presetSaveBtn.disabled = false;
      renderLog();
      setStatus(`Preset "${presetSelect.value}" dimuat (${chartList.length} grafik).`);
    } catch (err) {
      setStatus(`Gagal memuat preset: ${err.message}`, true);
    }
  });

  presetDeleteBtn.addEventListener('click', () => {
    const name = presetSelect.value;
    if (!name || !confirm(`Hapus preset "${name}"?`)) return;
    const presets = getPresets();
    delete presets[name];
    savePresets(presets);
    refreshPresetSelect();
  });

  refreshPresetSelect();

  flatpickr(startInput, {
    disableMobile: "true",
    plugins: [new rangePlugin({
      input: endInput
    })],
    maxDate: "today",
    dateFormat: "Y-m-d",
    onChange([start, end]) {
      startInput.value = toDateShortMonth(start);
      endInput.value = toDateShortMonth(end || start);
      dateRange = [start, end || start];
      loadRawData();
    }
  });

  startInput.value = toDateShortMonth(dateRange[0]);
  endInput.value = toDateShortMonth(dateRange[1]);

  /* ==========================================================================
     Raw data loading (Step 1)
     Pulls unpaginated raw transaction rows straight from the same endpoint
     the transaction table uses, for the currently selected date range.
     ========================================================================== */
  let currentRawData = [];
  let currentGraphData = null; // output of the AI's transform_data call
  let lastTransformCode = null;

  const RAW_COLUMNS = ['id', 'jenis_transaksi', 'barang', 'rekening', 'nominal', 'total', 'rutin', 'kelompok', 'tanggal', 'keterangan'];

  const setStatus = (text, isError = false) => {
    statusEl.textContent = text;
    statusEl.classList.toggle('text-danger', isError);
  };

  const renderLog = () => {
    const ids = Object.keys(charts);
    if (!ids.length) {
      logEl.textContent = lastTransformCode
        ? `// Step 2: transform_data (belum dipakai di grafik manapun)\n${lastTransformCode.trim()}`
        : 'Belum ada transformasi yang dijalankan.';
      return;
    }
    logEl.textContent = ids.map(id => {
      const c = charts[id];
      const parts = [`// Grafik "${c.target}"`];
      if (c.code) parts.push(`// Step 2: transform_data\n${c.code.trim()}`);
      parts.push(c.optionsBuilder
        ? `// Step 3: update_chart_config (optionsBuilder, auto-refresh aktif)\n${c.optionsBuilder.trim()}`
        : `// Step 3: update_chart_config (options statis, tidak auto-refresh)\n${JSON.stringify(c.options, null, 2)}`);
      return parts.join('\n');
    }).join('\n\n---\n\n');
  };

  /* ==========================================================================
     Re-runs every chart's stored transform code + optionsBuilder against the
     freshly loaded currentRawData, so charts stay in sync with the active
     date range without needing the AI in the loop again. Charts saved with a
     static "options" object (no optionsBuilder) are left untouched.
     ========================================================================== */
  const refreshAllCharts = () => {
    let refreshed = 0;
    Object.keys(charts).forEach(id => {
      const c = charts[id];
      if (!c.code || !c.optionsBuilder) return;
      try {
        const graphData = new Function('rawData', c.code)(currentRawData);
        const options = new Function('graphData', 'rawData', c.optionsBuilder)(graphData, currentRawData);
        c.instance?.destroy();
        c.instance = Highcharts.chart(id, options);
        c.options = options;
        refreshed++;
      } catch (err) {
        setStatus(`Gagal memperbarui grafik "${c.target}" setelah ganti tanggal: ${err.message}`, true);
      }
    });
    if (refreshed) renderLog();
    return refreshed;
  };

  async function loadRawData() {
    setStatus('Memuat data transaksi...');
    try {
      const body = new URLSearchParams();
      body.append('draw', '1');
      body.append('start', '0');
      body.append('length', '-1');
      body.append('startDate', dateRange[0].toLocaleDateString('sv-SE'));
      body.append('endDate', dateRange[1].toLocaleDateString('sv-SE'));
      RAW_COLUMNS.forEach((name, i) => {
        body.append(`columns[${i}][data]`, name);
        body.append(`columns[${i}][name]`, '');
        body.append(`columns[${i}][searchable]`, 'true');
        body.append(`columns[${i}][orderable]`, 'true');
        body.append(`columns[${i}][search][value]`, '');
        body.append(`columns[${i}][search][regex]`, 'false');
      });

      const res = await fetch('<?= BASEURL ?>/Transaction/datatable', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body
      });
      const json = await res.json();
      if (!res.ok || json.error) throw new Error(json.error || `HTTP ${res.status}`);

      currentRawData = json.data || [];
      currentGraphData = null;

      const refreshed = refreshAllCharts();
      setStatus(refreshed
        ? `Data siap: ${currentRawData.length} transaksi. ${refreshed} grafik diperbarui otomatis.`
        : `Data siap: ${currentRawData.length} transaksi. Silakan minta AI untuk menganalisis & membuat grafik.`);
    } catch (err) {
      setStatus(`Gagal memuat data: ${err.message}`, true);
      showAlert(`Gagal memuat data analisis: ${err.message}`, 'danger');
    }
  }

  loadRawData();

  /* ==========================================================================
     WebMCP Tools Protocol

     Flow the AI is expected to follow:
       1. get_raw_transactions -> raw rows for the active date range
       2. transform_data       -> AI-authored JS reshapes raw rows into series data
       3. update_chart_config  -> renders a chart (optionally targeted, for
                                   multiple charts at once). Prefer sending
                                   "optionsBuilder" (JS code) over a static
                                   "options" object: it's stored alongside the
                                   chart's transform code and both are re-run
                                   automatically whenever the date range
                                   changes, so charts stay live without the AI.
       4. delete_chart          -> optional, removes a previously rendered chart
     ========================================================================== */
  function registerWebMCPAnalytics() {
    const tools = [{
        name: 'get_raw_transactions',
        description: 'Langkah 1: Mendapatkan baris data transaksi mentah (tanpa agregasi apapun) sesuai rentang tanggal aktif di halaman Analisis. Tidak ada agregat siap-pakai (cashIn/cashOut/saldo/comps) yang disediakan — semua perhitungan/agregasi harus dilakukan sendiri oleh AI lewat transform_data. Kirim startDate & endDate (format YYYY-MM-DD) untuk memuat ulang data pada rentang tanggal lain. Panggil ini sebelum transform_data.',
        inputSchema: {
          type: 'object',
          properties: {
            startDate: {
              type: 'string',
              description: 'Format YYYY-MM-DD, opsional. Jika diisi bersama endDate, akan memuat ulang data & memperbarui filter tanggal di halaman.'
            },
            endDate: {
              type: 'string',
              description: 'Format YYYY-MM-DD, opsional.'
            }
          }
        },
        execute: async (params) => {
          if (params && params.startDate && params.endDate) {
            const start = new Date(params.startDate);
            const end = new Date(params.endDate);
            if (isNaN(start) || isNaN(end)) {
              return {
                success: false,
                error: 'startDate/endDate tidak valid, gunakan format YYYY-MM-DD.'
              };
            }
            dateRange = [start, end];
            startInput.value = toDateShortMonth(start);
            endInput.value = toDateShortMonth(end);
          }
          await loadRawData();
          return {
            success: true,
            startDate: dateRange[0].toLocaleDateString('sv-SE'),
            endDate: dateRange[1].toLocaleDateString('sv-SE'),
            count: currentRawData.length,
            data: currentRawData
          };
        }
      },
      {
        name: 'transform_data',
        description: 'Langkah 2: Menjalankan kode JavaScript buatan AI Agent untuk mentransformasi data mentah (dari get_raw_transactions) menjadi struktur data siap-pakai untuk grafik, misalnya query/agregasi ala-SQL (SUM per tanggal, GROUP BY kelompok, dsb). "code" adalah isi body sebuah fungsi JS dengan parameter `rawData` (array transaksi mentah, satu-satunya sumber data) dan WAJIB memakai `return` untuk hasilnya. Hasil transformasi dikembalikan agar AI dapat memeriksanya sebelum dipakai di update_chart_config.',
        inputSchema: {
          type: 'object',
          properties: {
            code: {
              type: 'string',
              description: 'Body fungsi JS. Contoh: "return rawData.filter(r => r.jenis_transaksi === \'PENGELUARAN\').map(r => [r.tanggal, r.nominal]);"'
            }
          },
          required: ['code']
        },
        execute: async (params) => {
          if (!params || typeof params.code !== 'string') {
            return {
              success: false,
              error: 'Parameter "code" (string) wajib diisi oleh AI.'
            };
          }
          if (!currentRawData.length) {
            return {
              success: false,
              error: 'Belum ada data mentah. Panggil get_raw_transactions terlebih dahulu.'
            };
          }
          try {
            const transform = new Function('rawData', params.code);
            const result = transform(currentRawData);
            currentGraphData = result;
            lastTransformCode = params.code;
            renderLog();
            setStatus('Transformasi data berhasil dijalankan oleh AI.');
            return {
              success: true,
              result
            };
          } catch (err) {
            setStatus(`Transformasi data gagal: ${err.message}`, true);
            return {
              success: false,
              error: err.message
            };
          }
        }
      },
      {
        name: 'update_chart_config',
        description: 'Langkah 3: Merender grafik Highcharts. Halaman ini mendukung LEBIH DARI SATU grafik sekaligus: kirim "target" untuk membuat/memperbarui grafik terpisah (mis. "trend", "per-kelompok"). Jika "target" kosong, memakai grafik utama. Memanggil ulang dengan target yang sama akan mengganti (replace) grafik tersebut. DIREKOMENDASIKAN memakai "optionsBuilder" (bukan "options" statis) agar grafik otomatis diperbarui saat pengguna mengganti rentang tanggal, tanpa perlu memanggil AI lagi.',
        inputSchema: {
          type: 'object',
          properties: {
            options: {
              type: 'object',
              description: 'Objek Highcharts.Options statis yang sudah jadi. Pakai ini hanya untuk grafik sekali-pakai — TIDAK akan otomatis diperbarui saat rentang tanggal berubah. Abaikan jika mengisi "optionsBuilder".'
            },
            optionsBuilder: {
              type: 'string',
              description: 'Direkomendasikan. Body fungsi JS dengan parameter `graphData` (hasil transform_data terbaru) dan `rawData` (data mentah terbaru), WAJIB `return` sebuah objek Highcharts.Options. Disimpan dan dijalankan ulang otomatis (bersama kode transform_data milik grafik ini) setiap kali rentang tanggal berubah, sehingga grafik selalu mengikuti data terbaru.'
            },
            target: {
              type: 'string',
              description: 'Nama grafik, opsional. Gunakan nama berbeda untuk menampilkan beberapa grafik sekaligus di halaman. Kosongkan untuk grafik utama.'
            }
          }
        },
        execute: async (params) => {
          if (!params || (!params.options && !params.optionsBuilder)) {
            return {
              success: false,
              error: 'Wajib mengisi salah satu: "options" (objek statis) atau "optionsBuilder" (kode JS, direkomendasikan).'
            };
          }
          try {
            const target = (params.target && String(params.target).trim()) || DEFAULT_CHART_TARGET;
            const id = getOrCreateChartContainer(target);
            let options = params.options;
            if (params.optionsBuilder) {
              options = new Function('graphData', 'rawData', params.optionsBuilder)(currentGraphData, currentRawData);
            }
            charts[id]?.instance?.destroy();
            const instance = Highcharts.chart(id, options);
            charts[id] = {
              target,
              code: lastTransformCode,
              optionsBuilder: params.optionsBuilder || null,
              options,
              instance
            };
            presetSaveBtn.disabled = false;
            renderLog();
            setStatus(`Grafik "${target}" berhasil dirender oleh AI.`);
            return {
              success: true,
              message: 'Objek Highcharts berhasil dirender.',
              target,
              autoRefresh: !!(params.optionsBuilder && lastTransformCode),
              activeCharts: Object.values(charts).map(c => c.target)
            };
          } catch (err) {
            setStatus(`Render grafik gagal: ${err.message}`, true);
            return {
              success: false,
              error: err.message
            };
          }
        }
      },
      {
        name: 'delete_chart',
        description: 'Menghapus grafik dengan "target" tertentu dari halaman (untuk membersihkan/mengganti tampilan multi-grafik). Kirim nilai "target" yang sama dengan yang dipakai saat update_chart_config. Kosongkan untuk menghapus grafik utama.',
        inputSchema: {
          type: 'object',
          properties: {
            target: {
              type: 'string',
              description: 'Nama grafik yang ingin dihapus, opsional (default: grafik utama).'
            }
          }
        },
        execute: async (params) => {
          const target = (params && params.target && String(params.target).trim()) || DEFAULT_CHART_TARGET;
          const id = containerIdFor(target);
          if (!charts[id]) {
            return {
              success: false,
              error: `Grafik dengan target "${target}" tidak ditemukan.`
            };
          }
          charts[id].instance?.destroy();
          delete charts[id];
          removeChartContainer(id);
          presetSaveBtn.disabled = Object.keys(charts).length === 0;
          renderLog();
          setStatus(`Grafik "${target}" dihapus.`);
          return {
            success: true,
            activeCharts: Object.values(charts).map(c => c.target)
          };
        }
      }
    ];

    const mc = navigator.modelContext || document.modelContext;
    if (mc && typeof mc.registerTool === 'function') {
      tools.forEach(t => {
        try {
          mc.registerTool(t);
        } catch (e) {}
      });
    }

    if (window.webmcp) {
      tools.forEach(t => window.webmcp.registerTool(t));
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerWebMCPAnalytics);
  } else {
    registerWebMCPAnalytics();
  }
</script>
