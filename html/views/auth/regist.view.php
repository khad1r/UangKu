<style>
  main {
    --main-bg: var(--primary-color) !important;
  }
</style>
<div class="wrapper">
  <div class="header">
  </div>
  <div class="main-logo">
  </div>
  <div class="body px-3">
    <div id="form" name="regist">
      <div class="indicator-body mt-1 mb-5">
        <i class="fa-solid fa-caret-up"></i>
      </div>
      <form method="post">
        <div class="form-group px-4">
          <label>User / Device</label>
          <input type="text" class="form-control" placeholder="User / Device" autocorrect="off" autocapitalize="none"
            name="user" value="" required="required">
          <?php InputValidator('user') ?>
        </div>
        <div class="form-group px-4">
          <label>Passkey</label>
          <input type="password" class="form-control" placeholder="Passkey" autocorrect="off" autocapitalize="none"
            name="passkey" value="-" required="required">
          <?php InputValidator('passkey') ?>
        </div>
        <hr>
        <div class="indicator-body">
          <div class="spinner-border mb-3" style="display:none;" role="status">
          </div>
        </div>
        <input type="submit" class="btn w-100 text-white" value="Registrasi" name="regist">
        <div class="note">
          <p>Apakah Anda sudah memiliki QRIS Bank Gresik?</p>
          <a href="#" id="openModal">Ya, saya sudah</a> | <a href="#">Belum, saya
            belum</a>
        </div>
      </form>
    </div>
  </div>
</div>
<dialog>
  <div class="dialog-header">
    <p>Panduan Login</p>
    <form method="dialog">
      <button>X</button>
    </form>
  </div>
  <div class="dialog-body">
    <p>Secara <b>Default</b>, gunakan <b>rekening Qris</b> anda tanpa tanda titik (.)
      sebagai
      username dan <b>NMID</b> sebagai password.</p>
    <p>Temukan NMID pada Qris Bank Gresik Anda (<b class="text-primary">IDXXXXXXXXXX</b>).</p>
    <img src="<?= BASEURL ?>/assets/img/qris_contoh.png" class="img-fluid qris" loading="lazy" alt="cara mendapatkan NMID" />
  </div>
</dialog>
<script>
  const modal = document.querySelector('dialog')
  document.querySelector('#openModal').addEventListener('click', (e) => {
    e.preventDefault()
    modal.showModal()
  })
  modal.addEventListener('click', function(event) {
    var rect = modal.getBoundingClientRect();
    var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
    if (!isInDialog) {
      modal.close();
    }
  })
  document.querySelector('form').addEventListener('input', () => {
    sessionStorage.setItem('isInput', true)
  })
  document.querySelector('form').addEventListener('submit', () => {
    document.querySelector(".spinner-border.mb-3").style.display = "block"
  })

  function scrollToForm() {
    window.scrollTo({
      top: document.body.scrollHeight,
      behavior: 'smooth'
    });
  }
  document.querySelector('.indicator-body').addEventListener('click', scrollToForm)
  document.addEventListener('DOMContentLoaded', async () => {
    if (sessionStorage.getItem('isInput')) scrollToForm()
    removeCache()
  })
</script>