<style>
  /* Base FAB Styles */
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

    @media (orientation: portrait) {
      bottom: 112px;
    }
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

  /* 🌟 Stylized Voice Overlay */
  .voice-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 15, 18, 0.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
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
      background: linear-gradient(145deg, rgba(40, 42, 50, 0.9), rgba(25, 26, 30, 0.95));
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 24px;
      padding: 28px;
      width: 92%;
      max-width: 420px;
      color: #fff;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .voice-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    #voice-title {
      font-weight: 600;
      letter-spacing: 0.5px;
      background: -webkit-linear-gradient(0deg, #fff, #aaa);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* 🌟 Stylized Wave Animation */
    .voice-wave-container {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      height: 70px;
      cursor: pointer;
      border-radius: 16px;
      background: rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: transform 0.2s cubic-bezier(0.25, 0.8, 0.25, 1), background-color 0.2s ease;
      padding: 10px;
      margin-bottom: 20px;

      &:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: scale(1.02);
      }

      &.listening .voice-wave-bar {
        animation: voiceWave 1.2s infinite ease-in-out;
        box-shadow: 0 0 10px rgba(var(--primary-rgb), 0.6);
      }
    }

    .voice-wave-bar {
      width: 6px;
      height: 12px;
      background-color: var(--primary-color);
      border-radius: 6px;
      transition: height 0.3s ease, background-color 0.3s ease;

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
    }

    /* 🌟 Stylized Transcript Box */
    .voice-transcript-container {
      background: rgba(0, 0, 0, 0.25);
      border-radius: 16px;
      min-height: 70px;
      max-height: 130px;
      overflow-y: auto;
      font-size: 1.1rem;
      line-height: 1.6;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: inset 0 4px 10px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    #voice-transcript {
      font-weight: 300;
      letter-spacing: 0.3px;
    }

    /* 🌟 Stylized Parsed Data Container */
    .voice-status-feedback {
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      margin-top: 20px;
      max-height: 300px;
      overflow-y: auto;
    }

    /* 🌟 Stylized Action Buttons */
    #voice-actions {
      margin-top: 24px;

      button {
        border-radius: 50rem;
        padding: 10px 20px;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
      }
    }

    #voice-btn-cancel {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #ddd;

      &:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
      }
    }

    #voice-btn-ok {
      background: linear-gradient(135deg, var(--green-color), #20c997);
      border: none;
      box-shadow: 0 6px 15px rgba(var(--green-rgb), 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(var(--green-rgb), 0.4);
      }
    }
  }

  @keyframes voiceWave {

    0%,
    100% {
      height: 12px;
    }

    50% {
      height: 45px;
      background-color: var(--primary-color-alt);
    }
  }
</style>

<!-- Voice Input FAB & Overlay -->
<button type="button" id="voice-fab" class="btn btn-primary" title="Input Suara">
  <i class="fas fa-microphone"></i>
