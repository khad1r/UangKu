<style>
  main {
    --bg-color: var(--primary-color) !important;
  }

  .wrapper {
    .main-logo {
      width: 100%;
      min-height: 60dvh;
      /* height: 90dvh; */
      /* position: a; */
      display: flex;
      justify-content: center;
      align-items: center;

      img {
        position: sticky;
        top: 2vh;
        padding: 1em;
        height: 100%;
        pointer-events: none;
        width: 25dvw;

        @media (orientation: portrait) {
          width: 75dvw;
        }
      }
    }
  }

  .body {
    position: sticky;
    bottom: 0;
    --body-mobile-margin-top: 0;
    --body-top-radius: 8em;
    --body-min-height: 40dvh;
    min-height: var(--body-min-height) !important;
    padding-top: 1.25rem;
    width: 40%;
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
      width: 90%;
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
  <div class="main-logo">
    <img src="<?= BASEURL ?>/assets/img/logo.png" class="">
  </div>
  <div class="body px-3 fixed-bottom">
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
  document.querySelector('form').addEventListener('submit', () => {
    document.querySelector(".spinner-border.mb-3").style.display = "block"
  })
</script>