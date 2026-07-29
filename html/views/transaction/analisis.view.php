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
          <div id="chart"></div>
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

  var Chart = null;

  var dateRange = dateRange || [
    new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    new Date()
  ];

  const startInput = document.querySelector('#startDate');
  const endInput = document.querySelector('#endDate');
  const statusEl = document.querySelector('#ai-status');
  const logEl = document.querySelector('#ai-last-code');
  const toggleLogBtn = document.querySelector('#toggle-ai-log');

  toggleLogBtn.addEventListener('click', () => {
    const hidden = logEl.style.display === 'none';
    logEl.style.display = hidden ? 'block' : 'none';
    toggleLogBtn.textContent = hidden ? 'Sembunyikan Kode Transform' : 'Lihat Kode Transform';
  });

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

      setStatus(`Data siap: ${currentRawData.length} transaksi. Silakan minta AI untuk menganalisis & membuat grafik.`);
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
       3. update_chart_config  -> AI-authored Highcharts.Options renders #chart
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
            logEl.textContent = params.code;
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
        description: 'Langkah 3: Memuat & merender objek Highcharts.Options yang dibuat secara penuh oleh AI Agent (biasanya memakai hasil transform_data) ke elemen #chart.',
        inputSchema: {
          type: 'object',
          properties: {
            options: {
              type: 'object',
              description: 'Objek Highcharts.Options lengkap yang dibangun oleh AI Agent'
            }
          },
          required: ['options']
        },
        execute: async (params) => {
          if (!params || !params.options) {
            return {
              success: false,
              error: 'Parameter "options" (Highcharts.Options object) wajib diisi oleh AI.'
            };
          }
          try {
            Chart = Highcharts.chart('chart', params.options);
            setStatus('Grafik berhasil dirender oleh AI.');
            return {
              success: true,
              message: 'Objek Highcharts berhasil dirender.'
            };
          } catch (err) {
            setStatus(`Render grafik gagal: ${err.message}`, true);
            return {
              success: false,
              error: err.message
            };
          }
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
