const CHANNEL = new BroadcastChannel('backgroundJob');
/* CACHE */
const STATIC = 'static-v1.1'
const CACHEMAXAGE = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds
const preCache = async () => {
  try {
    await caches.open(STATIC).then(cache => cache.addAll([
      "/assets/js/script.js",
      "/assets/css/style.css",
      "/error/notConnected",
    ]));
  } catch (error) {
    console.error('failed to precache:', error);
  }
}
self.addEventListener('install', (event) => {
  console.log('Service worker installed.');
  event.waitUntil(preCache())
});
self.addEventListener('message', async (event) => {
  if (event.data.prefetch) preCache()
});
self.addEventListener('activate', (event) => {
  console.log('Service worker activated.');
  CHANNEL.postMessage({ type: 'init' });
  // Remove unwanted caches
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (![STATIC].includes(cache)) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        }))
    }))
});
self.addEventListener('fetch', e => {
  // Handle Share Target file submission
  if (e.request.url.includes('pwa-share-handle')) {
    e.respondWith(
      (async () => {
        try {
          const formData = await e.request.formData();
          // console.log(formData);
          // for (const [key, value] of formData.entries()) {
          //   console.log(`${key}:`, value);
          // }
          const file = formData.get('attachment');
          if (file && file instanceof File) {
            const cache = await caches.open('shared-files');

            const fileResponse = new Response(file, {
              headers: { 'Content-Type': file.type, 'File-name': file.name }
            });

            // Simpan file ke cache dengan nama unik (misalnya timestamp atau hash)
            const timestamp = Date.now();
            // const fileUrl = `/cached-share/${timestamp}-${file.name}`;

            // await cache.put(fileUrl, fileResponse);
            await cache.put('/pwa-share-handle', fileResponse);

            // Redirect ke halaman yang bisa ambil dari cache nanti
            // return Response.redirect(`/Record?shared-file=${encodeURIComponent(fileUrl)}`);
          }
        } catch (error) {
          console.error("Cache handling sharing,", error, e.request.url);
        }
        return Response.redirect('/Record', 303);
      })()
    );
    return
  }
  /* Handle Not Connected Post Request (Req first the error on not connected) */
  if (e.request.method !== 'GET' || !e.request.url.startsWith('http')) {
    e.respondWith(fetch(e.request).catch(error => {
      if (e.request.url.startsWith(self.location.origin)) {
        if (e.request.headers.get("Accept")?.includes("application/json")) return Response.error();
        return caches.open(STATIC).then(cache => cache.match('/error/notConnected') || Promise.reject('No fallback available'));
      }
      return Promise.reject(error);
    }));
    return;
  }


  /* Handle Normal Request fallback : cache->Request->error */
  e.respondWith(
    caches.match(e.request).then(async cacheResponse => {
      let isCacheExpired = true
      if (cacheResponse) {
        const maxAgeMatch = cacheResponse.headers
          ?.get('Cache-Control')
          ?.match(/max-age=(\d+)/);
        const currentTime = Date.now();
        if (cacheResponse.headers.get('Date')) {
          const fetchTime = new Date(cacheResponse.headers.get('Date') || 0).getTime(); // or time cached
          const maxAge = (maxAgeMatch) ? parseInt(maxAgeMatch[1], 10) * 1000 : CACHEMAXAGE; // Convert seconds to milliseconds
          isCacheExpired = currentTime - fetchTime > maxAge;
        } else {
          const expiresHeader = cacheResponse.headers?.get('Expires');
          const expiresTime = new Date(expiresHeader).getTime();
          isCacheExpired = currentTime > expiresTime;
        }
      }
      // If we have a cache response and it's not expired, use it
      if (cacheResponse && cacheResponse.ok && !isCacheExpired) return cacheResponse;
      else caches.delete(e.request)

      // Otherwise, fetch and update cache
      try {
        const networkResponse = await fetch(e.request);
        const cacheControl = networkResponse.headers.get('Cache-Control');
        if (networkResponse.ok && !(cacheControl && cacheControl.includes('no-store'))) {
          const cacheDyn = await caches.open(STATIC)
          cacheDyn.put(e.request, networkResponse.clone())
        }
        return networkResponse;
      } catch (error) {
        console.error("Fetch failed,", error, e.request.url);
        caches.delete(e.request);
        if (e.request.headers.get("Accept")?.includes("application/json")) return Response.error();
        return caches.open(STATIC).then(cache => cache.match('/error/notConnected')) || Response.error();
      }
    }).catch(async (error) => {
      console.error("Cache handling failed,", error, e.request.url);
      return caches.open(STATIC).then(cache => cache.match('/error/notConnected')) || Response.error();
    }) // Fallback to cache on fetch error
  );
});