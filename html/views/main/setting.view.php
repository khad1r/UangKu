<style>
  main {
    --main-bg: transparent !important;
  }
</style>
<div class="header other mb-3">
  <?php $this->view('components/navbar', $data); ?>
</div>
<div class="px-4 mt-2 pb-5">
  <div class="pb-3 pt-2 mb-2 border-bottom border-2 border-primary form-check form-switch d-flex justify-content-between ps-0" style="font-size: .75em;font-weight: 600;">
    <label class="form-check-label" for="notif">Nyalakan Notifikasi</label>
    <input class="form-check-input" type="checkbox" id="notif">
  </div>
  <div class="border-bottom pt-2 pb-3 border-2 border-primary" name="login">
    <details <?= $data['expandInput'] ? 'open' : '' ?>>
      <summary class="">Keamanan</summary>
      <div id="form" class="mt-4">
        <form id="update-form" method="post">
          <div class="form-group px-3">
            <label>Username</label>
            <input type="text" class="form-control" placeholder="Username" autocorrect="off" autocapitalize="none"
              name="username" value="<?= $data['username'] ?>" required="required"
              pattern="^(?=.{5,}$)(?![_.@-])(?!.*[_.@-]{2})[a-zA-Z0-9._@-]+(?<![_.])$">
            <?php App::InputValidator('username') ?>
          </div>
          <div class="form-group px-3">
            <label>Password Sebelumnya</label>
            <input type="password" class="form-control" placeholder="Password Sebelumnya" autocorrect="off"
              autocapitalize="none" name="prevPassword" required="required">
            <?php App::InputValidator('prevPassword') ?>
          </div>
          <div class="form-group px-3">
            <label>Password</label>
            <input type="password" class="form-control" placeholder="Password" autocorrect="off" autocapitalize="none"
              name="password" required="required" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d\W_]{8,}$">
            <?php App::InputValidator('password') ?>
          </div>
          <div class="form-group px-3">
            <label>Konfirmasi Password</label>
            <input type="password" class="form-control" placeholder="Konfirmasi Password" autocorrect="off"
              autocapitalize="none" name="password-verify" required="required">
            <?php App::InputValidator('password-verify') ?>
          </div>
          <hr>
          <input type="submit" class="btn w-100 text-white" value="Update Keamanan" name="update">
        </form>
      </div>
    </details>
  </div>
  <a onclick="logout(event)" style='font-size: 1em; font-weight: 600;'
    class="my-4 btn btn-warning w-100 text-primary d-flex justify-content-between align-items-center">
    Logout
    <i class="fa-solid fa-right-from-bracket"></i>
  </a>
</div>
<!-- <script src="<?= BASEURL ?>/assets/js/notification-helper.js"></script> -->
<script>
  let notificationToggle = document.querySelector('#notif');
  notificationToggle.checked = getNotificationState();
  notificationToggle.addEventListener('change', async e => {
    try {
      if (e.target.checked) showAlert('primary', '<small>Sedang Memproses Permintaan Kamu....</small>');
      if ((e.target.checked) ? await enableNotification() : await disableNotification())
        showAlert('success', 'Operasi Berhasil');
      else
        e.target.checked = !e.target.checked;
    } catch (error) {
      e.target.checked = !e.target.checked;
      showAlert('danger', `Operasi Gagal.<br><small>${error.message}</small>`);
    }
  });

  document.querySelector("input[name='password-verify']").addEventListener('input', (e) => {
    if (e.target.value !== document.querySelector("input[name='password']").value) {
      e.target.setCustomValidity('Password tidak sama.');
      e.target.classList.add('notice');
    } else {
      e.target.setCustomValidity('');
      e.target.classList.remove('notice');
    }
  });

  document.querySelector("input[name='username']").addEventListener('input', (e) => {
    const pattern = /^(?=.{5,}$)(?![_.@-])(?!.*[_.@-]{2})[a-zA-Z0-9._@-]+(?<![_.])$/;
    if (!pattern.test(e.target.value)) {
      e.target.setCustomValidity('Username harus minimal 5 huruf \n terdiri dari huruf atau angka.');
      e.target.classList.add('notice');
    } else {
      e.target.setCustomValidity('');
      e.target.classList.remove('notice');
    }
  });

  document.querySelector("input[name='password']").addEventListener('input', (e) => {
    const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d\W_]{8,}$/;
    if (!pattern.test(e.target.value)) {
      e.target.classList.add('notice');
      e.target.setCustomValidity(
        'Password harus minimal 8 huruf \n terdiri dari minimal 1 huruf dan 1 angka.');
    } else {
      e.target.setCustomValidity('');
      e.target.classList.remove('notice');
    }
  });

  async function logout(event) {
    event.preventDefault();
    sessionStorage.clear();
    await removeCache()
    window.location.href = "<?= BASEURL ?>/Auth/Logout";
  }
</script>