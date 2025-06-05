<div class="container">
  <?php $Controller->view('rekening/topbar', $data); ?>
  <table data-label="List Rekening" class="table table-responsive myTable border-bottom" id="FormatTable">
    <thead class="sticky-top">
      <tr>
        <th scope="col" class="no-sort no-search hide-small">ID</th>
        <th scope="col" class="no-sort no-search">Nama</th>
        <th scope="col" class="no-sort no-search">Saldo</th>
        <th scope="col" class="no-sort">Keterangan</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.css" rel="stylesheet" integrity="sha384-orLdZZ463q2Du2MSqmwTiuVLakuDqKN7tEJF7uICXIZ793ejMDPC5RK1ve6caXLS" crossorigin="anonymous">
<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.js" integrity="sha384-IR2ESnTJ4NOqqJEZ2amCtwDOnKALFLe94drQmiqmlvKt7F1wv+CPFe0Wfu4uNYrv" crossorigin="anonymous"></script>
<script>
  document.querySelector("#list-tab").classList.add("active");
  const TABLE = document.querySelector('#FormatTable');
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
    'fixedHeader': false,
    'processing': true,
    'serverSide': true,
    'responsive': true,
    'ajax': {
      'url': '<?= BASEURL ?>/Rekening/datatable',
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
        showAlert('danger', errorMessage)
        loadingPage.style.display = "none";
      }
    },
    "order": [
      [0, "desc"]
    ],
    "columnDefs": [{
        "targets": [4, 5, 6, 7, 8], // Index of the hidden column
        "visible": false, // Hide this column
        "orderable": false,
        searchable: true,
      },
      {
        targets: "no-search",
        searchable: false,
      },
      {
        targets: "no-sort",
        "orderable": false
      },
      {
        targets: [0],
        "className": 'd-none d-sm-table-cell align-middle'
      }
    ],
    'columns': [{
        'data': 'id',
        'title': 'ID',
        width: '5%',
      },
      {
        width: '30%',
        'title': 'Nama',
        'data': function(data, type, dataToSet) {

          harta = (data.harta) ? '<span class="badge rounded-pill bg-danger">Properti</span>' : '';
          return /* HTML */ `
            <div><strong>${data.nama}&nbsp;${harta}</strong></div>
            <div class="kuitansi small font-italic">${data.no_asli.length > 0 ? 'No./Ref. Sebenarnya : '+data.no_asli:''}</div>
            <div class="kuitansi small font-italic">Dibuat : ${data.tgl_dibuat}</div>
            <div class="kuitansi small font-italic">${(!data.aktif) ? 'Ditutup : '+data.tgl_ditutup : ''}</div>
          `
        }
      },
      {
        width: '20%',
        'title': 'Saldo',
        'data': function(data, type, dataToSet) {
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
      }, {
        'data': 'nama',
      },
      {
        'data': 'no_asli',
      },
      {
        'data': 'tgl_dibuat',
      },
      {
        'data': 'tgl_ditutup',
      },
      {
        'data': 'nominal_asing',
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
</script>