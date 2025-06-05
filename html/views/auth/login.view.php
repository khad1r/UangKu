<style>
  main {
    --bg-color: var(--primary-color) !important;
  }

  .body {
    --body-mobile-margin-top: 0;
    --body-top-radius: clamp(0%, 10vw, 30%);
    --body-min-height: 20dvh;
    /* --body-min-height: calc(100dvh - var(--main-logo-height)); */
    padding-bottom: 10px;
    /* padding-top: auto; */
    display: flex;
    flex-direction: row;
    align-items: flex-end;
    justify-content: center;
  }

  .wrapper .main-logo {
    width: 100%;
    height: 60dvh;
  }
</style>
<div class="wrapper">
  <div class="header">
  </div>
  <div class="main-logo">
    <img src="<?= BASEURL ?>\assets\img\logo_512.png" class="">
  </div>
  <div class="body px-3">
    <div id="form">
      <form method="post" name="login">
        <div class="indicator-body mt-1 mb-5">
          <i class="fa-solid fa-caret-up"></i>
        </div>
        <input type="hidden" name="login" />
        <div class="indicator-body">
          <div class="spinner-border mb-3" style="display:none;" role="status">
          </div>
        </div>
        <hr>
        <button role="button" class="btn w-100 text-white" id="login-btn">Login</button>
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
  const DATA = <?= json_encode($data['webAuthnArgs']) ?>;
  webAuthnHelper.bta(DATA)
  const Authenticate = async () => {
    const credential = await navigator.credentials.get(DATA);
    let credential_data = {
      id: webAuthnHelper.atb(credential.rawId),
      clientDataJSON: webAuthnHelper.atb(credential.response.clientDataJSON),
      authenticatorData: webAuthnHelper.atb(credential.response.authenticatorData),
      signature: webAuthnHelper.atb(credential.response.signature),
      userHandle: webAuthnHelper.atb(credential.response.userHandle)
    };
    const form = document.querySelector("form[name='login']");
    form.login.value = JSON.stringify(credential_data);
    form.submit();
  }
  document.getElementById('login-btn').addEventListener('click', async (event) => {
    event.preventDefault();
    Authenticate()
  })
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
</script>