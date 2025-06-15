<div class="container-fluid px-md-5">
  <div class="card card-saldo">
    <div>Saldo Efektif</div>
    <h3 class="saldo" id="text-saldo"></h3>
    <hr>
    <a href="<?= BASEURL ?>\Rekening">
      <span>Semua Rekening</span>
      ❱
    </a>
  </div>
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
      <div class="col-md-12 card p-3 text-center">
        <div style="font-size:1.2em ;">
          <span class="text-success"><b id="text-pemasukan"></b>&nbsp;<small>(Pemasukan&nbsp;✅)</small></span>
          |
          <span class="text-danger"><b id="text-pengeluaran"></b>&nbsp;<small>(Pengeluaran&nbsp;❌)</small></span>
          |
          <span class="text-warning"><b id="text-net"></b>&nbsp;<small>(Net&nbsp;Cashflow&nbsp;💰)</small></span>
        </div>
      </div>
      <div class="col-md-6 card">
        <div class="card-body">
          <h6 class="text-center card-title">Tren Cashflow</h6>
          <div id="arusKasChart"></div>
        </div>
      </div>
      <div class="col-md-6 card">
        <div class="card-body">
          <h6 class="text-center card-title">Komposisi Pengeluaran Berdasarkan Kelompok</h6>
          <div id="treemapChart"></div>
        </div>
      </div>
    </div>
  </div>
  <table data-label="List Rekening" class="table table-responsive myTable border-bottom display nowrap" id="FormatTable">
    <thead class="sticky-top">
      <tr>
        <th scope="col" class="">ID</th>
        <th scope="col" class="">Jenis Transaksi</th>
        <th scope="col" class="">Barang / Judul</th>
        <th scope="col" class="">Rekening</th>
        <th scope="col" class="">Nominal</th>
        <th scope="col" class="">Total</th>
        <th scope="col" class="">Rutin</th>
        <th scope="col" class="">Kelompok</th>
        <th scope="col" class="">Tanggal</th>
        <th scope="col" class="">Keterangan</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
<dialog>
  <div class="dialog-header mb-2">
    <h4 class="fw-bold w-100 text-center" id="dialog-title"></h4>
    <form method="dialog">
      <button><i class="fas fs-4 fw-bold fa-times"></i></button>
    </form>
  </div>
  <div class="dialog-body row flex-row">
    <div class="col-md-8"></div>
    <div class="col-md-4">
      <form id="form" action="<?= BASEURL ?>/Record/review" method="post" style="--primary-color:var(--secondary-color)">
        <input type="hidden" name="id">
        <div class="form-group">
          <label>Review</label>
          <textarea name="review" class="form-control mt-1 border-bottom border-primary mb-2" autocomplete="off" placeholder="Review" rows="4"></textarea>
          <?php InputValidator('review') ?>
          <input type="submit" class="btn w-100 btn-secondary" value="Beri Review" name="record">
          <hr>
          <button role="button" type="button" onclick="deleteRekeing()" class="btn w-100" style="--primary-color:var(--red-color)" id="btn-delete">Hapus <i class="fas fa-trash-alt"></i></button>
        </div>
      </form>
      <a id="attachment" href="#" target="_blank">
        <canvas id="preview-canvas" style="display:none;"></canvas>
      </a>
    </div>
  </div>
</dialog>
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.css" rel="stylesheet" integrity="sha384-orLdZZ463q2Du2MSqmwTiuVLakuDqKN7tEJF7uICXIZ793ejMDPC5RK1ve6caXLS" crossorigin="anonymous">
<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.js" integrity="sha384-IR2ESnTJ4NOqqJEZ2amCtwDOnKALFLe94drQmiqmlvKt7F1wv+CPFe0Wfu4uNYrv" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js" crossorigin="anonymous"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap" crossorigin="anonymous"></script> -->
<script src="https://code.highcharts.com/highcharts.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/treemap.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/series-label.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/exporting.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/accessibility.js" crossorigin="anonymous"></script>

