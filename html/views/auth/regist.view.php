<style>
  main {
    --bg-color: var(--primary-color) !important;
    height: 100dvh;
    display: grid;
    place-content: center;
  }

  h1 {
    font-size: 3em;
  }

  .fa-fingerprint {
    font-size: 10em;
    margin-bottom: .2em;
  }

  #hide {
    display: none;
    width: 100%;
  }
</style>
<div class="d-grid place-content-center mx-md-0 mx-4">
  <h1 class="text-white text-center fas fa-fingerprint"></h1>
  <div id="hide">
    <h5 class="text-white text-center">Kamu seharusnya bisa tidak melihat ini</h5>
    <h6 class="text-white text-center"><i>Coba Refresh atau Kembali.</i></h6>
    <hr>
    <a class="text-primary btn bg-white w-100 fw-bold" href="<?= BASEURL  ?>">
      <i>Kembali</i>
    </a>
  </div>
</div>
<form method="POST">
  <input type="hidden" name="regist" />
</form>
<script>
  (async _ => {
    setTimeout(() => {
      document.querySelector('#hide').style.display = 'block'
    }, 5000)
    const options = <?= json_encode($data['webAuthnArgs']) ?>;
    await webAuthnHelper.bta(options)
    console.log(options);
    const credential = await navigator.credentials.create(options);

    const data = {
      id: credential.id,
      rawId: webAuthnHelper.atb(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: webAuthnHelper.atb(credential.response.clientDataJSON),
        attestationObject: webAuthnHelper.atb(credential.response.attestationObject)
      }
    };
    const form = document.querySelector('form');
    form.regist.value = JSON.stringify(data);
    form.submit();
  })()
</script>