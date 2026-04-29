<div class="container">
  <div class="container-fluid" style="max-width:32rem">
    <form id="form" method="post" action="/Transaction/database" enctype="multipart/form-data">
      <div class="form-group">
        <a href="/Transaction/database?export=true" class="btn bg-success font-weight-bold w-100" id="export">Download CSV</a>
        <input type="file" placeholder="Upload CSV File" class="form-control" accept="text/csv" name="attachment" />
        <?php InputValidator('attachment') ?>
        <small style="font-size: .5em;">Ini hanya akan mengupdate data yang ada di CSV sesuai id saja, silahkan hapus yang tidak perlu diupdate</small>
      </div>
      <div class="form-group center-input">
        <label>DB Version</label>
        <select class="form-control" id="rekening_masuk" placeholder="Rekening Masuk" name="rekening_masuk">
          <option value="" data-placeholder="true" readonly="true">DB Version</option>
        </select>
        <?php InputValidator('rekening_masuk') ?>
        <div class="d-flex justify-content-between font-weight-bold">
          <span class="info" id="rekening_masuk_akhir"></span>
        </div>
        <hr>
        <a href="Transaction/csv?export=true" class="btn font-weight-bold w-100" id="export">Download SQLite</a>
        <hr>
        <a href="Transaction/csv?export=true" class="btn font-weight-bold w-100" id="export">Reinitialize SQL</a>
        <small style="font-size: .5em;">This will reset the SQL database to its initial state with current saldo as saldo awal</small>
      </div>
    </form>
  </div>
</div>
<script src="https://unpkg.com/slim-select@latest/dist/slimselect.js" crossorigin="anonymous"></script>
<link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet" crossorigin="anonymous">
</link>
<script>
  const FORM = document.querySelector('form#form');
  FORM.attachment.addEventListener('change', () => {
    if (!FORM.attachment.value) return;
    Swal.fire({
      title: `Update Database`,
      text: "Proses ini akan mengupdate data berdasarkan file CSV yang diunggah, pastikan format file benar dan sudah dibackup sebelumnya",
      icon: "warning",
      target: "dialog",
      showCancelButton: true,
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      await showAlert('Memproses....', 'warning')
      FORM.submit()
    });

  })
</script>