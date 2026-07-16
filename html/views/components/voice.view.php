<style>
  #voice-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    z-index: 1040;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    cursor: pointer;
    border: none;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-color-alt));
    color: var(--white-color);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);

    &:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.45);
    }

    &:active {
      transform: translateY(1px) scale(0.98);
    }

    &.listening {
      background: linear-gradient(135deg, var(--red-color), var(--tertiary-color-alt));
      box-shadow: 0 0 0 0 rgba(var(--red-rgb), 0.7);
      animation: pulse-red 1.5s infinite;
    }

    @keyframes pulse-red {
      0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(var(--red-rgb), 0.7);
      }

      70% {
        transform: scale(1);
        box-shadow: 0 0 0 15px rgba(var(--red-rgb), 0);
      }

      100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(var(--red-rgb), 0);
      }
    }

    @media (orientation: portrait) {
      bottom: 112px;
    }
  }

  .voice-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(10px);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    transition: opacity 0.3s ease;

    &.hide {
      display: none !important;
    }

    .voice-card {
      background: rgba(30, 30, 30, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      padding: 24px;
      width: 90%;
      max-width: 450px;
      color: var(--white-color);
      box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .voice-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .voice-wave-container {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
      height: 60px;
    }

    .voice-wave-bar {
      width: 6px;
      height: 15px;
      background-color: var(--primary-color);
      border-radius: 3px;
      animation: voiceWave 1.2s infinite ease-in-out;

      &:nth-child(2) {
        animation-delay: 0.15s;
      }

      &:nth-child(3) {
        animation-delay: 0.3s;
      }

      &:nth-child(4) {
        animation-delay: 0.45s;
      }

      &:nth-child(5) {
        animation-delay: 0.6s;
      }

      @keyframes voiceWave {

        0%,
        100% {
          height: 15px;
        }

        50% {
          height: 50px;
          background-color: var(--primary-color-alt);
        }
      }
    }

    .voice-transcript-container {
      background: rgba(255, 255, 255, 0.07);
      border-radius: 8px;
      min-height: 80px;
      max-height: 150px;
      overflow-y: auto;
      font-size: 1.1rem;
      line-height: 1.5;
      text-align: left;
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .voice-status-feedback {
      font-size: 0.95rem;
      padding: 12px 16px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.05);
      color: #e0e0e0;
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: 16px;
      text-align: left;
      max-height: 250px;
      overflow-y: auto;
    }

    #voice-actions button {
      border-radius: 8px;
      padding: 6px 16px;
      font-size: 0.85rem;
      transition: all 0.2s ease;
    }

    #voice-actions button:hover {
      transform: translateY(-1px);
    }

    #voice-actions button:active {
      transform: translateY(1px);
    }
  }
</style>

<!-- Voice Input FAB & Overlay -->
<button type="button" id="voice-fab" class="btn btn-primary" title="Input Suara">
  <i class="fas fa-microphone"></i>
</button>

<div id="voice-overlay" class="voice-overlay hide">
  <div class="voice-card">
    <div class="voice-header mb-3">
      <h5 class="mb-0" id="voice-title">Mendengarkan...</h5>
      <button type="button" id="voice-close" class="btn-close btn-close-white" style="filter: invert(1); opacity: 0.8;"></button>
    </div>
    <div class="voice-wave-container mb-4" id="voice-wave-container">
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
    <!-- Voice confirmation actions -->
    <div id="voice-actions" class="d-flex justify-content-end gap-2 mt-3 hide">
      <button type="button" id="voice-btn-reset" class="btn btn-warning btn-sm px-3 font-weight-bold text-white">Ulangi</button>
      <button type="button" id="voice-btn-cancel" class="btn btn-secondary btn-sm px-3 font-weight-bold">Batal</button>
      <button type="button" id="voice-btn-ok" class="btn btn-success btn-sm px-4 font-weight-bold text-white">Terapkan</button>
    </div>
  </div>
</div>

