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
</style>
<div class="d-grid place-content-center mx-md-0 mx-4">
  <h1 class="text-warning text-center fa-solid fa-tower-broadcast"></h1>
  <h1 class="text-white text-center fw-bold">Oops!</h1>
  <h3 class="text-white text-center fw-bold">Ada Gangguan</h3>
  <h6 class="text-white text-center">Sepertinya internet sedang tidak baik-baik saja...</h6>
  <h5 class="text-white text-center"><i>Yuk, coba cek koneksi dulu.</i></h5>
  <hr>
  <a class="text-primary btn btn-warning btn-block fw-bold" href="<?= BASEURL . '/Main' ?>">
    <i>Kembali</i>
  </a>
</div>