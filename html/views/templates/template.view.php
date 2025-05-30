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
    <div class="container">
      <div class="row">
        <div class="col-lg"></div>
        <div class="col-lg-8 p-0"><?php $Controller->view($data['view'], $data); ?></div>
        <div class="col-lg"></div>
      </div>
    </div>
  </main>
  <?php $Controller->view('templates/footer', $data); ?>
</body>

</html>