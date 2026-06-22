<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('.card.card-saldo')?.remove();
    document.querySelector('div.container.mb-3')?.remove();
    <?php if (isset($transaksi['id'])) : ?>
      // Just trigger the draw. The extraQuery below handles sending the ID to the server.
      DT_TABLE.draw();
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
    <?php if (isset($transaksi['id'])) : ?>,
      search_relasi_id: '<?= $transaksi['id'] ?>' // Inject the custom parameter here
    <?php endif; ?>
  });
</script>
<?php $Controller->view('transaction/transaction', $data); ?>