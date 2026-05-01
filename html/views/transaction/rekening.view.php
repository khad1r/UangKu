<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('.card.card-saldo a')?.remove();
    if (<?= $data['rekening']['harta'] ?> == 1) {
      document.querySelector('div.container.mb-3')?.remove();
    }
  });
  if (<?= $data['rekening']['harta'] ?> == 1) {
    var dateRange = [new Date(0), new Date()];
    // var refreshGraph = false;
  }
  const extraQuery = (d) => ({
    ...d,
    id_rekening: <?= $data['rekening']['id'] ?>,
    rekening_is_harta: <?= $data['rekening']['harta'] ?>
  });
</script>
<?php $Controller->view('transaction/transaction', $data); ?>