<script>
  const FORM = document.querySelector('form#form');
  document.querySelector("#list-tab")?.classList.add("active");
  const TABLE = document.querySelector('#FormatTable');
  const MODAL = document.querySelector('dialog')
  const rootStyle = getComputedStyle(document.documentElement);
  const primaryColor = rootStyle.getPropertyValue('--primary-color').trim();
  const warningColor = rootStyle.getPropertyValue('--yellow-color').trim(); // or another var
  const bgColor = rootStyle.getPropertyValue('--bg-color').trim(); // or another var
  Highcharts.setOptions({
    chart: {
      backgroundColor: bgColor,
      plotBackgroundColor: bgColor,
      // plotShadow: true,
    }
  });
  let refreshGraph = true;
  const d = new Date();
  let dateRange = [
    new Date(d.getFullYear(), d.getMonth(), 1),
    d
  ];
  delete d;
  const startInput = document.querySelector('#startDate');
  const endInput = document.querySelector('#endDate');
  flatpickr(startInput, {
    plugins: [new rangePlugin({
      input: endInput
    })],
    maxDate: "today",
    dateFormat: "Y-m-d", // internal format
    onChange([start, end]) {
      startInput.value = toDateShortMonth(start);
      endInput.value = toDateShortMonth(end || start); // use start date if end is not selected
      dateRange = [start, end || start]
      DT_TABLE.draw();
    }
  });
  startInput.value = toDateShortMonth(dateRange[0]);
  endInput.value = toDateShortMonth(dateRange[1]); // use start date if end is not selected
  const CompsChart = Highcharts.chart('treemapChart', {
    chart: {
      type: 'treemap'
    },
    tooltip: {
      pointFormat: '<b>{point.name}</b>: {point.value:,.0f}'
    },
    series: [{
      type: "treemap",
      layoutAlgorithm: "squarified",
      data: []
    }],
    title: false,
  });
  const CashFlowChart = Highcharts.chart('arusKasChart', {
    title: false,
    xAxis: {
      type: 'datetime',
      title: {
        text: 'Tanggal'
      },
    },
    yAxis: {
      title: {
        text: 'Jumlah (Rp.)'
      },
      stackLabels: {
        enabled: true
      }
    },
    tooltip: {
      shared: true,
    },
    plotOptions: {
      column: {
        stacking: 'normal', // ✅ Enable stacking for bar series
        borderWidth: 0
      },
      area: {
        stacking: null,
        // stacking: 'normal',
        lineColor: '#666666',
        lineWidth: 4,
        marker: {
          lineWidth: 4,
          lineColor: '#666666'
        }
      }
    },
    series: [{
        name: 'Pemasukan',
        type: 'column',
        stack: 'Pemasukan',
        data: [],
        // color: 'rgba(76, 175, 80, 0.6)'
      },
      {
        name: 'Pemasukan (Non Rutin)',
        type: 'column',
        stack: 'Pemasukan',
        data: [],
        // color: 'rgba(76, 175, 100, 0.6)'
      },
      {
        name: 'Pengeluaran',
        type: 'column',
        stack: 'Pengeluaran',
        data: [],
        // color: 'rgba(244, 67, 54, 0.6)'
      }, {
        name: 'Pengeluaran (Non Rutin)',
        type: 'column',
        stack: 'Pengeluaran',
        data: [],
        // color: 'rgba(255, 152, 0, 0.6)'
      }, {
        name: 'Cash Flow',
        type: 'line',
        data: [],
        // color: 'rgba(76, 80,175, 0.6)'
      },
    ]
  });
  const UpdateGraph = (data) => {
    for (let i = 0; i < CashFlowChart.series.length; i++) {
      CashFlowChart.series[i].setData(data.cashFlow[i] ?? [], false)
    }
    CompsChart.series[0].setData(data.comps, false)
    CashFlowChart.redraw()
    CompsChart.redraw()
    document.querySelector('#text-pemasukan').innerHTML = 'Rp.&nbsp;' + data.cashIn.toLocaleString('id')
    document.querySelector('#text-pengeluaran').innerHTML = 'Rp.&nbsp;' + data.cashOut.toLocaleString('id')
    document.querySelector('#text-net').innerHTML = 'Rp.&nbsp;' + (data.cashIn - data.cashOut).toLocaleString('id')
    document.querySelector('#text-saldo').innerHTML = 'Rp.&nbsp;' + data.saldo.toLocaleString('id')
  }
  const DT_TABLE = new DataTable(TABLE, {
    "dom": "ftlp",
    "language": {
      "lengthMenu": "Per _MENU_ Data",
      "zeroRecords": "Tidak ada transaksi",
      "infoEmpty": "Tidak ada transaksi",
      "search": "",
      "paginate": {
        "first": "⇚",
        "last": "⇛",
        "next": "⇒",
        "previous": "⇐"
      }
    },
    'bInfo': false,
    'fixedHeader': false,
    'processing': true,
    'serverSide': true,
    // 'bAutoWidth': false,
    'ajax': {
      'url': '<?= BASEURL ?>/Transaction/datatable',
      'type': 'POST',
      'data': (d) => {
        d.startDate = dateRange[0].toLocaleDateString('sv-SE')
        d.endDate = dateRange[1].toLocaleDateString('sv-SE')
        d.queryGraph = refreshGraph
        return d;
      },
      "dataSrc": (json) => {
        if (refreshGraph) UpdateGraph(json);
        refreshGraph = false;
        return json.data; // Return the data part of the response
      },
      "error": async (xhr, error, code) => {
        let errorMessage = ` Terjadi Kesalahan: ${code}`;

        if (xhr.status === 429) {
          errorMessage = 'Terlalu banyak permintaan.<br><small>Silakan coba lagi nanti.</small>';
          isRateLimit = true;
          setTimeout(() => {
            isRateLimit = false
          }, 60000)
        } else if (xhr.status === 401) {
          errorMessage = 'Unauthorized.<br><small>Silahkan login kembali</small>';
          setTimeout(() => {
            sessionStorage.clear();
            removeCache()
            window.location.href = "<?= BASEURL ?>/Auth/Logout"
          }, 1500);
        } else if (xhr.status === 0) {
          errorMessage = 'Tidak dapat terhubung ke server.<br><small>Pastikan koneksi internet Anda stabil.</small>';
        } else if (xhr.status >= 400 && xhr.status < 500) {
          errorMessage = 'Terjadi kesalahan pada permintaan Anda. #102.';
        } else if (xhr.status >= 500) {
          errorMessage = 'Terjadi kesalahan pada server.<br><small>Silakan coba lagi nanti.</small>';
        }
        if (DT_TABLE.rows().data().toArray().length === 0) {}
        showAlert('danger', errorMessage)
        loadingPage.style.display = "none";
      }
    },
    "order": [
      [0, "desc"]
    ],
    responsive: true,
    columnDefs: [{
        responsivePriority: 2,
        targets: 0
      },
      {
        responsivePriority: 1,
        targets: [1, 3, 4, 7, 8]
      },
      {
        responsivePriority: 0,
        targets: [2, 5]
      },
    ],
    'columns': [{
        'data': 'id',
        'title': 'ID',
        'width': '5%',
      },
      {
        'data': 'jenis_transaksi',
        'title': 'Jenis Transaksi',
      },
      {
        // 'width': '30%',
        'data': 'barang',
        'title': 'Barang / Jasa',
        'render': (item, type, data, meta) => {
          harta = (data.harta) ? '<span class="badge rounded-pill bg-danger">Properti</span>' : '';
          bunga = data.penyusutan_bunga && +data.penyusutan_bunga ?
            +data.penyusutan_bunga > 0 ? `<div class="kuitansi small font-italic text-succes">Rp${(+data.penyusutan_bunga).toLocaleString('id')}/bln</div>` :
            `<div class="kuitansi small font-italic text-danger">Rp.${(+data.penyusutan_bunga).toLocaleString('id')}/bln</div>` : ''
          '';
          // `<a href="<?= BASEURL ?>/Transaction/detail/${data.relasi_transaksi}" class="kuitansi small"><i class="fas fa-link"></i>${data.relasi_transaksi}</a>` :
          relas = data.relasi_transaksi ?
            /* HTML */
            `<div><a href="javascript:findData('${data.relasi_transaksi}')" class="kuitansi small"><i class="fas fa-link"></i> trx.${data.relasi_transaksi}</a></div>` :
            '';
          return /* HTML */ `
          <strong>${data.barang}${harta}</strong>${bunga}${relas}
        `
        },
      },
      {
        'data': 'rekening',
        'title': 'Rekening',
        // 'width': '20%',
      },
      {
        // 'width': '20%',
        'title': 'Nominal',
        'data': 'nominal',
        'render': (item, type, data, meta) => {
          if (data.mata_uang.length > 0) return /* HTML */ `
            <div class="nominal fw-bold text-primary">${formattedNumber.format(data.nominal_asing)},- ${data.mata_uang}</div>
            <div class="small text-secondary">Rp.&nbsp${formattedNumber.format(data.nominal)},-</div>`
          else return /* HTML */ `
            <div class="nominal fw-bold text-primary">Rp.&nbsp${formattedNumber.format(data.nominal)},-</div>`
        }
      },
      {
        'title': 'Total',
        'data': function(data, type, dataToSet) {
          if (data.mata_uang.length > 0) return /* HTML */ `
            <div class="nominal fw-bold text-primary">${formattedNumber.format(data.nominal_asing*data.kuantitas)},- ${data.mata_uang} <span class="small">(${data.kuantitas} Qty)</span></div>
            <div class="small text-secondary">Rp.&nbsp${formattedNumber.format(data.nominal*data.kuantitas)},-</div>`
          else return /* HTML */ `
            <div class="nominal fw-bold text-primary">Rp.&nbsp${formattedNumber.format(data.nominal*data.kuantitas)},- <span class="small">(${data.kuantitas} Qty)</span></div>`
        }
      },
      {
        'title': 'Rutin',
        'data': 'rutin',
        // 'render': (item, type, data, meta) => item ? '<span class="text-primary">✔️</span>' : '<span class="text-danger">✖️</span>'
        'render': (item, type, data, meta) => item ? '<span class="badge rounded-pill bg-primary">Rutin</span>' : '<span class="badge rounded-pill bg-warning">Non Rutin</span>'
      },
      {
        'title': 'Kelompok',
        'data': 'kelompok',
      },
      {
        'title': 'Tanggal',
        'data': 'tanggal',
        'render': (item, type, data, meta) => toDateShortMonth(new Date(item))
      },
      {
        'title': 'Keterangan',
        'data': 'keterangan',
        'render': (item, type, data, meta) => {
          return /* HTML */ `
            <div class="small w-lg-50 truncate-text"><span class="fw-bold text-warning">${data.attachment? '<i class="fas fa-paperclip"></i>' : ''}</span>${data.keterangan}</div>
            <div class="small w-lg-50 truncate-text text-secondary">${data.review}</div>
            `
        }
      },
    ],
    initComplete: function() {
      const input = document.querySelector('#FormatTable_wrapper .dt-search input');
      input.placeholder = '🔍 Cari Disini...';
    },
    createdRow: function(row, data, dataIndex) {
      const cells = row.querySelectorAll('td');
      cells.forEach((td, index) => {
        const header = DT_TABLE.column(index).header();
        td.setAttribute('data-label', header.textContent);
      });
    },
    'drawCallback': function(settings) {
      // Convert DataTable to array (assumes DT_TABLE is a DataTables instance)
      var tableData = DT_TABLE.rows().data().toArray();

      // Only proceed if table has visible records
      if (DT_TABLE.rows({
          filter: 'applied'
        }).count() > 0) {
        const tooltipRows = TABLE.querySelectorAll('tbody tr');

        tooltipRows.forEach(row => {
          row.setAttribute('data-bs-toggle', 'tooltip');
          row.setAttribute('data-bs-placement', 'bottom');
          row.setAttribute('data-bs-html', 'true');
          row.setAttribute('title', 'Klik 2x untuk membuka');

          // Initialize Bootstrap tooltip
          new bootstrap.Tooltip(row, {
            template: /* HTML */ `
              <div class="tooltip" role="tooltip">
                <div class="arrow"></div>
                <h6><strong><div class="tooltip-inner"></div></strong></h6>
              </div>
            `
          });
        });

      }
    }
  });

  // Double click to edit
  TABLE.querySelector('tbody').addEventListener('dblclick', function(e) {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const row = DT_TABLE.row(tr).data();
    MODAL.querySelector('#dialog-title').textContent = row.barang;
    MODAL.querySelector('.dialog-body').children[0].innerHTML = ''
    tr.childNodes.forEach(td => {
      if (td.dataset.label == 'Keterangan') return;
      const divEl = document.createElement('div');
      // divEl.childNodes
      divEl.classList.add('d-flex', 'flex-row', 'p-3', 'align-items-center', 'mb-2', 'border-2', 'border-bottom', 'border-primary', 'justify-content-between', 'w-100');
      divEl.innerHTML = /* HTML */ `
        <div class="fw-bold">${td.dataset.label}</div>
        <div>${td.innerHTML}</div>
      `
      MODAL.querySelector('.dialog-body').children[0].append(divEl);
    })
    const divEl = document.createElement('div');
    divEl.classList.add('d-flex', 'flex-row', 'justify-content-between', 'w-100', 'pt-3', 'px-5');
    divEl.innerHTML = /* HTML */ `<pre class="no-decoration">${row.keterangan}<pre>`
    MODAL.querySelector('.dialog-body').children[0].append(divEl);
    // FORM.review.textContent = row.review;
    FORM.review.value = row.review;
    FORM.id.value = row.id
    const loadingTask = pdfjsLib.getDocument(row.attachment);
    const canvas = document.querySelector('#preview-canvas');
    const ctx = canvas.getContext('2d');
    canvas.style.display = 'none';
    loadingTask.promise.then(function(pdf) {
      pdf.getPage(1).then(function(page) {
        const scale = 1.5;
        const viewport = page.getViewport({
          scale: scale
        });

        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
          canvasContext: ctx,
          viewport: viewport
        };
        page.render(renderContext);
        canvas.style.display = 'block';
      });
    }).catch(e => {
      const img = new Image();
      img.onload = () => {
        const maxWidth = 600;
        const scale = Math.min(maxWidth / img.width, 1);
        canvas.width = img.width * scale;
        canvas.height = img.height * scale;
        canvas.style.display = "block";

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      };
      img.src = row.attachment;
    });

    MODAL.querySelector('a#attachment').href = row.attachment;
    MODAL.showModal()

    // window.location.href = `<?= BASEURL ?>/Transaction/detail/${row.id}`;
  });
  MODAL.addEventListener('click', function(event) {
    var rect = MODAL.getBoundingClientRect();
    var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
    if (!isInDialog) {
      MODAL.close();
    }
  })
  const findData = (id) => {
    DT_TABLE.search(id).draw();
    MODAL.close()
  }
  const deleteRekeing = () => {
    Swal.fire({
      title: `Hapus ${MODAL.querySelector('#dialog-title').textContent}..?`,
      text: "Tindakan ini tidak bisa dikembalikan",
      icon: "warning",
      target: "dialog",
      showCancelButton: true,
    }).then((result) => {
      if (!result.isConfirmed) return;
      FORM.action = "<?= BASEURL ?>/Record/delete"
      console.log(FORM);
      FORM.submit()
    });
  }
</script>