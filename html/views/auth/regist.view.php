<form method="POST">
  <input type="hidden" name="regist" />
</form>
<script src="<?= BASEURL ?>/assets/js/script.js"></script>
<script>
  (async _ => {
    const options = <?= json_encode($data['webAuthnArgs']) ?>;
    webAuthnHelper.bta(options)
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