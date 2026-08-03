<div class="container pb-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold m-0">Daftar Wishlist</h5>
    <button type="button" class="btn btn-primary rounded-circle" style="width:3em;height:3em;" onclick="openAdd()">
      <i class="fas fa-plus"></i>
    </button>
  </div>
  <?php if (empty($data['items'])): ?>
    <div class="text-center text-secondary py-5">
      <i class="fas fa-star fa-2x mb-2"></i>
      <div>Belum ada barang di wishlist.</div>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column gap-2">
      <?php foreach ($data['items'] as $item): ?>
        <div class="card p-3 wishlist-card" style="cursor:pointer;"
          data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES) ?>"
          data-nama="<?= htmlspecialchars($item['nama'], ENT_QUOTES) ?>"
          data-harga_estimasi="<?= htmlspecialchars($item['harga_estimasi'] ?? '', ENT_QUOTES) ?>"
          data-link="<?= htmlspecialchars($item['link'] ?? '', ENT_QUOTES) ?>"
          data-catatan="<?= htmlspecialchars($item['catatan'] ?? '', ENT_QUOTES) ?>">
          <div class="d-flex justify-content-between align-items-start">
            <strong><?= htmlspecialchars($item['nama']) ?></strong>
            <?php if (!empty($item['harga_estimasi'])): ?>
              <span class="text-primary fw-bold">Rp&nbsp;<?= number_format($item['harga_estimasi'], 0, ',', '.') ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($item['catatan'])): ?>
            <div class="small text-secondary truncate-text mt-1"><?= htmlspecialchars($item['catatan']) ?></div>
          <?php endif; ?>
          <?php if (!empty($item['link'])): ?>
            <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()" class="small"><i class="fas fa-link"></i>&nbsp;Lihat Barang</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<dialog id="wishlist-dialog">
  <div class="dialog-header mb-2">
    <h4 class="fw-bold w-100 text-center" id="dialog-title">Tambah Wishlist</h4>
    <form method="dialog">
      <button><i class="fas fs-4 fw-bold fa-times"></i></button>
    </form>
  </div>
  <div class="dialog-body">
    <form id="form" method="post">
      <input type="hidden" name="id">
      <div class="form-group">
        <label>Nama Barang</label>
        <input type="text" name="nama" class="form-control mt-1 border-bottom border-primary mb-2" autocomplete="off" placeholder="Nama Barang" required>
        <?php InputValidator('nama') ?>
      </div>
      <div class="form-group">
        <label>Estimasi Harga</label>
        <input type="number" name="harga_estimasi" class="form-control mt-1 border-bottom border-primary mb-2" autocomplete="off" placeholder="Estimasi Harga (Rp)">
        <?php InputValidator('harga_estimasi') ?>
      </div>
      <div class="form-group">
        <label>Link</label>
        <input type="url" name="link" class="form-control mt-1 border-bottom border-primary mb-2" autocomplete="off" placeholder="Link Produk (opsional)">
        <?php InputValidator('link') ?>
      </div>
      <div class="form-group">
        <label>Catatan</label>
        <textarea name="catatan" class="form-control mt-1 border-bottom border-primary mb-2" rows="4" placeholder="Catatan, alasan, pertimbangan..."></textarea>
        <?php InputValidator('catatan') ?>
      </div>
      <input type="submit" class="btn w-100" value="Tambah" id="btn-submit">
      <hr id="hr-delete" class="hide">
      <button role="button" type="button" onclick="confirmDeleteWishlist()" class="btn w-100 hide" style="--primary-color:var(--red-color)" id="btn-delete">Hapus <i class="fas fa-trash-alt"></i></button>
    </form>
  </div>
</dialog>
<form id="delete-form" method="post" action="<?= BASEURL ?>/Wishlist/delete" class="hide">
  <input type="hidden" name="id">
</form>

<script>
  const FORM = document.querySelector('form#form');
  const MODAL = document.querySelector('#wishlist-dialog');
  const DELETE_FORM = document.querySelector('#delete-form');
  const WISHLIST_DATA = <?= json_encode($data['items']) ?>;

  MODAL.addEventListener('click', function(event) {
    const rect = MODAL.getBoundingClientRect();
    const isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
      rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
    if (!isInDialog) MODAL.close();
  });

  const openAdd = () => {
    FORM.reset();
    FORM.id.value = '';
    FORM.action = '<?= BASEURL ?>/Wishlist/add';
    document.querySelector('#dialog-title').textContent = 'Tambah Wishlist';
    document.querySelector('#btn-submit').value = 'Tambah';
    document.querySelector('#btn-delete').classList.add('hide');
    document.querySelector('#hr-delete').classList.add('hide');
    MODAL.showModal();
  };

  const openEdit = (item) => {
    FORM.reset();
    FORM.id.value = item.id;
    FORM.nama.value = item.nama || '';
    FORM.harga_estimasi.value = item.harga_estimasi || '';
    FORM.link.value = item.link || '';
    FORM.catatan.value = item.catatan || '';
    FORM.action = `<?= BASEURL ?>/Wishlist/edit/${item.id}`;
    document.querySelector('#dialog-title').textContent = 'Edit Wishlist';
    document.querySelector('#btn-submit').value = 'Simpan';
    document.querySelector('#btn-delete').classList.remove('hide');
    document.querySelector('#hr-delete').classList.remove('hide');
    MODAL.showModal();
  };

  window.confirmDeleteWishlist = (id) => {
    const targetId = id || FORM.id.value;
    const item = WISHLIST_DATA.find(w => String(w.id) === String(targetId));
    Swal.fire({
      title: `Hapus ${item ? '<b>' + item.nama + '</b>' : 'item ini'}?`,
      text: "Tindakan ini tidak bisa dikembalikan",
      icon: "warning",
      target: "dialog",
      showCancelButton: true
    }).then((result) => {
      if (!result.isConfirmed) return;
      DELETE_FORM.id.value = targetId;
      DELETE_FORM.submit();
    });
  };

  document.querySelectorAll('.wishlist-card').forEach(card => {
    card.addEventListener('click', () => openEdit(card.dataset));
  });
</script>
<?php $Controller->view('components/webmcp', $data); ?>
