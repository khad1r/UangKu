<style>
  #FormatTable_wrapper {
    --primary-color: var(--secondary-color)
  }
</style>
<div class="container">
  <table data-label="List Rekening" class="table table-responsive myTable border-bottom" id="FormatTable">
    <thead class="sticky-top">
      <tr>
        <th>No.</th>
        <th>Nama</th>
        <th>Status Kredensial</th>
        <th><a href="javascript:MODAL.showModal();"
            class="text-white fw-bold tooltip-bg bg-secondary fs-5 my-tooltip d-flex flex-column"
            data-tooltip="Tambah Kredensial?">
            <i class="fas fs-4 fw-bold fa-plus"></i></a></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
<dialog style="width: 30em; max-width: 100dvw;">
  <div class="dialog-header mb-2">
    <h4 class="fw-bold w-100 text-center" id="dialog-title">Tambah Kredensial</h4>
    <form method="dialog">
      <button><i class="fas fs-4 fw-bold fa-times"></i></button>
    </form>
  </div>
  <div class="dialog-body">
    <form id="form" method="post" style="--primary-color:var(--secondary-color)">
      <input type="hidden" name="challenge">
      <div class="form-group">
        <label>Nickname : Nama / Device</label>
        <input type="text" name="nickname" class="form-control mt-1 border-bottom border-primary mb-2" autocomplete="off" placeholder="Nickname : Nama / Device">
        <?php InputValidator('nickname') ?>
        <input type="submit" class="btn w-100 btn-secondary" value="Tambah">
      </div>
    </form>
  </div>
</dialog>
<link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.css" rel="stylesheet" integrity="sha384-orLdZZ463q2Du2MSqmwTiuVLakuDqKN7tEJF7uICXIZ793ejMDPC5RK1ve6caXLS" crossorigin="anonymous">
<script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.1/r-3.0.4/datatables.min.js" integrity="sha384-IR2ESnTJ4NOqqJEZ2amCtwDOnKALFLe94drQmiqmlvKt7F1wv+CPFe0Wfu4uNYrv" crossorigin="anonymous"></script>
<script>
  const MODAL = document.querySelector('dialog')
  let webAuthnArgs
  MODAL.addEventListener('click', function(event) {
    var rect = MODAL.getBoundingClientRect();
    var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
    if (!isInDialog) {
      MODAL.close();
    }
  })
  const TABLE = document.querySelector('#FormatTable');
  const DT_TABLE = new DataTable(TABLE, {
    "dom": "ftlp",
    "language": {
      "lengthMenu": "Per _MENU_ Data",
      "zeroRecords": "Tidak ada kredensial",
      "infoEmpty": "Tidak ada kredensial",
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
      'url': '<?= BASEURL ?>/Users/args?datatable=1',
      'type': 'POST',
      'data': (post_data) => {
        return post_data;
      },
      "dataSrc": (json) => {
        webAuthnArgs = json.webAuthnArgs
        webAuthnHelper.bta(webAuthnArgs)
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
    columnDefs: [{
        targets: [0, 2, 3],
        className: 'text-center align-middle',
      },
      {
        targets: [1],
        className: 'align-middle',
      },
      {
        targets: [2, 3],
        "orderable": false
      },
      {
        targets: [0, 2],
        responsivePriority: 1,
      }, {
        targets: [1, 3],
        className: 'align-middle',
        responsivePriority: 0,
      },
    ],
    "order": [
      [0, "desc"]
    ],
    'columns': [{
        'data': 'passkey_id',
        'with': "5%"
      },
      {
        'data': 'nickname',
        'render': function(item, type, data, meta) {
          created_at = (data.created_at) ? `<div class="kuitansi small font-italic">Dibuat : ${toDateShortMonth(new Date(data.created_at))}</div>` : ''
          return `<div><strong>${data.nickname}</strong></div>${created_at}`
        }
      },
      {
        'with': "10%",
        'data': 'credential_id',
        'render': (item, type, data, meta) => item ?
          '<i class="fas text-success fs-1 fa-fingerprint"></i>' : '<i class="fas fs-1 text-danger fa-times"></i>'
      },
      {
        'with': "5%",
        'data': function(data, type, dataToSet) {
          if (!data.action) return ''
          return /* HTML */ `
            <a href="javascript:deleteCreds('${data.passkey_id}','${data.nickname}')"
            class="text-danger fs-5 my-tooltip d-flex flex-column"
            data-tooltip="Hapus">
            <i class="fas fs-4 fw-bold fa-trash"></i></a>
          `
        }
      },
    ],
    initComplete: function() {
      const input = document.querySelector('#FormatTable_wrapper .dt-search input');
      input.placeholder = '🔍 Cari Disini...';
    },
  });
  const deleteCreds = (id, name) => {
    console.log(id, name);
    Swal.fire("SweetAlert2 is working!");
    Swal.fire({
      title: `Hapus <b>${name}</b>..?`,
      icon: "warning",
      html: `
          <div><b>Tindakan ini tidak bisa dikembalikan</b></div>,
          <small><i>lakukan authentikasi untuk menghapus</i></small>,
        `,
      confirmButtonColor: "#d33",
      confirmButtonText: `<i class="fa fs-1 mx-5 my-3 fa-fingerprint"></i>`
    }).then(async (result) => {
      if (!result.isConfirmed) return
      const credential = await navigator.credentials.get(webAuthnArgs);
      let credential_data = {
        id: webAuthnHelper.atb(credential.rawId),
        clientDataJSON: webAuthnHelper.atb(credential.response.clientDataJSON),
        authenticatorData: webAuthnHelper.atb(credential.response.authenticatorData),
        signature: webAuthnHelper.atb(credential.response.signature),
        userHandle: webAuthnHelper.atb(credential.response.userHandle)
      };
      const form = document.querySelector("form#form");
      form.challenge.value = JSON.stringify(credential_data);
      form.action = `<?= BASEURL ?>/Users/delete/${id}`;
      form.submit();
    });
  }
</script>