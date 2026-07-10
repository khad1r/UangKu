<div class="container d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow border-0 p-4" style="max-width: 400px; width: 100%;">
    <div class="card-body text-center">
      <?php if ($success) : ?>
        <div class="text-success mb-3">
          <i class="fas fa-check-circle fa-3x"></i>
        </div>
        <h4 class="card-title fw-bold mb-3">Upload Berhasil!</h4>
        <div class="alert alert-success py-2 px-3 small mb-3 text-start">
          <i class="fas fa-info-circle me-1"></i> Silakan tutup halaman/tab ini.
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="window.close()">Tutup Halaman</button>
        <script>
          setTimeout(() => {
            window.close()
          }, 1500);
        </script>
      <?php elseif (!empty($error)) : ?>
        <div class="text-danger mb-3">
          <i class="fas fa-exclamation-circle fa-3x"></i>
        </div>
        <h4 class="card-title fw-bold mb-3">Gagal</h4>
        <div class="alert alert-danger py-2 px-3 small mb-3 text-start">
          <i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
        <button type="button" class="btn btn-danger w-100" onclick="window.close()">Tutup Halaman</button>
        <script>
          setTimeout(() => {
            window.close()
          }, 3000);
        </script>
      <?php else : ?>
        <div class="text-primary mb-3">
          <i class="fas fa-file-upload fa-3x"></i>
        </div>
        <h4 class="card-title fw-bold mb-4">Unggah Lampiran</h4>
        <p class="text-secondary small mb-4">Transaksi #<?= $formattedId ?> &bull; <?= htmlspecialchars($trans['barang']) ?></p>

        <form id="upload-form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="upload" value="1">
          <input type="file" placeholder="Attachment" class="form-control d-none" accept="image/*, application/pdf" name="attachment" id="attachment" />
          <?php InputValidator('attachment') ?>
          <label for="attachment" class="btn btn-primary w-100" id="select-btn">Pilih & Unggah File</label>
        </form>
        <script>
          const form = document.querySelector('form');
          form.attachment.addEventListener('change', () => {
            if (form.attachment.files.length <= 0) return
            loadingPage.style.display = "grid";
            form.submit();
          });
        </script>
      <?php endif; ?>
    </div>
  </div>
</div>