</button>
<div id="voice-overlay" class="voice-overlay hide">
  <div class="voice-card">
    <div class="voice-header">
      <h5 class="mb-0" id="voice-title">Mendengarkan...</h5>
      <button type="button" id="voice-close" class="btn-close btn-close-white" style="filter: invert(1); opacity: 0.6;"></button>
    </div>

    <div class="voice-transcript-container p-3">
      <p id="voice-transcript" class="mb-0 text-muted font-italic">Mulai berbicara...</p>
    </div>

    <!-- Parsed results will be injected here -->
    <div class="voice-status-feedback hide p-3" id="voice-feedback"></div>

    <div class="voice-wave-container" id="voice-wave-container">
      <div class="voice-wave-bar"></div>
      <div class="voice-wave-bar"></div>
      <div class="voice-wave-bar"></div>
      <div class="voice-wave-bar"></div>
      <div class="voice-wave-bar"></div>
    </div>

    <!-- Voice confirmation actions -->
    <div id="voice-actions" class="d-flex justify-content-between gap-3 hide">
      <button type="button" id="voice-btn-cancel" class="btn w-50">
        <i class="fas fa-times"></i> Batal
      </button>
      <button type="button" id="voice-btn-ok" class="btn w-50 text-white">
        <i class="fas fa-check"></i> Terapkan
      </button>
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

    // 1. Try to find explicit "kelompok [nama]" first
    const kelompokMatch = text.match(/\bkelompok\s+([a-z0-9\s]+)/i);

    if (kelompokMatch) {
      kelompok = kelompokMatch[1].trim();
      text = text.replace(kelompokMatch[0], '');
    }
    // 2. Fallback: ONLY look for 1 or 2 words AFTER "rutin"
    else if (isRutin) {
      // Look forward only to prevent eating prepositions like "dari dompet"
      const rutinMatch = text.match(/\brutin\s+([a-z0-9]+\s+[a-z0-9]+|[a-z0-9]+)\b/i);

      if (rutinMatch) {
        const candidate = rutinMatch[1].trim();

        // Safety check: Prevent it from capturing action words or prepositions (e.g., "rutin beli...")
        const isStopWord = [...VOICE_KEYWORDS.pengeluaran, ...VOICE_KEYWORDS.pemasukan, ...VOICE_KEYWORDS.preps].some(w => candidate.includes(w));

        if (!isStopWord) {
          kelompok = candidate;
          // Remove ONLY the extracted group word from the text so it doesn't leak into 'barang'
          text = text.replace(kelompok, '');
        }
      }
    }

    // Globally clean up the trigger words
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
    const voiceBtnCancel = document.querySelector('#voice-btn-cancel');
    const voiceBtnOk = document.querySelector('#voice-btn-ok');

    if (!voiceFab) return; // Guard for pages without the FAB

    if (!SpeechRecognition) {
      voiceFab.style.display = 'none';
      return;
    }

    let recognizing = false;
    let isStarting = false;
    let lastParsedData = null;
    let accumulatedTranscript = '';
    let sessionFinalTranscript = '';

    function startRecognition() {
      if (recognizing || isStarting) return;
      try {
        isStarting = true;
        recognition.start();
      } catch (e) {
        console.error(e);
        isStarting = false;
      }
    }

    function stopRecognition() {
      try {
        recognition.stop();
      } catch (e) {
        console.error(e);
      }
      recognizing = false;
      isStarting = false;
    }

    function showParsedFeedback(parsed) {
      if (!parsed) return;

      // Header for the parsed data
      let html = `
        <div class="d-flex align-items-center mb-3 pb-2" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
          <i class="fas fa-bolt text-warning me-2 fs-5"></i>
          <h6 class="mb-0 fw-bold text-white" style="letter-spacing: 0.5px;">Hasil Deteksi</h6>
        </div>
        <div class="d-flex flex-column gap-2">
      `;

      // Helper function to build beautiful rows
      const formatRow = (icon, iconColor, label, val, isHighlight = false) => {
        if (!val) return '';
        const valStyle = isHighlight ?
          'color: var(--primary-color); font-weight: 800; font-size: 1.15rem; text-shadow: 0 0 10px rgba(var(--primary-rgb),0.3);' :
          'color: #f8f9fa; font-weight: 500; font-size: 0.95rem;';

        return `
          <div class="d-flex justify-content-between align-items-center bg-dark p-2 px-3 rounded-3" style="background: rgba(0,0,0,0.3) !important;">
            <div class="text-white-50 small d-flex align-items-center gap-2" style="font-size: 0.85rem;">
              <i class="fas ${icon} fa-fw text-${iconColor}"></i> ${label}
            </div>
            <div class="text-end text-wrap text-break" style="${valStyle} max-width: 60%; line-height: 1.2;">
              ${val}
            </div>
          </div>
        `;
      };

      // 1. Transaction Type
      let typeColor = parsed.jenis === J_TRANS[1] ? 'success' : (parsed.jenis === J_TRANS[2] ? 'warning' : 'danger');
      let typeIcon = parsed.jenis === J_TRANS[1] ? 'fa-arrow-circle-down' : (parsed.jenis === J_TRANS[2] ? 'fa-exchange-alt' : 'fa-arrow-circle-up');
      html += formatRow(typeIcon, typeColor, 'Tipe', parsed.jenis);

      // 2. Item / Description
      html += formatRow('fa-tag', 'info', 'Barang', parsed.barang);

      // 3. Nominal (Highlighted)
      if (parsed.nominal > 0) {
        const formattedNominal = new Intl.NumberFormat('id-ID').format(parsed.nominal);
        html += formatRow('fa-money-bill-wave', 'success', 'Nominal', `Rp. ${formattedNominal}`, true);
      }

      // 4. Accounts
      const formatAccountRow = (icon, label, rek) => {
        if (rek) {
          return formatRow(icon, 'secondary', label, rek.nama.toUpperCase());
        } else {
          // If no account is detected, show a warning text
          const warningEl = `<span class="text-danger fst-italic" style="font-size: 0.85rem;"><i class="fas fa-exclamation-triangle"></i> Belum disebutkan</span>`;
          return formatRow(icon, 'danger', label, warningEl);
        }
      };
      if (parsed.jenis === J_TRANS[2]) { // Pindah Buku
        html += formatAccountRow('fa-wallet', 'Sumber', parsed.rekSumber);
        html += formatAccountRow('fa-piggy-bank', 'Masuk', parsed.rekMasuk);
      } else if (parsed.jenis === J_TRANS[1]) { // Pemasukan
        html += formatAccountRow('fa-piggy-bank', 'Masuk Ke', parsed.rekMasuk);
      } else { // Pengeluaran
        html += formatAccountRow('fa-wallet', 'Sumber', parsed.rekSumber);
      }

      // 5. Rutin / Kelompok
      if (parsed.isRutin) {
        html += formatRow('fa-sync-alt', 'primary', 'Rutin', 'Ya');
      }
      if (parsed.kelompok) {
        html += formatRow('fa-folder-open', 'primary', 'Kelompok', parsed.kelompok);
      }

      html += `</div>`; // Close column wrapper

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
      accumulatedTranscript = '';
      sessionFinalTranscript = '';
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
    recognition.continuous = false; // Disable continuous to allow automatic pauses, click wave to continue
    recognition.interimResults = true;

    recognition.onstart = () => {
      recognizing = true;
      isStarting = false;
      if (voicePopover) voicePopover.hide();
      voiceFab.classList.add('listening');
      voiceOverlay.classList.remove('hide');
      voiceWaveContainer.classList.add('listening');
      voiceTitle.textContent = 'Mendengarkan...';
    };

    recognition.onerror = (event) => {
      console.error(event.error);
      isStarting = false;
      if (event.error === 'not-allowed') {
        voiceTranscript.textContent = 'Izin mikrofon ditolak.';
      } else if (event.error === 'no-speech') {
        voiceTranscript.textContent = 'Tidak terdengar suara.';
      } else {
        voiceTranscript.textContent = `Kesalahan: ${event.error}`;
      }
      setTimeout(() => {
        if (!lastParsedData && !recognizing) {
          voiceOverlay.classList.add('hide');
          voiceFab.classList.remove('listening');
        }
      }, 2000);
    };

    recognition.onend = () => {
      recognizing = false;
      isStarting = false;
      voiceFab.classList.remove('listening');
      // Stop wave animation
      voiceWaveContainer.classList.remove('listening');

      // Save session's final transcript to accumulated
      accumulatedTranscript = (accumulatedTranscript + ' ' + sessionFinalTranscript).replace(/\s+/g, ' ').trim();
      sessionFinalTranscript = '';

      if (lastParsedData) {
        voiceTitle.textContent = 'Hasil Analisis';
      } else {
        voiceTitle.textContent = 'Bicara Terhenti (Klik wave untuk lanjut)';
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

      sessionFinalTranscript = finalTranscript;

      const currentText = (accumulatedTranscript + ' ' + finalTranscript + ' ' + interimTranscript).replace(/\s+/g, ' ').trim();
      voiceTranscript.textContent = currentText || 'Mendengarkan...';
      voiceTranscript.classList.remove('text-muted');

      if (currentText) {
        lastParsedData = parseVoiceInput(currentText);

        // Show parsed data in overlay live in real-time
        showParsedFeedback(lastParsedData);
        voiceActions.classList.remove('hide');
      }
    };

    // Click the wave to continue listening, double click to reset
    voiceWaveContainer.addEventListener('click', () => {
      startRecognition();
    });

    voiceWaveContainer.addEventListener('dblclick', (e) => {
      e.stopPropagation();
      stopRecognition();
      resetVoiceUI();
      // Restart after a small timeout to let the previous instance stop completely
      setTimeout(() => {
        startRecognition();
      }, 300);
    });

    voiceFab.addEventListener('click', () => {
      if (voicePopover) voicePopover.hide();
      if (recognizing || isStarting) {
        stopRecognition();
      } else {
        if (voiceOverlay.classList.contains('hide')) {
          resetVoiceUI();
        }
        startRecognition();
      }
    });

    voiceClose.addEventListener('click', () => {
      stopRecognition();
      voiceOverlay.classList.add('hide');
    });

    voiceOverlay.addEventListener('click', (e) => {
      if (e.target === voiceOverlay) {
        stopRecognition();
        voiceOverlay.classList.add('hide');
      }
    });

    // OK / Terapkan Button click - actually update the form
    voiceBtnOk.addEventListener('click', async () => {
      if (lastParsedData) {
        await VoiceParseCallback(lastParsedData);
        stopRecognition();
        voiceOverlay.classList.add('hide');
      }
    });

    // Batal / Close Button click - just close overlay
    voiceBtnCancel.addEventListener('click', () => {
      stopRecognition();
      voiceOverlay.classList.add('hide');
    });
  });
</script>