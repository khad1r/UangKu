/**
 * wishlist-share.js
 * PWA Share Target & Web Share API helper for Wishlist
 */

/**
 * Retrieve shared data from Cache or URL search params
 * @returns {Promise<{title?: string, text?: string, url?: string, action?: string}|null>}
 */
async function getSharedWishlistData() {
  // 1. Check Service Worker shared cache
  try {
    if ('caches' in window) {
      const cache = await caches.open('shared-files');
      const response = await cache.match('/pwa-share-wishlist');
      if (response) {
        await cache.delete('/pwa-share-wishlist'); // Consume & clear
        const json = await response.json();
        if (json && (json.title || json.text || json.url)) {
          return json;
        }
      }
    }
  } catch (err) {
    console.error('Error reading shared wishlist cache:', err);
  }

  // 2. Fallback to URL Query Params (e.g. /Wishlist?action=add&nama=...&link=...&harga=...)
  const params = new URLSearchParams(window.location.search);
  const action = params.get('action');
  const nama = params.get('nama') || params.get('name') || params.get('title');
  const link = params.get('link') || params.get('url');
  const harga = params.get('harga') || params.get('price');
  const catatan = params.get('catatan') || params.get('text') || params.get('note');

  if (action === 'add' || nama || link || harga || catatan) {
    // Clean URL without reloading page
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);

    return {
      title: nama || '',
      url: link || '',
      text: catatan || '',
      harga: harga || '',
      action: action || ''
    };
  }

  return null;
}

/**
 * Parse shared payload to extract structured wishlist fields:
 * - nama (product title)
 * - harga_estimasi (numeric IDR)
 * - link (URL)
 * - catatan (description/notes)
 * @param {{title?: string, text?: string, url?: string, harga?: string}} payload 
 * @returns {{nama: string, harga_estimasi: string, link: string, catatan: string}}
 */
function parseWishlistPayload(payload) {
  let rawTitle = (payload.title || '').trim();
  let rawText = (payload.text || '').trim();
  let rawUrl = (payload.url || '').trim();
  let rawHarga = (payload.harga || '').trim();

  // 1. Extract URL if not explicitly provided
  if (!rawUrl && rawText) {
    const urlMatch = rawText.match(/https?:\/\/[^\s]+/i);
    if (urlMatch) {
      rawUrl = urlMatch[0];
      rawText = rawText.replace(urlMatch[0], '').trim();
    }
  }

  // 2. Extract Price (IDR format support: Rp 150.000, Rp. 1.250.000, 150rb, 50k, 1.5jt)
  let extractedPrice = rawHarga ? String(rawHarga).replace(/\D/g, '') : '';
  if (!extractedPrice) {
    const combinedText = `${rawTitle} ${rawText}`;
    
    // Pattern 1: Rp. 150.000 or IDR 150.000
    const rpMatch = combinedText.match(/(?:rp\.?|idr)\s*([\d\.,]+)/i);
    if (rpMatch) {
      const cleanNum = rpMatch[1].replace(/\./g, '').replace(/,/g, '');
      if (!isNaN(cleanNum) && cleanNum.length >= 3) {
        extractedPrice = cleanNum;
      }
    }

    // Pattern 2: 150rb / 150k / 1.5jt
    if (!extractedPrice) {
      const shortMatch = combinedText.match(/([\d\.,]+)\s*(rb|ribu|k|jt|juta)\b/i);
      if (shortMatch) {
        let val = parseFloat(shortMatch[1].replace(',', '.'));
        const unit = shortMatch[2].toLowerCase();
        if (unit === 'rb' || unit === 'ribu' || unit === 'k') {
          val = val * 1000;
        } else if (unit === 'jt' || unit === 'juta') {
          val = val * 1000000;
        }
        if (!isNaN(val)) extractedPrice = String(Math.round(val));
      }
    }
  }

  // 3. Clean Product Name / Title
  let nama = rawTitle;
  if (!nama && rawText) {
    // Use first line of text
    const lines = rawText.split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length > 0) nama = lines[0];
  }
  if (!nama && rawUrl) {
    try {
      const hostname = new URL(rawUrl).hostname.replace(/^www\./, '');
      nama = `Barang dari ${hostname}`;
    } catch {
      nama = 'Barang Impian Baru';
    }
  }

  // Remove common Indonesian e-commerce suffixes
  nama = nama
    .replace(/\s*[-|•]\s*(Tokopedia|Shopee|Shopee Indonesia|Lazada|Blibli|Bukalapak|TikTok Shop|Amazon).*$/i, '')
    .replace(/^Beli\s+/i, '')
    .trim();

  // 4. Notes (Catatan)
  let catatan = rawText;
  if (catatan === nama) catatan = '';

  return {
    nama: nama || 'Barang Impian',
    harga_estimasi: extractedPrice || '',
    link: rawUrl || '',
    catatan: catatan || ''
  };
}

/**
 * Outbound Share Wishlist item via Web Share API or Clipboard
 * @param {Event} [event] 
 * @param {{nama: string, harga_estimasi?: number|string, link?: string, catatan?: string}} item 
 */
async function shareWishlistItem(event, item) {
  if (event && event.stopPropagation) event.stopPropagation();

  const formattedPrice = item.harga_estimasi 
    ? ` (Rp ${new Intl.NumberFormat('id-ID').format(item.harga_estimasi)})` 
    : '';
  
  const shareText = `Wishlist UangKu: ${item.nama}${formattedPrice}${item.catatan ? '\n' + item.catatan : ''}`;
  const shareUrl = item.link || window.location.href;

  if (navigator.share) {
    try {
      await navigator.share({
        title: item.nama,
        text: shareText,
        url: shareUrl
      });
      return;
    } catch (err) {
      if (err.name === 'AbortError') return; // User canceled share sheet
      console.warn('Navigator share error, falling back to clipboard:', err);
    }
  }

  // Clipboard fallback
  try {
    const copyContent = `${shareText}\n${shareUrl}`.trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(copyContent);
      if (typeof showAlert === 'function') {
        showAlert('Detail wishlist berhasil disalin ke clipboard!', 'success', 'Tersalin');
      } else {
        alert('Detail wishlist berhasil disalin!');
      }
    }
  } catch (err) {
    console.error('Clipboard copy failed:', err);
  }
}

/**
 * Initialize Wishlist Share Target handler on page load
 * @param {Object} options 
 * @param {Function} options.onData Callback receiving parsed wishlist item
 */
async function initWishlistShareTarget(options) {
  const sharedData = await getSharedWishlistData();
  if (!sharedData) return;

  const parsed = parseWishlistPayload(sharedData);

  if (options && typeof options.onData === 'function') {
    options.onData(parsed, sharedData);
    if (typeof showAlert === 'function' && (sharedData.title || sharedData.url || sharedData.text)) {
      showAlert('Data produk dari share berhasil dimuat!', 'success', 'Wishlist');
    }
  }
}
