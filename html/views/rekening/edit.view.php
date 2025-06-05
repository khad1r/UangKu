<?php $Controller->view('rekening/add', $data); ?>
<script>
  const OLD_DATA = <?= json_encode($data['oldData']) ?>;
  document.addEventListener("DOMContentLoaded", function() {
    FORM.nama_rekening.value = OLD_DATA.nama;
    FORM.no_asli.value = OLD_DATA.no_asli;
    FORM.nominal_asing.value = OLD_DATA.nominal_asing;
    FORM.tgl_dibuat.value = OLD_DATA.tgl_dibuat;
    FORM.aktif.checked = OLD_DATA.aktif;
    FORM.harta.checked = OLD_DATA.harta;
    FORM.tgl_ditutup.value = OLD_DATA.tgl_ditutup;
    FORM.tgl_ditutup.disabled = OLD_DATA.aktif;
    FORM.tgl_ditutup.required = !OLD_DATA.aktif;
    FORM.keterangan.textContent = OLD_DATA.keterangan;
    FORM.record.value = 'Update Rekening'
    FORM.record.style.backgroundColor = 'var(--secondary-color)';
    FORM.record.style.color = 'var(--primary-color)';
    /* Might Not Needed */
    // FORM.insertAdjacentHTML('afterbegin', /* HTML */ `
    //   <a href="#" class="px-5 py-3 mt-3 mb-4 border-bottom border-top border-danger w-100 text-danger d-flex align-items-center justify-content-between"
    //     onclick="deleteRekeing()"
    //   >
    //     <span>Hapus</span>
    //     <i class="fas fa-trash-alt"></i>
    //   </a>
    //   `)
    FORM.insertAdjacentHTML('afterbegin', /* HTML */ `
      <input type="hidden" name="id" value="${OLD_DATA['id']}">
      `)
  });
  /* Might Not Needed */
  // const deleteRekeing = () => {
  //   Swal.fire({
  //     title: `Hapus ${OLD_DATA['nama']}..?`,
  //     text: "Tindakan ini tidak bisa dikembalikan",
  //     icon: "warning",
  //     showCancelButton: true,
  //   }).then((result) => {
  //     if (!result.isConfirmed) return;
  //     FORM.id.value = 'DELETE'
  //     FORM.submit()
  //   });
  // }
</script>