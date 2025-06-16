<!DOCTYPE html>
<html lang="id" translate="no">
<?php $Controller->view('templates/header', $data); ?>

<body>
  <div class="loading-Page">
    <div class="spinner-border" role="status">
    </div>
  </div>
  <main>
    <?php $Controller->view('components/alert', $data); ?>
    <?php if (isset($data['top-left-view'])) $Controller->view($data['top-left-view'], $data) ?>
    <!-- <div class="container">
      <div class="row">
        <div class="col-lg"></div>
        <div class="col-lg-8 p-0"></div>
        <div class="col-lg"></div>
      </div>
    </div> -->
    <?php $Controller->view($data['view'], $data); ?>
    <?php if (isset($data['right-bottom-view'])) $Controller->view($data['right-bottom-view'], $data) ?>
  </main>
  <?php $Controller->view('templates/footer', $data); ?>
</body>

</html>
<?php if (isset($_SESSION['InputError'])) unset($_SESSION['InputError']) ?>