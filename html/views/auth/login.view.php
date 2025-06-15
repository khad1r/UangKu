<style>
  main {
    --bg-color: var(--primary-color) !important;
  }

  .wrapper .main-logo {
    width: 100%;
    height: 60dvh;
  }

  .body {
    --body-mobile-margin-top: 0;
    --body-top-radius: 8em;
    --body-min-height: 40dvh;
    min-height: var(--body-min-height) !important;
    padding-top: 1.25rem;
    width: 50em;
    margin-inline: auto;
    padding-bottom: 10px;
    border-radius: var(--body-top-radius) var(--body-top-radius) 0 0;
    box-shadow: color-mix(in srgb, var(--black-color) 25%, transparent) 0px -12.5px 30px;
    position: relative;
    background-color: var(--white-color);
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;

    .indicator-body {
      display: grid;
      place-content: center;

      .spinner-border {
        color: var(--primary-color);
      }
    }

    @media (orientation: portrait) {
      & {
        /* min-width: none; */
        width: 90%;
      }
    }
  }

  form {
    font-size: 1.2em;

    /* position: sticky;
      bottom: 0 !important; */

    .btn {
      color: var(--primary-color);
      font-size: 7em;

      &:hover,
      &:focus,
      &:active {
        outline: none;
        border: none;
        color: var(--primary-color-alt);
      }
    }
  }
</style>
<div class="wrapper">
  <div class="header">
  </div>
  <div class="main-logo">
    <img src="<?= BASEURL ?>/assets/img/Logo.png" class="">
  </div>
  <div class="body px-3">
    <form method="post" name="login">
      <input type="hidden" name="login" />
      <div class="indicator-body text-primary mt-1 mb-5">
        <i class="fa-solid fa-caret-up"></i>
      </div>
      <div class="indicator-body">
        <div class="spinner-border mb-3" style="display:none;" role="status">
        </div>
      </div>
      <button role="button" class="btn" id="login-btn"><i class="fas fa-fingerprint"></i></button>
    </form>
  </div>
</div>
<script>
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
  modal.addEventListener('click', function(event) {
    var rect = modal.getBoundingClientRect();
    var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
    if (!isInDialog) {
      modal.close();
    }
  })
  document.querySelector('form').addEventListener('submit', () => {
    document.querySelector(".spinner-border.mb-3").style.display = "block"
  })
</script>