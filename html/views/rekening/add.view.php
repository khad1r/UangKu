<div class="container">
  <?php $Controller->view('rekening/topbar', $data); ?>
  <div class="container-fluid" style="max-width:32rem">
    <form id="form" method="post">
      <div class="form-group">
        <label>Nama Rekening</label>
        <input type="text" id="nama_rekening" placeholder="Nama Rekening" name="nama_rekening" class="form-control" required>
        <?php InputValidator('nama_rekening') ?>
      </div>
      <div class="form-group">
        <label>Ref Rekening Sebenarnya</label>
        <input type="text" id="no_asli" placeholder="Ref Rekening Sebenarnya" name="no_asli" class="form-control">
        <?php InputValidator('no_asli') ?>
        <span class="info"><small>Referensi / No. Rekening yang sebenarnya</small></span>
      </div>
      <div class="p-3 mb-4 border-bottom border-top border-2 border-primary form-check form-switch d-flex justify-content-between ps-0" style="font-size: .75em;font-weight: 600;">
        <label class="form-check-label text-primary w-100" for="harta">Rekening Harta</label>
        <input class="form-check-input" type="checkbox" id="harta" name="harta">
        <?php InputValidator('harta') ?>
      </div>
      <div class="form-group">
        <label>Nominal Asing</label>
        <input type="text" id="nominal_asing" placeholder="Nominal Asing" name="nominal_asing" class="form-control">
        <?php InputValidator('nominal_asing') ?>
        <span class="info"><small>Jika nilai/saldo Rekening tidak menggunakan Rupiah(Rp.)</small></span>
      </div>
      <div class="form-group">
        <label>Tanggal Dibuat</label>
        <input type="text" id="tgl_dibuat" placeholder="Tanggal Dibuat" name="tgl_dibuat" class="form-control date-format" required>
        <?php InputValidator('tgl_dibuat') ?>
      </div>
      <div class="p-3 mb-4 border-bottom border-top border-2 border-primary form-check form-switch d-flex justify-content-between ps-0" style="font-size: .75em;font-weight: 600;">
        <label class="form-check-label text-primary w-100" for="aktif">Rekening Aktif</label>
        <input class="form-check-input" type="checkbox" id="aktif" name="aktif" checked>
        <?php InputValidator('aktif') ?>
      </div>
      <div class="form-group">
        <label>Tanggal Ditutup</label>
        <input type="text" id="tgl_ditutup" placeholder="Tanggal Ditutup" name="tgl_ditutup" class="form-control date-format" disabled=true>
        <?php InputValidator('tgl_ditutup') ?>
      </div>
      <div class="form-group">
        <label>Keterangan</label>
        <textarea class="form-control" name="keterangan" placeholder="Keterangan" id="keterangan" rows="3"></textarea>
      </div>
      <div class="form-group" id="sumary">
        <input type="submit" class="btn btn-primary font-weight-bold w-100" value="Tambah Rekening" name="record">
      </div>
    </form>
  </div>
</div>
<script>
  const FORM = document.querySelector('form#form');
  // const submit = () => {
  //   JSAlert.confirm("Input Tidak Akan Bisa Diubah Kembali <br>Ingin Melanjutkan?").then(function(result) {
  //     if (!result) return;
  //     document.querySelector('#form-transaksi').submit();
  //   });
  // }
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelector("#item-tab")?.classList.add("active");
  });
  FORM.aktif.addEventListener('change', e => {
    FORM.tgl_ditutup.disabled = e.target.checked;
    FORM.tgl_ditutup.required = !e.target.checked;
  });
  FORM.addEventListener('submit', async e => {
    await e.preventDefault()
    const {
      tgl_dibuat,
      tgl_ditutup,
    } = FORM
    tgl_dibuat.value = tgl_dibuat.dataset.raw
    tgl_ditutup.value = tgl_ditutup.dataset.raw
    e.currentTarget.submit()
  })
</script>