<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('.card.card-saldo')?.remove();
    document.querySelector('div.container.mb-3')?.remove();
    <?php if (isset($transaksi['id'])) : ?>
      DT_TABLE.column(0).search('<?= $transaksi['id'] ?>', true, false).draw();
      // DT_TABLE.column(0).search(<?= $transaksi['id'] ?>).draw();
    <?php endif; ?>
  });

  var refreshGraph = false;
  var dontLoad = true;
  var dateRange = [new Date(0), new Date()];
  const extraQuery = ({
    start_date,
    end_date,
    ...rest
  }) => ({
    ...rest,
    refreshGraph: false
  });
</script>
<?php $Controller->view('transaction/transaction', $data); ?>