<script>
  let VoiceParseCallback = async (parsedData) => {
    console.log(parsedData);
    showAlert('Tidak terdapat voice parser disini', 'warning')
  };
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

  /* ========================================================
     VOICE INPUT FUNCTIONALITY (Optimized)
     ======================================================== */

  // 1. MEMOIZE STATIC DATA OUTSIDE THE FUNCTION
  const VOICE_KEYWORDS = {
    pengeluaran: ['pengeluaran', 'beli', 'bayar', 'belanja', 'jajan', 'ongkos', 'keluar', 'pulsa', 'makan'],
    pemasukan: ['pemasukan', 'gaji', 'terima', 'dapat', 'bunga', 'masuk', 'refund', 'kembalian'],
    pindahBuku: ['pindah buku', 'transfer', 'kirim', 'pindah', 'mutasi'],
    numbers: ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'seratus', 'seribu', 'puluh', 'belas', 'ratus', 'ribu', 'juta', 'miliar', 'milyar', 'rupiah', 'sebesar', 'nominal', 'harga', 'jumlah', 'senilai'],
    preps: ['dari', 'ke', 'di', 'pakai', 'untuk', 'dengan', 'menggunakan', 'masuk']
  };

  let descriptionStopWordsRegex = null;
  let sortedAccountsCache = null;

  // Initialize caches once to prevent recalculating on every interim voice result
  function initVoiceCaches() {
    if (!descriptionStopWordsRegex) {
      let stopWords = [
        ...VOICE_KEYWORDS.pengeluaran,
        ...VOICE_KEYWORDS.pemasukan,
        ...VOICE_KEYWORDS.pindahBuku,
        ...VOICE_KEYWORDS.numbers,
        ...VOICE_KEYWORDS.preps
      ];

      if (typeof ARGS !== 'undefined' && ARGS.Rekening) {
        // Sort accounts by length once
        sortedAccountsCache = [...ARGS.Rekening].sort((a, b) => b.nama.length - a.nama.length);
        // Add account names to stopwords
        ARGS.Rekening.forEach(r => stopWords.push(r.nama.toLowerCase()));
      }

      // Escape regex special characters just in case, then compile a single, massive Regex
      const escapedWords = stopWords.map(w => w.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&'));
      descriptionStopWordsRegex = new RegExp('\\b(' + escapedWords.join('|') + ')\\b', 'gi');
    }
  }

  // Master Voice Parser
  function parseVoiceInput(text) {
    initVoiceCaches(); // Ensure this is called

    text = text.toLowerCase().trim();

    // FIX 1: Pre-clean "rp" or "rp." that speech-to-text might glue to numbers
    // (Changes "roti rp.15.000" -> "roti 15.000")
    text = text.replace(/\brp\s*\.?/gi, ' ');

    // 1. Transaction Type Detection
    let jenis = J_TRANS[0]; // Default Pengeluaran
    if (VOICE_KEYWORDS.pindahBuku.some(k => text.includes(k))) {
      jenis = J_TRANS[2];
    } else if (VOICE_KEYWORDS.pemasukan.some(k => text.includes(k))) {
      jenis = J_TRANS[1];
    }

    // 2. Account Matching
    let rekSumber = null;
    let rekMasuk = null;

    if (sortedAccountsCache) {
      if (jenis === J_TRANS[2]) { // Pindah Buku
        const matchDari = text.match(/dari\s+([a-z0-9\s]+)/i);
        const matchKe = text.match(/(?:ke|masuk(?:\s+ke)?)\s+([a-z0-9\s]+)/i);

        if (matchDari) rekSumber = sortedAccountsCache.find(r => matchDari[1].includes(r.nama.toLowerCase()));
        if (matchKe) rekMasuk = sortedAccountsCache.find(r => matchKe[1].includes(r.nama.toLowerCase()));

        if (!rekSumber || !rekMasuk) {
          const foundAccounts = sortedAccountsCache.filter(r => text.includes(r.nama.toLowerCase()));
          if (!rekSumber && foundAccounts.length >= 1) rekSumber = foundAccounts[0];
          if (!rekMasuk && foundAccounts.length >= 2) rekMasuk = foundAccounts[1];
        }
      } else {
        const matchedRek = sortedAccountsCache.find(r => text.includes(r.nama.toLowerCase()));
        if (matchedRek) {
          jenis === J_TRANS[1] ? (rekMasuk = matchedRek) : (rekSumber = matchedRek);
        }
      }
    }

    // 3. Amount / Nominal Extraction
    let nominalVal = 0;

    // FIX 2: Match digits optionally followed by multiplier words (e.g., "15", "15.000", "15 ribu", "1,5 juta")
    const digitRegex = /(\d+(?:[.,]\d+)*)\s*(ribu|juta|miliar|milyar)?/gi;
    let match;
    let lastDigitMatch = null;

    // Loop to find the last valid amount mentioned
    while ((match = digitRegex.exec(text)) !== null) {
      lastDigitMatch = match;
    }

    if (lastDigitMatch) {
      // Clean dots and change Indonesian comma to decimal point
      let parsedVal = parseFloat(lastDigitMatch[1].replace(/\./g, '').replace(',', '.'));
      const multiplier = lastDigitMatch[2];

      // Apply multiplier if the STT returned a mixed format like "15 ribu"
      if (multiplier) {
        if (multiplier === 'ribu') parsedVal *= 1000;
        else if (multiplier === 'juta') parsedVal *= 1000000;
        else if (multiplier === 'miliar' || multiplier === 'milyar') parsedVal *= 1000000000;
      }

      // Assign if valid
      if (!isNaN(parsedVal) && parsedVal > 0) {
        nominalVal = parsedVal;
      }
    }

    // Fallback to purely written words (e.g., "lima belas ribu")
    if (!nominalVal) {
      nominalVal = parseIndonesianWordsToNumber(text) || 0;
    }

    // 4. Rutin and Kelompok Extraction
    const isRutin = text.includes('rutin') && !text.includes('non rutin');

    let kelompok = null;
    // Alternative: Treats either "kelompok [X]" OR "rutin [X]" as the group name
    const kelompokMatch = text.match(/\b(?:kelompok|rutin)\s+([a-z0-9\s]+)/i);
    if (kelompokMatch) {
      kelompok = kelompokMatch[1].trim();
      text = text.replace(kelompokMatch[0], '');
    }

    text = text.replace(/non rutin|rutin/gi, '');

    // 5. Description (Barang) Extraction
    let barang = text;

    // Apply the single, pre-compiled regex for O(1) stripping
    if (descriptionStopWordsRegex) {
      barang = barang.replace(descriptionStopWordsRegex, '');
    }

    // FIX 3: Strip digits AND their attached multipliers so they don't leak into the barang string
    barang = barang.replace(/(\d+(?:[.,]\d+)*)\s*(ribu|juta|miliar|milyar)?/gi, '');

    // Final clean up (spaces and non-alphanumeric edges)
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

  // Wrap DOM bindings and listeners in DOMContentLoaded to ensure bootstrap is loaded
  document.addEventListener('DOMContentLoaded', () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const voiceFab = document.querySelector('#voice-fab');
    const voiceOverlay = document.querySelector('#voice-overlay');
    const voiceClose = document.querySelector('#voice-close');
    const voiceTranscript = document.querySelector('#voice-transcript');
    const voiceFeedback = document.querySelector('#voice-feedback');
    const voiceTitle = document.querySelector('#voice-title');
    const voiceWaveContainer = document.querySelector('#voice-wave-container');
    const voiceActions = document.querySelector('#voice-actions');
    const voiceBtnReset = document.querySelector('#voice-btn-reset');
    const voiceBtnCancel = document.querySelector('#voice-btn-cancel');
    const voiceBtnOk = document.querySelector('#voice-btn-ok');

    if (!voiceFab) return; // Guard for pages without the FAB

    if (!SpeechRecognition) {
      voiceFab.style.display = 'none';
      return;
    }

    let recognizing = false;
    let lastParsedData = null;

    function showParsedFeedback(parsed) {
      if (!parsed) return;

      let html = '<div style="font-weight: 700; margin-bottom: 10px; color: var(--primary-color); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px; font-size: 1rem;">Deteksi Transaksi:</div>';
      html += `<div style="display: grid; grid-template-columns: 110px 1fr; gap: 8px 4px; font-size: 0.9rem; line-height: 1.4;">`;

      const formatRow = (label, val) => {
        if (!val) return '';
        return `<div class="text-white-50"><strong>${label}</strong></div><div class="text-white">${val}</div>`;
      };

      html += formatRow('Tipe', parsed.jenis);
      html += formatRow('Barang', parsed.barang);

      if (parsed.nominal > 0) {
        const formattedNominal = new Intl.NumberFormat('id-ID').format(parsed.nominal);
        html += formatRow('Nominal', `Rp. ${formattedNominal}`);
      }

      if (parsed.jenis === J_TRANS[2]) { // Pindah Buku
        if (parsed.rekSumber) html += formatRow('Sumber', parsed.rekSumber.nama.toUpperCase());
        if (parsed.rekMasuk) html += formatRow('Masuk', parsed.rekMasuk.nama.toUpperCase());
      } else if (parsed.jenis === J_TRANS[1]) { // Pemasukan
        if (parsed.rekMasuk) html += formatRow('Masuk Ke', parsed.rekMasuk.nama.toUpperCase());
      } else { // Pengeluaran
        if (parsed.rekSumber) html += formatRow('Sumber Dari', parsed.rekSumber.nama.toUpperCase());
      }

      if (parsed.isRutin) {
        html += formatRow('Rutin', 'Ya');
      }
      if (parsed.kelompok) {
        html += formatRow('Kelompok', parsed.kelompok);
      }

      html += `</div>`;

      voiceFeedback.innerHTML = html;
      voiceFeedback.classList.remove('hide');
    }

    function resetVoiceUI() {
      voiceTitle.textContent = 'Mendengarkan...';
      voiceTranscript.textContent = 'Mulai berbicara...';
      voiceTranscript.classList.add('text-muted');
      voiceFeedback.innerHTML = '';
      voiceFeedback.classList.add('hide');
      voiceActions.classList.add('hide');
      voiceWaveContainer.classList.remove('hide');
      lastParsedData = null;
    }

    // Initialize Bootstrap Popover for voice suggestions
    let voicePopover = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
      voicePopover = new bootstrap.Popover(voiceFab, {
        trigger: 'hover',
        placement: 'left',
        html: true,
        title: 'Format Input Suara',
        content: /* HTML */ `
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
    recognition.continuous = true; // KEEP ACTIVE: allow user to continue speaking
    recognition.interimResults = true;

    recognition.onstart = () => {
      recognizing = true;
      if (voicePopover) voicePopover.hide();
      voiceFab.classList.add('listening');
      voiceOverlay.classList.remove('hide');
      resetVoiceUI();
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
        if (!lastParsedData) {
          voiceOverlay.classList.add('hide');
          voiceFab.classList.remove('listening');
        }
      }, 2000);
    };

    recognition.onend = () => {
      recognizing = false;
      voiceFab.classList.remove('listening');
      // When recognition ends due to silence/browser timeout:
      // Hide wave animation and change title to indicate listening has finished
      voiceWaveContainer.classList.add('hide');
      if (lastParsedData) {
        voiceTitle.textContent = 'Hasil Analisis';
      }
    };

    recognition.onresult = (event) => {
      let interimTranscript = '';
      let finalTranscript = '';

      for (let i = 0; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript;
        } else {
          interimTranscript += event.results[i][0].transcript;
        }
      }

      const displayTranscript = finalTranscript + interimTranscript;
      voiceTranscript.textContent = displayTranscript || 'Mendengarkan...';
      voiceTranscript.classList.remove('text-muted');

      const textToParse = finalTranscript + interimTranscript;
      if (textToParse.trim()) {
        lastParsedData = parseVoiceInput(textToParse);

        // Show parsed data in overlay live in real-time
        showParsedFeedback(lastParsedData);
        voiceActions.classList.remove('hide');
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

    // OK / Terapkan Button click - actually update the form
    voiceBtnOk.addEventListener('click', async () => {
      if (lastParsedData) {
        await VoiceParseCallback(lastParsedData);
        recognition.stop();
        voiceOverlay.classList.add('hide');
      }
    });

    // Ulangi / Reset Button click - stop current, reset UI and restart Speech Recognition
    voiceBtnReset.addEventListener('click', () => {
      recognition.stop();
      resetVoiceUI();
      // Restart after a small timeout to let the previous instance stop completely
      setTimeout(() => {
        if (!recognizing) {
          recognition.start();
        }
      }, 300);
    });

    // Batal / Close Button click - just close overlay
    voiceBtnCancel.addEventListener('click', () => {
      recognition.stop();
      voiceOverlay.classList.add('hide');
    });
  });
</script>