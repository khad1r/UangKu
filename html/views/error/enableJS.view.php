<!DOCTYPE html>
<html lang="id" translate="no">
<?php $this->view('templates/header', $data); ?>

<body>
  <main>
    <?php $this->view('components/alert', $data); ?>
    <div class="container">
      <div class="row">
        <div class="col-lg"></div>
        <div class="col-lg-8 p-0">
          <style>
            main {
              --main-bg: var(--primary-color) !important;
              height: 100dvh;
              display: grid;
              place-content: center;
            }
          </style>
          <div class="d-grid place-content-center mx-md-0 mx-4">
            <h1 class="text-danger text-center fa-solid fa-warning"></h1>
            <h1 class="text-white text-center fw-bold">Oops!!</h1>
            <h3 class="text-white text-center fw-bold">JavaScript Anda Dimatikan</h3>
            <h6 class="text-white text-center">Untuk pengalaman terbaik, harap aktifkan JavaScript di browser Anda.</h6>
            <hr>
            <a class="text-white btn btn-danger btn-block fw-bold" href="<?= BASEURL . '/' ?>">
              <i>Refresh</i>
            </a>
          </div>
        </div>
        <div class="col-lg"></div>
      </div>
    </div>
  </main>
</body>

</html>