<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('.card.card-saldo a')?.remove();
  });
  const extraQuery = (d) => ({
    ...d,
    id_rekening: <?= $data['rekening']['id'] ?>
  });
</script>
<?php $Controller->view('transaction/transaction', $data); ?>