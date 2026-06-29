<div class="container">
  <div class="container-fluid" style="max-width:32rem">
    <form id="form" method="post" action="/Databases?csrf_token=<?= $data['csrf_token'] ?>" enctype="multipart/form-data">
      <div class="form-group">
        <div class="date-range input-group mb-3 w-100">
          <input class="form-control" id="startDate" type="text" placeholder="Mulai">
          <span class="input-group-text">s/d</span>
          <input class="form-control" id="endDate" type="text" placeholder="Akhir">
        </div>
        <a href="/Databases?export=true&csrf_token=<?= $data['csrf_token'] ?>" class="btn bg-success font-weight-bold w-100" id="export">Download CSV</a>
        <input type="file" placeholder="Upload CSV File" class="form-control" accept="text/csv" name="attachment" />
        <?php InputValidator('attachment') ?>
        <small style="font-size: .5em;">Ini hanya akan mengupdate data yang ada di CSV sesuai id saja, silahkan hapus yang tidak perlu diupdate</small>
      </div>
      <div class="form-group center-input">
        <label>DB Version</label>
        <select class="form-control" id="dbVersion" placeholder="DB Version" name="dbVersion">
          <option value="" data-placeholder="true" readonly="true">DB Version</option>
          <?php foreach ($data['dbVersions'] as $version) : ?>
            <option value="<?= $version ?>"><?= $version ?></option>
          <?php endforeach; ?>
        </select>
        <?php InputValidator('dbVersion') ?>
        <hr>
        <a href="/Databases?sqllite=download&csrf_token=<?= $data['csrf_token'] ?>" class="btn font-weight-bold w-100" id="export">Download SQLite</a>
        <hr>
        <a href="/Databases?sqllite=reinitialize&csrf_token=<?= $data['csrf_token'] ?>" class="btn font-weight-bold w-100" id="export">Reinitialize SQL</a>
        <small style="font-size: .5em;">This will reset the SQL database to its initial state with current saldo as saldo awal</small>
      </div>
    </form>
  </div>
</div>
<script src="https://unpkg.com/slim-select@3/dist/slimselect.js" crossorigin="anonymous"></script>
<link href="https://unpkg.com/slim-select@3/dist/slimselect.css" rel="stylesheet" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/flatpickr" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js" crossorigin="anonymous"></script>
</link>
<script>
  const d = new Date();
  var dateRange = dateRange || [
    new Date(d.getFullYear(), d.getMonth(), 1),
    d
  ];
  delete d;
  const startInput = document.querySelector('#startDate');
  const endInput = document.querySelector('#endDate');
  flatpickr(startInput, {
    disableMobile: "true",
    plugins: [new rangePlugin({
      input: endInput
    })],
    maxDate: "today",
    dateFormat: "Y-m-d", // internal format
    onChange([start, end]) {
      startInput.value = toDateShortMonth(start);
      endInput.value = toDateShortMonth(end || start); // use start date if end is not selected
      dateRange = [start, end || start]
    }
  });
  startInput.value = toDateShortMonth(dateRange[0]);
  endInput.value = toDateShortMonth(dateRange[1]); // use start date if end is not selected
  const FORM = document.querySelector('form#form');
  FORM.querySelector("#export").addEventListener('click', (e) => {
    e.preventDefault();
    Swal.fire({
      title: `Ekspor Database`,
      text: `Proses ini akan mengekspor data transaksi ${startInput.value} s.d ${endInput.value} ke dalam file CSV`,
      icon: "warning",
      target: "dialog",
      showCancelButton: true,
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      await showAlert('Memproses....', 'warning')
      window.location.href = e.target.href + `&startDate=${formatDBDate(dateRange[0])}&endDate=${formatDBDate(dateRange[1])}`
    });
  })
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
  FORM.dbVersion.addEventListener('change', () => {
    if (!FORM.dbVersion.value) return;
    Swal.fire({
      title: `Change Database`,
      text: "Proses ini akan mengganti database yang digunakan ke versi yang dipilih, pastikan sudah dibackup sebelumnya",
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