<div class="container">
  <form id="form" method="post" class="row" enctype="multipart/form-data">
    <div class="col-lg-8" id="main-group">
      <div class="form-group center-input">
        <label>Jenis Transaksi</label>
        <select class="form-control" id="jenis_transaksi" placeholder="Jenis Transaksi" name="jenis_transaksi" required>
          <option value="" class="hide" data-placeholder="true" readonly="true">Jenis Transaksi</option>
          <?php foreach ($data['jenis_transaksi'] as $x) { ?>
            <option value="<?= $x ?>"><?= $x ?></option>
          <?php } ?>
        </select>
        <?php InputValidator('jenis_transaksi') ?>
      </div>
      <div class="form-group">
        <label>Barang / Judul</label>
        <input type="text" class="form-control" placeholder="Barang / Judul" name="barang" required>
        <?php InputValidator('barang') ?>
      </div>
      <div class="p-3 mb-4 border-bottom border-top border-2 border-primary form-check form-switch d-flex justify-content-between ps-0" style="font-size: .75em;font-weight: 600;">
        <label class="form-check-label text-primary w-100" for="harta">Nilai Harta</label>
        <input class="form-check-input hide-group" type="checkbox" id="harta" name="harta" disabled="true">
        <?php InputValidator('harta') ?>
      </div>
      <div class="form-group center-input">
        <label>Rekening Sumber</label>
        <select class="form-control hide-group" id="rekening_sumber" placeholder="Rekening Sumber" name="rekening_sumber" required disabled="true">
          <option class="hide" value="" data-placeholder="true" readonly="true">Rekening Sumber</option>
        </select>
        <?php InputValidator('rekening_sumber') ?>
        <div class="d-flex justify-content-between font-weight-bold">
          <span class="info" id="rekening_sumber_akhir"></span>
        </div>
      </div>
      <div class="form-group center-input">
        <label>Rekening Masuk</label>
        <select class="form-control hide-group" id="rekening_masuk" placeholder="Rekening Masuk" name="rekening_masuk" required disabled="true">
          <option class="hide" value="" data-placeholder="true" readonly="true">Rekening Masuk</option>
        </select>
        <?php InputValidator('rekening_masuk') ?>
        <div class="d-flex justify-content-between font-weight-bold">
          <span class="info" id="rekening_masuk_akhir"></span>
        </div>
      </div>
      <div class="form-group">
        <label>Nominal</label>
        <div class="input-group">
          <span class="input-group-text">Rp.</span>
          <input type="text" class="form-control input-text-lg" placeholder="Nominal" name="nominal" inputmode="numeric" id="nominal" required>
        </div>
        <?php InputValidator('nominal') ?>
      </div>
      <div class="form-group">
        <label>Nominal Asing</label>
        <div class="input-group">
          <input type="text" class="form-control input-text-lg hide-group" placeholder="Nominal Asing" name="nominal_asing" inputmode="numeric" id="nominal_asing" required disabled="true">
          <span class="input-group-text" id="nominalasing">Rp.</span>
        </div>
        <?php InputValidator('nominal_asing') ?>
      </div>
      <div class="form-group">
        <label>Penyusutan / Bunga</label>
        <div class="input-group">
          <span class="input-group-text">Rp.</span>
          <input type="text" class="form-control input-text-lg hide-group" placeholder="Penyusutan / Bunga" name="penyusutan_bunga" inputmode="numeric" id="penyusutan_bunga" required disabled="true">
        </div>
        <?php InputValidator('penyusutan_bunga') ?>
      </div>
      <div class="form-group">
        <label>Kuantitas</label>
        <input type="number" class="form-control input-text-lg" placeholder="Kuantitas" name="kuantitas" inputmode="numeric" id="kuantitas" min="1" value="1" required>
        <?php InputValidator('kuantitas') ?>
      </div>
      <div class="form-group">
        <label>Total Transaksi</label>
        <div class="input-group">
          <span class="input-group-text">Rp.</span>
          <input type="text" class="form-control input-text-lg" name="total" placeholder="Total Transaksi" inputmode="numeric" id="total" readonly>
        </div>
        <?php InputValidator('total') ?>
      </div>
      <div class="form-group">
        <label>Total Transaksi Asing</label>
        <div class="input-group">
          <input type="text" class="form-control input-text-lg hide-group" name="total_asing" placeholder="Total Transaksi" inputmode="numeric" id="total_asing" readonly disabled="true">
          <span class="input-group-text" id="totalasing">Rp.</span>
        </div>
        <?php InputValidator('total_asing') ?>
      </div>
      <div class="p-3 mb-4 border-bottom border-top border-2 border-primary form-check form-switch d-flex justify-content-between ps-0" style="font-size: .75em;font-weight: 600;">
        <label class="form-check-label text-primary w-100" for="rutin">Rutin / Non Rutin</label>
        <input class="form-check-input" type="checkbox" id="rutin" name="rutin">
        <?php InputValidator('rutin') ?>
      </div>
      <div class="form-group">
        <label>Kelompok Transaksi</label>
        <select class="form-control" id="kelompok" placeholder="Koneksikan Transaksi" name="kelompok">
          <option class="hide" value="" data-placeholder="true" readonly="true">Kelompok Transaksi</option>
        </select>
        <?php InputValidator('kelompok') ?>
      </div>
    </div>
    <div class="col-lg-4" id="summary-group">
      <div class="form-group">
        <label>Tanggal Transaksi</label>
        <input type="date" id="tanggal" placeholder="Tanggal Transaksi" name="tanggal" class="form-control input-text-lg date-format" required>
        <?php InputValidator('tanggal') ?>
      </div>
      <div class="form-group">
        <label>Koneksikan Transaksi</label>
        <select class="form-control" id="relasi_transaksi" placeholder="Koneksikan Transaksi" name="relasi_transaksi">
          <option class="hide" value="" data-placeholder="true" readonly="true">Koneksikan Transaksi</option>
        </select>
        <?php InputValidator('relasi_transaksi') ?>
      </div>
      <div class="form-group">
        <!-- <label>Attachment</label> -->
        <input type="file" placeholder="Attachment" class="form-control" accept="image/*, application/pdf" name="attachment" />
        <?php InputValidator('attachment') ?>
        <canvas id="preview-canvas" style="display:none;"></canvas>
      </div>
      <div class="form-group">
        <div>
          <textarea name="keterangan" class="form-control border-bottom border-primary mb-2" placeholder="Keterangan" rows="5"></textarea>
          <?php InputValidator('keterangan') ?>
          <input type="submit" class="btn w-100" value="Catat Transaksi" name="record">
        </div>
      </div>
    </div>
    <div id="ss-dropdown"></div>
  </form>

  <!-- Voice Input FAB & Overlay -->
  <button type="button" id="voice-fab" class="btn btn-primary" title="Input Suara">
    <i class="fas fa-microphone"></i>
  </button>

  <div id="voice-overlay" class="voice-overlay hide">
    <div class="voice-card">
      <div class="voice-header mb-3">
        <h5 class="mb-0">Mendengarkan...</h5>
        <button type="button" id="voice-close" class="btn-close btn-close-white" style="filter: invert(1); opacity: 0.8;"></button>
      </div>
      <div class="voice-wave-container mb-4">
        <div class="voice-wave-bar"></div>
        <div class="voice-wave-bar"></div>
        <div class="voice-wave-bar"></div>
        <div class="voice-wave-bar"></div>
        <div class="voice-wave-bar"></div>
      </div>
      <div class="voice-transcript-container p-3">
        <p id="voice-transcript" class="mb-0 text-muted font-italic">Mulai berbicara...</p>
      </div>
      <div class="voice-status-feedback hide" id="voice-feedback"></div>
    </div>
  </div>
