<style>
  main {
    --main-bg: var(--primary-color) !important;
    height: 100dvh;
    display: grid;
    place-content: center;
  }
</style>
<div class="d-grid place-content-center mx-md-0 mx-4">
  <h1 class="text-warning text-center fa-solid fa-ban"></h1>
  <h1 class="text-white text-center fw-bold">Oops!!</h1>
  <h3 class="text-white text-center fw-bold">Halaman&nbsp;Tidak&nbsp;ada</h3>
  <h6 class="text-white">Sepertinya&nbsp;kamu&nbsp;berjalan&nbsp;kearah&nbsp;yang&nbsp;salah</h6>
  <hr>
  <a class="text-primary btn btn-warning btn-block fw-bold" href="<?= BASEURL . '/' ?>">
    <i>Yuk, kembali ke jalan yang benar.</i>
  </a>
</div>