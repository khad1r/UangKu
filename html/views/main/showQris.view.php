<div class="header other mb-4">
  <?php $this->view('components/navbar', $data); ?>
</div>
<div class="qris-container mx-3">
  <!-- <img class="img-fluid border border-secondary qris" src="<?= $data['qris_url'] ?>" loading="lazy" alt="" /> -->
  <img class="img-fluid qris placeholder" src="<?= BASEURL . '/assets/img/qrisbg-small.jpg' ?>" loading="lazy"
    alt="placeholder" />
</div>
<!-- <script src="<?= BASEURL ?>/assets/js/notification-helper.js"></script> -->
<script>
  const img = new Image()
  img.crossOrigin = "anonymous"
  img.src = "<?= $data['qris_url'] ?>"
  img.classList.add('img-fluid', 'qris', 'placeholder');
  img.addEventListener('load', () => {
    document.querySelector("img.qris.placeholder").remove('placeholder');
    document.querySelector(".qris-container").append(img);
    img.classList.remove('placeholder');
  })
  // Handle errors (e.g., failed status code)
  img.addEventListener('error', () => showAlert('info', `<small>Gagal menunjukan Qris</small>`));
</script>