</div>
<script src="https://unpkg.com/slim-select@3/dist/slimselect.js" crossorigin="anonymous"></script>
<link href="https://unpkg.com/slim-select@3/dist/slimselect.css" rel="stylesheet" crossorigin="anonymous">
</link>
<script>
  const FORM = document.querySelector('form#form');
  const J_TRANS = <?= json_encode($data['jenis_transaksi']) ?>;
  let ARGS;
  let rekening_sumber_akhir = document.querySelector('#rekening_sumber_akhir')
  let rekening_masuk_akhir = document.querySelector('#rekening_masuk_akhir')

  FORM.tanggal.value = new Intl.DateTimeFormat('sv-SE').format(new Date());
  fetch(`<?= BASEURL ?>/Record/args?rekening=true`)
    .then(r => r.ok ? r.json() : Promise.reject(new Error(`Error ${r.status}`)))
    .then(async d => {
      ARGS = d
      await loadArgs()
      formState(null);
    })
    .catch((e) => showAlert(e.message, 'danger'))
  const rekeningSelectFormater = (d) => {
    let option = {
      text: `${d.nama}`,
      value: d.id,
    }
    harta = d.harta ? '<small class="text-warning fas fa-coins"></small>' : ''

    option['html'] = (d.isAsing) ?
      `<span class="d-flex justify-content-between w-100 me-2"><b>${harta}&nbsp;${d.nama.toUpperCase()}</b><span>${formattedNumber.format(d.saldo_asing)},- ${d.nominal_asing}</span></span>` :
      `<span class="d-flex justify-content-between w-100 me-2"><b>${harta}&nbsp;${d.nama.toUpperCase()}</b><span>Rp. ${formattedNumber.format(d.saldo)},-</span></span>`
    return option
  }

  const loadArgs = () => {
    FORM.jenis_transaksi.SlimSelect = new SlimSelect({
      select: FORM.jenis_transaksi,
      settings: {
        contentLocation: document.querySelector('#ss-dropdown'),
        allowDeselect: false
      }
    })
    FORM.rekening_sumber.SlimSelect = new SlimSelect({
      select: FORM.rekening_sumber,
      data: ARGS.Rekening.map(rekeningSelectFormater),
      settings: {
        contentLocation: document.querySelector('#ss-dropdown'),
        placeholderText: 'Rekening Sumber',
        allowDeselect: true
      }
    })
    FORM.rekening_masuk.SlimSelect = new SlimSelect({
      select: FORM.rekening_masuk,
      data: ARGS.Rekening.map(rekeningSelectFormater),
      settings: {
        contentLocation: document.querySelector('#ss-dropdown'),
        placeholderText: 'Rekening Masuk',
        allowDeselect: true
      }
    })
    FORM.rekening_masuk.SlimSelect.setSelected()
    FORM.rekening_sumber.SlimSelect.setSelected()
    FORM.rekening_sumber.lawan = FORM.rekening_masuk
    FORM.rekening_masuk.lawan = FORM.rekening_sumber
    FORM.kelompok.SlimSelect = new SlimSelect({
      select: FORM.kelompok,
      settings: {
        contentLocation: document.querySelector('#ss-dropdown'),
        allowDeselect: true,
      },
      events: {
        addable: (value) => value,
        search: searchKelompok
      }
    })
    FORM.relasi_transaksi.SlimSelect = new SlimSelect({
      select: FORM.relasi_transaksi,
      settings: {
        contentLocation: document.querySelector('#ss-dropdown'),
        allowDeselect: true
      },
      events: {
        search: searchRelasi
      }
    })
  }
  const rekeningSelectEvent = async (e) => {
    if (e.target.disabled) return;

    e.target.rekening = await ARGS.Rekening.find(r => r.id == e.target.value);

    hitung()
    // Skip validation if not in 'operasi' mode
    const isOperasi = J_TRANS[2] === FORM.jenis_transaksi.value;
    if (isOperasi && !validateRekeningSelection(e)) return;

    // Update UI based on rekening type
    const isAsing = e.target.rekening?.isAsing || e.target.lawan.rekening?.isAsing || false;

    FORM.switchStateInput(FORM.nominal_asing, isAsing);
    FORM.switchStateInput(FORM.total_asing, isAsing);

    if (isAsing) {
      rekeningAsing = e.target.rekening?.isAsing ?
        e.target.rekening :
        e.target.lawan.rekening?.isAsing ?
        e.target.lawan.rekening :
        false
      document.querySelector('#nominalasing').textContent = rekeningAsing.nominal_asing;
      document.querySelector('#totalasing').textContent = rekeningAsing.nominal_asing;
    }
  };

  // 🧠 Validation extracted for clarity
  function validateRekeningSelection(e) {
    const masukVal = FORM.rekening_masuk.value;
    const sumberVal = FORM.rekening_sumber.value;
    const masukRek = FORM.rekening_masuk.rekening;
    const sumberRek = FORM.rekening_sumber.rekening;
    // ⏱ Reset select safely
    function resetSlimSelect(e) {
      setTimeout(() => e.target.SlimSelect.setSelected(''), 0);
    }
    if (masukVal && sumberVal && masukVal === sumberVal) {
      showAlert('Tidak bisa pindah buku sama rekening', 'warning');
      resetSlimSelect(e);
      return false;
    }
    if (masukRek?.harta || sumberRek?.harta) {
      showAlert('Rekening Aset Harta tidak boleh langsung pindah buku', 'warning');
      resetSlimSelect(e);
      return false;
    }
    if (masukRek?.isAsing && sumberRek?.isAsing && masukRek.nominal_asing === sumberRek.nominal_asing) {
      showAlert('Tidak bisa pindah buku antar rekening dengan jenis uang asing berbeda', 'warning');
      resetSlimSelect(e);
      return false;
    }
    return true;
  }

  function hitung() {
    const {
      nominal,
      nominal_asing,
      kuantitas,
      total,
      total_asing,
      rekening_sumber,
      rekening_masuk
    } = FORM;

    const qty = +kuantitas.value || 0;
    const nominalIDR = nominal.value ? parseFloat(nominal.value.replace(/\./g, "").replace(",", ".")) : null;
    const nominalAsing = nominal_asing.value ? parseFloat(nominal_asing.value.replace(/\./g, "").replace(",", ".")) : null;

    const sumber = rekening_sumber.rekening;
    const masuk = rekening_masuk.rekening;

    const formatSaldo = (val) => val.toLocaleString('id');
    const formatDisplaySaldo = (val) => Number.isInteger(val) ? `${formatSaldo(val)},-` : formatSaldo(val);

    const totalIDR = nominalIDR * qty;
    const totalAsing = nominalAsing * qty;
    const saldoAkhirMasuk = (masuk?.saldo || 0) + totalIDR;
    const saldoAkhirSumber = (sumber?.saldo || 0) - totalIDR;
    const saldoAkhirMasukAsing = (masuk?.saldo_asing || 0) + totalAsing;
    const saldoAkhirSumberAsing = (sumber?.saldo_asing || 0) - totalAsing;

    // Process total IDR
    total.value = totalIDR > 0 ? formatSaldo(totalIDR) : '';
    total_asing.value = totalAsing > 0 ? formatSaldo(totalAsing) : '';
    rekening_sumber_akhir.innerHTML = null
    rekening_masuk_akhir.innerHTML = null
    if (nominalIDR !== null && qty) {

      if (sumber) {
        rekening_sumber_akhir.innerHTML = /* HTML */ `
        <p class="d-flex justify-content-between">Saldo Akhir : <b>Rp.${formatDisplaySaldo(saldoAkhirSumber)}</b></p>
      `;
      }
      if (masuk) {
        rekening_masuk_akhir.innerHTML = /* HTML */ `
        <p class="d-flex justify-content-between">Saldo Akhir : <b>Rp.${formatDisplaySaldo(saldoAkhirMasuk)}</b></p>
      `;
      }
    }

    // Process total Asing
    if (nominalAsing !== null && qty) {

      if (sumber && sumber.isAsing) {
        rekening_sumber_akhir.innerHTML = /* HTML */ `
        <p class="d-flex justify-content-between">Saldo Akhir : <b>${formatDisplaySaldo(saldoAkhirSumberAsing)}&nbsp;${sumber.nominal_asing}</b></p>
        <small class="d-flex justify-content-end">Rp.&nbsp;${formatDisplaySaldo(saldoAkhirSumber)}</small>
      `;
      }

      if (masuk && masuk.isAsing) {
        rekening_masuk_akhir.innerHTML = /* HTML */ `
        <p class="d-flex justify-content-between">Saldo Akhir : <b>${formatDisplaySaldo(saldoAkhirMasukAsing)}&nbsp;${masuk.nominal_asing}</b></p>
        <small class="d-flex justify-content-end">Rp.&nbsp;${formatDisplaySaldo(saldoAkhirMasuk)}</small>
      `;
      }
    }
  }

  function formState(e) {
    let jenis_transaksi = e?.target.value ?? FORM.jenis_transaksi.value;
    // STATE.operasi = jenis_transaksi;
    const state = J_TRANS.map(v => v === jenis_transaksi);
    FORM.switchStateInput(FORM.harta, state[0] || state[1])
    FORM.harta.switchState((state[0] || state[1]) && FORM.harta.checked)
    FORM.rutin.switchState((state[0] || state[1]) && FORM.rutin.checked)
    if (!(state[0] || state[2])) FORM.rekening_sumber.SlimSelect.setSelected()
    FORM.switchStateInput(FORM.rekening_sumber, state[0] || state[2])
    if (!state[1] || state[2]) FORM.rekening_masuk.SlimSelect.setSelected()
    FORM.switchStateInput(FORM.rekening_masuk, state[1] || state[2])
    // FORM.switchStateInput(FORM.rutin, state[0] || state[1])
    rekening_sumber_akhir.innerHTML = ''
    rekening_masuk_akhir.innerHTML = ''
  }
  FORM.rekening_sumber.addEventListener('change', rekeningSelectEvent)
  FORM.rekening_masuk.addEventListener('change', rekeningSelectEvent)
  FORM.switchStateInput = (element, state = null) => {
    if (typeof element === "string") element = document.querySelector(element);
    if (!(element instanceof Element)) return;

    // Check if element is inside FORM
    if (!FORM.contains(element)) return;
    if (state === null) state = element.disabled;
    if (state === !element.disabled) return;
    element.disabled = !state;
    element.classList.toggle('hide-group', !state);
    element.value = null
    if (element.SlimSelect)
      setTimeout(() => element.SlimSelect.setSelected(''), 0);
  }
  /* Modify state by  */
  FORM.jenis_transaksi.addEventListener('change', formState)
  FORM.harta.State = FORM.harta.checked;
  FORM.harta.switchState = (state = !FORM.harta.State) => {
    if (state === FORM.harta.State) return;
    FORM.relasi_transaksi.required = state
    FORM.switchStateInput(FORM.penyusutan_bunga, state)
    let rekening = state ? ARGS.Rekening.filter(r => r.harta) : ARGS.Rekening
    FORM.rekening_sumber.SlimSelect
      .setData(rekening.map(rekeningSelectFormater))
    FORM.rekening_masuk.SlimSelect
      .setData(rekening.map(rekeningSelectFormater))
    FORM.rekening_sumber.SlimSelect.setSelected()
    FORM.rekening_masuk.SlimSelect.setSelected()
    FORM.harta.State = !FORM.harta.State
  }
  FORM.harta.addEventListener('change', e => {
    e.target.switchState(e.target.checked)
  });
  FORM.rutin.State = FORM.rutin.checked;
  FORM.rutin.switchState = (state = !FORM.rutin.State) => {
    if (state === FORM.rutin.State) return;
    FORM.kelompok.required = state
    FORM.rutin.State = !FORM.rutin.State
  }
  FORM.rutin.addEventListener('change', e => {
    e.target.switchState(e.target.checked)
  });
  FORM.kuantitas.addEventListener('keyup', function(e) {
    hitung()
  })
  // Helper to format as Indonesian Currency/Number
  const formatID = (val) => {
    if (!val) return '';
    // Split parts to handle decimals separately
    let parts = val.toString().replace(/[^0-9,]/g, '').split(',');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return parts.length > 1 ? parts[0] + ',' + parts[1].substring(0, 4) : parts[0];
  };
  [FORM.nominal, FORM.nominal_asing].forEach(el => {
    // el.addEventListener('change', () => hitung());
    el.addEventListener('keyup', function(e) {
      hitung()
      // Allow digits and a single comma
      let v = e.target.value.replace(/[^0-9,]/g, '');

      // Prevent multiple commas
      const commaCount = (v.match(/,/g) || []).length;
      if (commaCount > 1) {
        v = v.lastIndexOf(',') !== -1 ? v.substring(0, v.lastIndexOf(',')) : v;
      }

      e.target.value = formatID(v);
    });
  });
  FORM.addEventListener('submit', async e => {
    await e.preventDefault()
    const processValue = (input) => {
      // Convert Indonesian format (1.250,50) to standard Float (1250.50)
      let raw = input.value.replace(/\./g, '').replace(',', '.');
      return parseFloat(raw) || 0;
    };
    await showAlert('Memproses....', 'warning')
    FORM.record.disabled = true;
    FORM.record.value = "Memproses...";
    const {
      nominal,
      nominal_asing,
      tanggal
    } = FORM
    // Set the values to raw floats for backend processing
    nominal.value = processValue(nominal);
    nominal_asing.value = processValue(nominal_asing);
    tanggal.value = tanggal.dataset.raw;

    e.currentTarget.submit();
  })
  const formBreakpoint = window.matchMedia('(min-width: 768px)');
  const moveSummary = () => {
    if (formBreakpoint.matches) {
      FORM.tanggal.closest('.form-group').insertAdjacentElement('afterend', FORM.total_asing.closest('.form-group'))
      FORM.tanggal.closest('.form-group').insertAdjacentElement('afterend', FORM.total.closest('.form-group'))
      FORM.tanggal.closest('.form-group').insertAdjacentElement('afterend', FORM.rekening_masuk.closest('.form-group'))
      FORM.tanggal.closest('.form-group').insertAdjacentElement('afterend', FORM.rekening_sumber.closest('.form-group'))
      FORM.tanggal.closest('.form-group').insertAdjacentElement('afterend', FORM.harta.closest('div'))
    } else {
      FORM.barang.closest('.form-group').insertAdjacentElement('afterend', FORM.harta.closest('div'));
      FORM.harta.closest('div').insertAdjacentElement('afterend', FORM.rekening_sumber.closest('.form-group'));
      FORM.rekening_sumber.closest('.form-group').insertAdjacentElement('afterend', FORM.rekening_masuk.closest('.form-group'));
      FORM.kuantitas.closest('.form-group').insertAdjacentElement('afterend', FORM.total.closest('.form-group'));
      FORM.total.closest('.form-group').insertAdjacentElement('afterend', FORM.total_asing.closest('.form-group'));
    }
  }
  formBreakpoint.addEventListener('change', moveSummary)

  FORM.attachment.addEventListener('change', handleFileUpload);
  document.addEventListener('paste', handleFileUpload);
  const canvas = document.querySelector('#preview-canvas');
  const ctx = canvas.getContext('2d');

  async function handleFileUpload(event) {
    const files = [...(event.target.files || event.clipboardData.files)];
    const file = files[0]; // only handle first file for preview

    canvas.style.display = "none"; // hide first
    ctx.clearRect(0, 0, canvas.width, canvas.height); // clear canvas
    if (!file) return;

    if (file.type === "application/pdf") {
      const buffer = await file.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({
        data: buffer
      }).promise;
      const page = await pdf.getPage(1);
      const scale = 1.5;
      const viewport = page.getViewport({
        scale
      });

      canvas.width = viewport.width;
      canvas.height = viewport.height;
      canvas.style.display = "block";

      await page.render({
        canvasContext: ctx,
        viewport
      }).promise;

    } else if (file.type.startsWith("image/")) {
      const img = new Image();
      img.onload = () => {
        const maxWidth = 600;
        const scale = Math.min(maxWidth / img.width, 1);
        canvas.width = img.width * scale;
        canvas.height = img.height * scale;
        canvas.style.display = "block";

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      };
      img.src = URL.createObjectURL(file);
    } else {
      return showAlert("Unsupported file type: " + file.type, 'warning');
    }
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    FORM.attachment.files = dataTransfer.files;
  }

  function debounce(fn, delay = 500) {
    let timer;
    return function(...args) {
      clearTimeout(timer)
      return new Promise((resolve, reject) => {
        timer = setTimeout(() => {
          fn.apply(this, args).then(resolve).catch(reject)
        }, delay)
      })
    }
  }
  const searchKelompok = debounce((search, currentData) => {
    return new Promise((resolve, reject) => {
      if (search.length < 3) {
        return reject('Setidaknya 3 huruf')
      }
      // Fetch random first and last name data
      fetch(`<?= BASEURL ?>/Record/args?kelompok=${search}`)
        .then((response) => response.json())
        .then((data) => {
          // Take the data and create an array of options
          // excluding any that are already selected in currentData
          const newOption =
            data.kelompok
            // .filter((kel) => !currentData.some((old) => old.value === `${kel.first_name} ${kel.last_name}`))
            .map((kel) => {
              return {
                text: kel,
                value: kel,
              }
            })
          resolve(newOption)
        })
        .catch((e) => {
          showAlert(e.message, 'danger');
          return reject('Error')
        })
    })
  })
  const searchRelasi = debounce((search, currentData) => {
    return new Promise((resolve, reject) => {
      if (search.length < 4) {
        return reject('Setidaknya 4 Huruf')
      }
      fetch(`<?= BASEURL ?>/Record/args?transaksi=${search}`)
        .then((response) => response.json())
        .then((data) => {
          const newOption =
            data.transaksi
            .map((d) => {
              rek = [d.rekening_sumber, d.rekening_masuk].filter(Boolean).join(' | ')
              return {
                text: `${d.barang}-${d.id}`,
                value: d.id,
                html: /* HTML */ `
                  <div class="w-100 d-flex justify-content-between flex-row">
                    <b class="truncate-text">${d.barang}</b>
                    <span class="truncate-text">${d.rekening_sumber ? '-'+d.rekening_sumber : ''}+${d.rekening_masuk ? '-'+d.rekening_masuk : ''}</span>
                    <small style='font-size:.75em'>[${d.id}]</small>
                  </div>
                  <div class="w-100 d-flex justify-content-between">
                    <b>Rp.${(+d.nominal).toLocaleString('id')}</b> <span class="truncate-text">${d.kelompok}</span>
                  </div>
                `
              }
            })
          resolve(newOption)
        })
        .catch((e) => {
          showAlert(e.message, 'danger');
          return reject('Error')
        })
    })
  }, 1200)
  /* ========================================================
     VOICE INPUT FUNCTIONALITY (Speech Recognition for IDR)
     ======================================================== */

  // Indonesian number parsing helper
  function parseIndonesianWordsToNumber(text) {
    const words = text.toLowerCase().replace(/[^a-z0-9\s]/g, '').split(/\s+/);

    function parseSubThousand(subWords) {
      const units = {
        'nol': 0,
        'satu': 1,
        'dua': 2,
        'tiga': 3,
        'empat': 4,
        'lima': 5,
        'enam': 6,
        'tujuh': 7,
        'delapan': 8,
        'sembilan': 9,
        'sepuluh': 10,
        'sebelas': 11,
        'seratus': 100,
        'seribu': 1000
      };

      let val = 0;
      for (let i = 0; i < subWords.length; i++) {
        const w = subWords[i];
        if (units[w] !== undefined) {
          val += units[w];
        } else if (w === 'puluh') {
          let lastToken = subWords[i - 1];
          if (lastToken && units[lastToken] !== undefined && units[lastToken] < 10) {
            val -= units[lastToken];
            val += units[lastToken] * 10;
          } else {
            val += 10;
          }
        } else if (w === 'belas') {
          let lastToken = subWords[i - 1];
          if (lastToken && units[lastToken] !== undefined && units[lastToken] < 10) {
            val -= units[lastToken];
            val += units[lastToken] + 10;
          } else {
            val += 11;
          }
        } else if (w === 'ratus') {
          let lastToken = subWords[i - 1];
          if (lastToken && units[lastToken] !== undefined && units[lastToken] < 10) {
            val -= units[lastToken];
            val += units[lastToken] * 100;
          } else {
            val += 100;
          }
        }
      }
      return val;
    }

    let total = 0;
    let tempWords = [];

    for (let i = 0; i < words.length; i++) {
      const w = words[i];
      if (w === 'miliar' || w === 'milyar') {
        total += (parseSubThousand(tempWords) || 1) * 1000000000;
        tempWords = [];
      } else if (w === 'juta') {
        total += (parseSubThousand(tempWords) || 1) * 1000000;
        tempWords = [];
      } else if (w === 'ribu') {
        total += (parseSubThousand(tempWords) || 1) * 1000;
        tempWords = [];
      } else {
        tempWords.push(w);
      }
    }
    total += parseSubThousand(tempWords);
    return total;
  }

  // Master Voice Parser
  function parseVoiceInput(text) {
    const originalText = text;
    text = text.toLowerCase().trim();

    // 1. Transaction Type Detection
    let jenis = null;

    const keywordsPengeluaran = ['pengeluaran', 'beli', 'bayar', 'belanja', 'jajan', 'ongkos', 'keluar', 'pulsa', 'makan'];
    const keywordsPemasukan = ['pemasukan', 'gaji', 'terima', 'dapat', 'bunga', 'masuk', 'refund', 'kembalian'];
    const keywordsPindahBuku = ['pindah buku', 'transfer', 'kirim', 'pindah', 'mutasi'];

    if (keywordsPindahBuku.some(k => text.includes(k))) {
      jenis = J_TRANS[2]; // Pindah Buku
    } else if (keywordsPemasukan.some(k => text.includes(k))) {
      jenis = J_TRANS[1]; // Pemasukan
    } else {
      jenis = J_TRANS[0]; // Pengeluaran (default)
    }

    // 2. Account Matching
    let rekSumber = null;
    let rekMasuk = null;

    if (ARGS && ARGS.Rekening) {
      // Match longer names first to prevent false partial matches (e.g., "Bank Central" before "Bank")
      const sortedRek = [...ARGS.Rekening].sort((a, b) => b.nama.length - a.nama.length);

      if (jenis === J_TRANS[2]) { // Pindah Buku
        // Check prepositions like "dari [rek]" and "ke [rek]"
        const regexDari = /dari\s+([a-z0-9\s]+)/i;
        const regexKe = /(?:ke|masuk(?:\s+ke)?)\s+([a-z0-9\s]+)/i;

        const matchDari = text.match(regexDari);
        const matchKe = text.match(regexKe);

        if (matchDari) {
          const phraseDari = matchDari[1];
          const found = sortedRek.find(r => phraseDari.includes(r.nama.toLowerCase()));
          if (found) rekSumber = found;
        }
        if (matchKe) {
          const phraseKe = matchKe[1];
          const found = sortedRek.find(r => phraseKe.includes(r.nama.toLowerCase()));
          if (found) rekMasuk = found;
        }

        // Fallback: If not found, match any mention in text
        if (!rekSumber || !rekMasuk) {
          const foundAccounts = [];
          for (let r of sortedRek) {
            if (text.includes(r.nama.toLowerCase())) {
              foundAccounts.push(r);
            }
          }
          if (foundAccounts.length >= 1 && !rekSumber) rekSumber = foundAccounts[0];
          if (foundAccounts.length >= 2 && !rekMasuk) rekMasuk = foundAccounts[1];
        }
      } else {
        // Find single matched account
        let matchedRek = null;
        for (let r of sortedRek) {
          if (text.includes(r.nama.toLowerCase())) {
            matchedRek = r;
            break;
          }
        }

        if (matchedRek) {
          if (jenis === J_TRANS[1]) { // Pemasukan
            rekMasuk = matchedRek;
          } else { // Pengeluaran
            rekSumber = matchedRek;
          }
        }
      }
    }

    // 3. Amount / Nominal Extraction
    let nominalVal = 0;
    const digitMatch = text.match(/\b\d+(?:[\d.]*(?:\d+))?\b/g);
    let extractedDigitNumber = null;

    if (digitMatch) {
      const parsedDigits = digitMatch.map(dStr => {
        const val = parseFloat(dStr.replace(/\./g, '').replace(',', '.'));
        return {
          str: dStr,
          val: val
        };
      }).filter(item => !isNaN(item.val));

      if (parsedDigits.length > 0) {
        // Take the last or largest numeric match
        const lastNum = parsedDigits[parsedDigits.length - 1];
        if (lastNum.val >= 100 || parsedDigits.length === 1) {
          extractedDigitNumber = lastNum.val;
        }
      }
    }

    let extractedWordNumber = parseIndonesianWordsToNumber(text);
    nominalVal = extractedDigitNumber || extractedWordNumber || 0;

    // 4. Rutin and Kelompok Extraction
    let isRutin = false;
    let kelompok = null;

    // Check for "rutin" / "non rutin"
    if (text.includes('non rutin')) {
      isRutin = false;
      text = text.replace('non rutin', '');
    } else if (text.includes('rutin')) {
      isRutin = true;
      text = text.replace('rutin', '');
    }

    // Check for "kelompok"
    const kelompokMatch = text.match(/\bkelompok\s+([a-z0-9\s]+)/i);
    if (kelompokMatch) {
      kelompok = kelompokMatch[1].trim();
      text = text.replace(kelompokMatch[0], '');
    }

    // 5. Description (Barang) Extraction
    let barang = text;

    // Remove transaction keywords
    const allKeywords = [...keywordsPengeluaran, ...keywordsPemasukan, ...keywordsPindahBuku];
    allKeywords.forEach(k => {
      const reg = new RegExp('\\b' + k + '\\b', 'gi');
      barang = barang.replace(reg, '');
    });

    // Remove Account names
    if (ARGS && ARGS.Rekening) {
      ARGS.Rekening.forEach(r => {
        const reg = new RegExp('\\b' + r.nama + '\\b', 'gi');
        barang = barang.replace(reg, '');
      });
    }

    // Remove standard words and numbers
    const numberWords = [
      'nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
      'sepuluh', 'sebelas', 'seratus', 'seribu', 'puluh', 'belas', 'ratus', 'ribu', 'juta', 'miliar', 'milyar',
      'rupiah', 'sebesar', 'nominal', 'harga', 'jumlah', 'senilai'
    ];
    numberWords.forEach(w => {
      const reg = new RegExp('\\b' + w + '\\b', 'gi');
      barang = barang.replace(reg, '');
    });

    if (digitMatch) {
      digitMatch.forEach(d => {
        barang = barang.replace(d, '');
      });
    }

    // Remove trailing/leading helper words
    const preps = ['dari', 'ke', 'di', 'pakai', 'untuk', 'dengan', 'menggunakan', 'masuk'];
    preps.forEach(p => {
      const reg = new RegExp('\\b' + p + '\\b', 'gi');
      barang = barang.replace(reg, '');
    });

    // Final clean
    barang = barang.replace(/\s+/g, ' ').trim();
    barang = barang.replace(/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/g, '');

    if (barang.length > 0) {
      barang = barang.charAt(0).toUpperCase() + barang.slice(1);
    } else {
      barang = jenis === J_TRANS[2] ? "Pindah Buku" : jenis;
    }

    return {
      jenis,
      barang,
      nominal: nominalVal,
      rekSumber,
      rekMasuk,
      isRutin,
      kelompok
    };
  }

  // Update Form
  async function updateFormFromParsedData(parsed) {
    if (!parsed) return;

    let changes = [];

    // Update Jenis Transaksi
    if (parsed.jenis) {
      FORM.jenis_transaksi.SlimSelect.setSelected(parsed.jenis);
      formState({
        target: FORM.jenis_transaksi
      });
      changes.push(`Jenis: <b>${parsed.jenis}</b>`);
    }

    // Update Description (Barang)
    if (parsed.barang) {
      FORM.barang.value = parsed.barang;
      changes.push(`Barang: <b>"${parsed.barang}"</b>`);
    }

    // Update Nominal
    if (parsed.nominal > 0) {
      FORM.nominal.value = formatID(parsed.nominal.toString());
      changes.push(`Nominal: <b>Rp. ${formatID(parsed.nominal.toString())}</b>`);
    }

    // Update Accounts
    if (parsed.jenis === J_TRANS[2]) { // Pindah Buku
      if (parsed.rekSumber) {
        FORM.rekening_sumber.SlimSelect.setSelected(parsed.rekSumber.id);
        FORM.rekening_sumber.rekening = parsed.rekSumber;
        changes.push(`Sumber: <b>${parsed.rekSumber.nama.toUpperCase()}</b>`);
      }
      if (parsed.rekMasuk) {
        FORM.rekening_masuk.SlimSelect.setSelected(parsed.rekMasuk.id);
        FORM.rekening_masuk.rekening = parsed.rekMasuk;
        changes.push(`Masuk: <b>${parsed.rekMasuk.nama.toUpperCase()}</b>`);
      }
    } else if (parsed.jenis === J_TRANS[1]) { // Pemasukan
      if (parsed.rekMasuk) {
        FORM.rekening_masuk.SlimSelect.setSelected(parsed.rekMasuk.id);
        FORM.rekening_masuk.rekening = parsed.rekMasuk;
        changes.push(`Masuk Ke: <b>${parsed.rekMasuk.nama.toUpperCase()}</b>`);
      }
    } else { // Pengeluaran
      if (parsed.rekSumber) {
        FORM.rekening_sumber.SlimSelect.setSelected(parsed.rekSumber.id);
        FORM.rekening_sumber.rekening = parsed.rekSumber;
        changes.push(`Sumber Dari: <b>${parsed.rekSumber.nama.toUpperCase()}</b>`);
      }
    }

    // Update Rutin Checkbox
    if (parsed.isRutin) {
      FORM.rutin.checked = true;
      FORM.rutin.switchState(true);
      changes.push(`Rutin: <b>Ya</b>`);
    }

    // Update Kelompok
    if (parsed.kelompok) {
      // Because `rutin` triggers required `kelompok`, it's good that we set it.
      // Make sure rutin state is handled
      if (!FORM.rutin.checked) {
        FORM.kelompok.required = false;
      }

      // Create option object
      const kelOption = { text: parsed.kelompok, value: parsed.kelompok };
      let existingData = FORM.kelompok.SlimSelect.getData();

      try {
        // Try to fetch existing kelompok to see if it exists
        const response = await fetch(`<?= BASEURL ?>/Record/args?kelompok=${parsed.kelompok}`);
        if (response.ok) {
           const data = await response.json();
           if (data.kelompok && data.kelompok.length > 0) {
              // For simplicity, just pick the first match from the backend if it exists
              // The backend match could be exact or partial
              kelOption.text = data.kelompok[0];
              kelOption.value = data.kelompok[0];
           }
        }
      } catch(e) {
        // ignore errors and fallback to user's word
      }

      // Check if it's already in the SlimSelect options
      if (!existingData.some(opt => opt.value === kelOption.value)) {
         existingData.push(kelOption);
         FORM.kelompok.SlimSelect.setData(existingData);
      }

      FORM.kelompok.SlimSelect.setSelected(kelOption.value);
      changes.push(`Kelompok: <b>${kelOption.text}</b>`);
    }

    hitung();

    const voiceFeedback = document.querySelector('#voice-feedback');
    if (changes.length > 0) {
      voiceFeedback.innerHTML = `Terdeteksi:<br>${changes.join(', ')}`;
      voiceFeedback.classList.remove('hide');
    }
  }

  // Voice recognition initiation
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const voiceFab = document.querySelector('#voice-fab');
  const voiceOverlay = document.querySelector('#voice-overlay');
  const voiceClose = document.querySelector('#voice-close');
  const voiceTranscript = document.querySelector('#voice-transcript');
  const voiceFeedback = document.querySelector('#voice-feedback');

  if (!SpeechRecognition) {
    voiceFab.style.display = 'none';
  } else {
    // Initialize Bootstrap Popover for voice suggestions
    let voicePopover = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
      voicePopover = new bootstrap.Popover(voiceFab, {
        trigger: 'hover',
        placement: 'left',
        html: true,
        title: 'Format Input Suara',
        content: `
          <div style="font-size: 0.85rem; line-height: 1.4; max-width: 250px;">
            Coba ucapkan:<br>
            • <strong>Pengeluaran:</strong> <em>"beli kopi lima belas ribu pakai gopay"</em><br>
            • <strong>Pemasukan:</strong> <em>"gaji bulanan lima juta masuk mandiri"</em><br>
            • <strong>Pindah Buku:</strong> <em>"transfer dari gopay ke dompet seratus ribu"</em>
          </div>
        `
      });
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'id-ID';
    recognition.continuous = false;
    recognition.interimResults = true;

    let recognizing = false;

    recognition.onstart = () => {
      recognizing = true;
      if (voicePopover) voicePopover.hide();
      voiceFab.classList.add('listening');
      voiceOverlay.classList.remove('hide');
      voiceTranscript.textContent = 'Mendengarkan...';
      voiceTranscript.classList.remove('text-muted');
      voiceFeedback.classList.add('hide');
      voiceFeedback.textContent = '';
    };

    recognition.onerror = (event) => {
      console.error(event.error);
      if (event.error === 'not-allowed') {
        voiceTranscript.textContent = 'Izin mikrofon ditolak.';
      } else if (event.error === 'no-speech') {
        voiceTranscript.textContent = 'Tidak terdengar suara.';
      } else {
        voiceTranscript.textContent = `Kesalahan: ${event.error}`;
      }
      setTimeout(() => {
        voiceOverlay.classList.add('hide');
        voiceFab.classList.remove('listening');
      }, 2000);
    };

    recognition.onend = () => {
      recognizing = false;
      voiceFab.classList.remove('listening');
    };

    recognition.onresult = (event) => {
      let interimTranscript = '';
      let finalTranscript = '';

      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript;
        } else {
          interimTranscript += event.results[i][0].transcript;
        }
      }

      voiceTranscript.textContent = finalTranscript || interimTranscript || 'Mendengarkan...';

      if (finalTranscript) {
        const parsed = parseVoiceInput(finalTranscript);
        updateFormFromParsedData(parsed).then(() => {
          setTimeout(() => {
            voiceOverlay.classList.add('hide');
          }, 2200);
        });
      }
    };

    voiceFab.addEventListener('click', () => {
      if (voicePopover) voicePopover.hide();
      if (recognizing) {
        recognition.stop();
      } else {
        recognition.start();
      }
    });

    voiceClose.addEventListener('click', () => {
      recognition.stop();
      voiceOverlay.classList.add('hide');
    });

    voiceOverlay.addEventListener('click', (e) => {
      if (e.target === voiceOverlay) {
        recognition.stop();
        voiceOverlay.classList.add('hide');
      }
    });
  }

  moveSummary();

  /* PWA Share handle */

  window.addEventListener('DOMContentLoaded', async () => {
    const file = await getFileFromCache();
    if (!file) return
    const {
      blob,
      fileName
    } = file;
    const cachedFile = new File([blob], fileName, {
      type: blob.type
    }); // Use original filename
    handleFileUpload({
      target: {
        files: [cachedFile]
      }
    })
  });
</script>
