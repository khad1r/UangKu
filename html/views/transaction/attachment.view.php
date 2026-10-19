<div class="container d-flex align-items-center justify-content-center min-vh-100">
  <div class="card shadow border-0 p-4" style="max-width: 400px; width: 100%;">
    <div class="card-body text-center">
      <style>
        .close-hint {
          animation: fadeInHint 0.4s ease-in forwards;
        }

        @keyframes fadeInHint {
          from {
            opacity: 0;
            transform: translateY(-4px);
          }

          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
      </style>
      <?php if ($success) : ?>
        <div class="text-success mb-3">
          <i class="fas fa-check-circle fa-3x"></i>
        </div>
        <h4 class="card-title fw-bold mb-3">Upload Berhasil!</h4>
        <div class="alert alert-success py-2 px-3 small mb-3 text-start">
          <i class="fas fa-info-circle me-1"></i> Lampiran tersimpan. Silakan tutup halaman/tab ini.
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="closeWindow()">Tutup Halaman</button>
        <a href="<?= BASEURL ?>/Transaction" class="btn btn-outline-secondary btn-sm w-100 mt-2">Buka UangKu</a>
        <small class="text-muted d-block mt-2 close-hint" style="font-size: 0.75rem; display: none;">Jika tab tidak tertutup otomatis, silakan tutup secara manual.</small>
        <script>
          function closeWindow() {
            window.open('', '_self', '');
            window.close();
          }
          setTimeout(closeWindow, 1500);
          setTimeout(() => {
            document.querySelectorAll('.close-hint').forEach(el => el.style.display = 'block');
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
        <button type="button" class="btn btn-danger w-100" onclick="closeWindow()">Tutup Halaman</button>
        <a href="<?= BASEURL ?>/Transaction" class="btn btn-outline-secondary btn-sm w-100 mt-2">Buka UangKu</a>
        <small class="text-muted d-block mt-2 close-hint" style="font-size: 0.75rem; display: none;">Jika tab tidak tertutup otomatis, silakan tutup secara manual.</small>
        <script>
          function closeWindow() {
            window.open('', '_self', '');
            window.close();
          }
          setTimeout(closeWindow, 3000);
          setTimeout(() => {
            document.querySelectorAll('.close-hint').forEach(el => el.style.display = 'block');
          }, 1500);
        </script>
      <?php else : ?>
        <div id="upload-section">
          <div class="text-primary mb-3">
            <i class="fas fa-file-upload fa-3x"></i>
          </div>
          <h4 class="card-title fw-bold mb-4">Unggah Lampiran</h4>
          <p class="text-secondary small mb-4">Transaksi #<?= $formattedId ?> &bull; <?= htmlspecialchars($trans['barang'] ?? '') ?></p>

          <div id="upload-alert" class="alert alert-danger py-2 px-3 small mb-3 text-start" style="display:none;"></div>

          <form id="upload-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="upload" value="1">
            <input type="file" class="form-control d-none" accept="image/*, application/pdf" name="attachment[]" id="attachment" multiple />
            <?php InputValidator('attachment') ?>
            <label for="attachment" class="btn btn-primary w-100" id="select-btn">
              <i class="fas fa-images me-1"></i> Pilih & Unggah File
            </label>
          </form>
        </div>

        <div id="success-section" style="display:none;">
          <div class="text-success mb-3">
            <i class="fas fa-check-circle fa-3x"></i>
          </div>
          <h4 class="card-title fw-bold mb-3">Upload Berhasil!</h4>
          <div class="alert alert-success py-2 px-3 small mb-3 text-start">
            <i class="fas fa-info-circle me-1"></i> Lampiran tersimpan. Silakan tutup tab ini.
          </div>
          <button type="button" class="btn btn-primary w-100" onclick="closeWindow()">Tutup Halaman</button>
          <a href="<?= BASEURL ?>/Transaction" class="btn btn-outline-secondary btn-sm w-100 mt-2">Buka UangKu</a>
          <small class="text-muted d-block mt-2 close-hint" style="font-size: 0.75rem; display: none;">Jika tab tidak tertutup otomatis, silakan tutup secara manual.</small>
        </div>

        <script>
          function closeWindow() {
            window.open('', '_self', '');
            window.close();
          }

          const form = document.querySelector('#upload-form');
          const fileInput = document.querySelector('#attachment');
          const selectBtn = document.querySelector('#select-btn');
          const uploadAlert = document.querySelector('#upload-alert');
          const uploadSection = document.querySelector('#upload-section');
          const successSection = document.querySelector('#success-section');

          fileInput.addEventListener('change', async () => {
            if (fileInput.files.length <= 0) return;
            const count = fileInput.files.length;
            const originalBtnHtml = selectBtn.innerHTML;

            selectBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah ${count} file...`;
            selectBtn.classList.add('disabled');
            if (uploadAlert) uploadAlert.style.display = 'none';

            try {
              const formData = new FormData(form);
              formData.append('ajax', '1');

              const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
              });

              const result = await response.json();
              if (response.ok && result.success) {
                // Upload sukses tanpa reload browser: history.length tetap 1!
                if (uploadSection) uploadSection.style.display = 'none';
                if (successSection) successSection.style.display = 'block';

                setTimeout(() => {
                  closeWindow();
                }, 1500);

                setTimeout(() => {
                  if (successSection) {
                    successSection.querySelectorAll('.close-hint').forEach(el => el.style.display = 'block');
                  }
                }, 1500);
              } else {
                throw new Error(result.error || 'Gagal mengunggah file.');
              }
            } catch (err) {
              selectBtn.innerHTML = originalBtnHtml;
              selectBtn.classList.remove('disabled');
              fileInput.value = ''; // Reset input agar bisa pilih ulang
              if (uploadAlert) {
                uploadAlert.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> ${err.message || 'Terjadi kesalahan saat upload.'}`;
                uploadAlert.style.display = 'block';
              }
            }
          });
        </script>
      <?php endif; ?>
    </div>
  </div>
</div>