<div class="container">
  <?php $Controller->view('rekening/topbar', $data); ?>
  <table data-label="List Rekening" class="table table-responsive myTable border-bottom" id="FormatTable">
    <thead class="sticky-top">
      <tr>
        <th scope="col" class="no-sort no-search"></th>
        <th scope="col" class="no-sort no-search">ID</th>
        <th scope="col" class="no-sort">Nama</th>
        <th scope="col" class="no-sort">Saldo</th>
        <th scope="col" class="no-sort">Keterangan</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div class="my-3 card card-saldo">
    <div>Saldo Efektif</div>
    <h3 class="saldo" id="text-saldo"></h3>
    <hr>
  </div>
  <div class="card rounded">
    <div class="card-body">

      <div class="date-range input-group">
        <input class="form-control" id="startDate" type="text" placeholder="Mulai">
        <span class="input-group-text">s/d</span>
        <input class="form-control" id="endDate" type="text" placeholder="Akhir">
      </div>
      <h5 class="mt-2 text-center fw-bolder card-title">Cashflow</h5>
      <div id="arusRekeningChart"></div>
    </div>
  </div>
</div>
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.css" rel="stylesheet" integrity="sha384-orLdZZ463q2Du2MSqmwTiuVLakuDqKN7tEJF7uICXIZ793ejMDPC5RK1ve6caXLS" crossorigin="anonymous">
<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.js" integrity="sha384-IR2ESnTJ4NOqqJEZ2amCtwDOnKALFLe94drQmiqmlvKt7F1wv+CPFe0Wfu4uNYrv" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/select/3.0.1/js/dataTables.select.js" crossorigin="anonymous"></script>
<link href="https://cdn.datatables.net/select/3.0.1/css/select.dataTables.css" rel="stylesheet" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/highcharts.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/treemap.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/series-label.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/exporting.js" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/modules/accessibility.js" crossorigin="anonymous"></script>
<script>
  document.querySelector("#list-tab")?.classList.add("active");
  const TABLE = document.querySelector('#FormatTable');
  const rootStyle = getComputedStyle(document.documentElement);
  const primaryColor = rootStyle.getPropertyValue('--primary-color').trim();
  const warningColor = rootStyle.getPropertyValue('--yellow-color').trim(); // or another var
  const bgColor = rootStyle.getPropertyValue('--bg-color').trim(); // or another var
  let accountsCashFlow
  const d = new Date();
  let dateRange = [
    new Date(d.getFullYear(), 0, 1),
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
    async onChange([start, end]) {
      startInput.value = toDateShortMonth(start);
      endInput.value = toDateShortMonth(end || start); // use start date if end is not selected
      dateRange = [start, end || start]
      await fetchData();
      drawGraph();
    }
  });
  startInput.value = toDateShortMonth(dateRange[0]);
  endInput.value = toDateShortMonth(dateRange[1]); // use start date if end is not selected
  const DT_TABLE = new DataTable(TABLE, {
    "dom": "ftlp",
    "language": {
      "lengthMenu": "Per _MENU_ Data",
      "zeroRecords": "Tidak ada rekening",
      "infoEmpty": "Tidak ada rekening",
      "search": "",
      "paginate": {
        "first": "⇚",
        "last": "⇛",
        "next": "⇒",
        "previous": "⇐"
      }
    },
    'bInfo': false,
    'processing': true,
    'serverSide': true,
    'responsive': true,
    select: {
      style: 'multi',
      selector: 'td:first-child'
    },
    'ajax': {
      'url': '<?= BASEURL ?>/Rekening/args?datatable=1',
      'type': 'POST',
      'data': (post_data) => {
        // Append SelectedDate to the POST data
        // post_data.selectedDate = dayMap[SelectedIndex].date;
        // post_data.mutasiMode = mutasiMode;
        return post_data;
      },
      "dataSrc": (json) => {
        // generateHeader(json.total)
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
        showAlert(errorMessage, 'danger');
        loadingPage.style.display = "none";
      }
    },
    "order": [
      [1, "desc"]
    ],
    "columnDefs": [{
        targets: "no-search",
        searchable: false,
      },
      {
        targets: "no-sort",
        "orderable": false
      },
      {
        targets: [0],
        render: DataTable.render.select(),
        orderable: false,
        // "className": 'd-none d-sm-table-cell align-middle'
      },
    ],
    'columns': [{
        'data': 'id',
        'title': '',
      }, {
        'data': 'id',
        'title': 'ID',
        width: '5%',
      },
      {
        width: '30%',
        'title': 'Nama',
        'data': 'nama',
        'render': function(item, type, data, meta) {
          harta = (data.harta) ? '<span class="badge rounded-pill bg-danger">Properti</span>' : '';
          return /* HTML */ `
            <div><strong>${data.nama}&nbsp;${harta}</strong></div>
            <div class="kuitansi small font-italic">${data.no_asli.length > 0 ? 'No./Ref. Sebenarnya : '+data.no_asli:''}</div>
            <div class="kuitansi small font-italic">Dibuat : ${toDateShortMonth(new Date(data.tgl_dibuat))}</div>
            <div class="kuitansi small font-italic">${(!data.aktif) ? 'Ditutup : '+ toDateShortMonth(new Date(data.tgl_ditutup)): ''}</div>
          `
        }
      },
      {
        width: '20%',
        'title': 'Saldo',
        'data': 'saldo',
        'render': function(item, type, data, meta) {
          if (data.nominal_asing.length > 0) return /* HTML */ `
            <span class="nominal fw-bold text-primary">${formattedNumber.format(data.saldo_asing)+' '+data.nominal_asing}</span><br>
            <span class="small text-secondary">Rp.&nbsp${formattedNumber.format(data.saldo)},-</span>`
          else return /* HTML */ `
            <span class="nominal fw-bold text-primary">Rp.&nbsp${formattedNumber.format(data.saldo)},-</span>`
        }
      },
      {
        'title': 'Keterangan',
        'data': 'keterangan',
        // 'render': (data, type, full, meta) => /* HTML */ `<pre>${}</pre>`;
      },
    ],
    initComplete: function() {
      const input = document.querySelector('#FormatTable_wrapper .dt-search input');

      // Example modifications:
      input.placeholder = '🔍 Cari Disini...';
      // input.classList.add('w-100', 'w-sm-50');
      // input.style.border = '2px solid #007bff';
      // input.style.borderRadius = '8px';
      // input.style.padding = '8px 12px';
      // input.style.width = '250px';
    },
    createdRow: function(row, data, dataIndex) {
      const cells = row.querySelectorAll('td');
      cells.forEach((td, index) => {
        const header = DT_TABLE.column(index).header();
        // Set data-label for responsive display
        td.setAttribute('data-label', header.textContent);
        // Copy each class from the header to the cell
        // header.classList.forEach(cls => td.classList.add(cls));
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
          row.setAttribute('title', 'Klik 2x untuk melakukan edit');

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

        // Double click to edit
        TABLE.querySelector('tbody').addEventListener('dblclick', function(e) {
          const tr = e.target.closest('tr');
          if (!tr) return;

          const row = DT_TABLE.row(tr).data();
          window.location.href = `<?= BASEURL ?>/Rekening/edit/${row.id}`;
        });

        // Right-click to show hidden link for context menu
        TABLE.querySelector('tbody').addEventListener('mousedown', function(e) {
          if (e.button === 0) return;

          const tr = e.target.closest('tr');
          if (!tr) return;

          const row = DT_TABLE.row(tr).data();

          let link = document.createElement('a');
          link.href = `<?= BASEURL ?>/Rekening/edit/${row.id}`;
          link.style.position = 'fixed';
          link.style.width = '10px';
          link.style.height = '10px';
          link.style.zIndex = '1000';
          link.style.left = `${e.clientX - 5}px`;
          link.style.top = `${e.clientY - 5}px`;

          document.body.appendChild(link);

          link.addEventListener('contextmenu', () => {
            setTimeout(() => link.remove(), 1);
          });
        });

      }
    }
  });
  const CashFlowChart = Highcharts.chart('arusRekeningChart', {
    title: false,
    chart: {
      backgroundColor: bgColor,
      plotBackgroundColor: bgColor,
    },
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
    series: []
  });
  const fetchData = async () => {
    const body = new FormData();
    body.append("startDate", dateRange[0].toLocaleDateString('sv-SE'));
    body.append("endDate", dateRange[1].toLocaleDateString('sv-SE'));
    accountsCashFlow = await fetch("<?= BASEURL ?>/Rekening/args?graph=1", {
        method: "POST",
        body,
      })
      .then(r => r.json())
      .catch((e) => showAlert(e.message, 'danger'))
  }
  const drawGraph = () => {
    document.querySelector('#text-saldo').innerHTML = 'Rp.&nbsp;' + (+accountsCashFlow.saldo).toLocaleString('id')
    let selected = DT_TABLE.rows({
      selected: true
    }).data().toArray();
    CashFlowChart.series.forEach(element => {
      element.remove()
    }); // remove 4th series (index starts at 0)
    if (selected.length === 0) CashFlowChart.addSeries({
      name: 'Cashflow',
      type: 'line',
      data: accountsCashFlow.all,
      tooltip: {
        valuePrefix: 'Rp'
      }
    })
    selected.forEach(row => {
      CashFlowChart.addSeries({
        name: row.nama,
        type: 'line',
        data: accountsCashFlow[row.id],
        tooltip: {
          valuePrefix: row.nominal_asing.length > 0 ? row.nominal_asing : 'Rp'
        }
      })
    });
  }
  DT_TABLE
    .on('select', drawGraph)
    .on('deselect', drawGraph);
  document.addEventListener('DOMContentLoaded', async () => {
    await fetchData();
    drawGraph();
  })
</script>