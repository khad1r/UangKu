const CHANNEL = new BroadcastChannel('backgroundJob');
/* CACHE */
const STATIC = 'static-v1.2'
const CACHEMAXAGE = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds
const preCache = async () => {
  try {
    await caches.open(STATIC).then(cache => cache.addAll([
      "/assets/js/script.js",
      "/assets/css/style.css",
      "/error/notConnected",
      "/",
    ]));
  } catch (error) {
    console.error('failed to precache:', error);
  }
}
self.addEventListener('install', (event) => {
  console.log('Service worker installed.');
  event.waitUntil(Promise.all([
    preCache(),
    self.skipWaiting()
  ]));
});
self.addEventListener('message', async (event) => {
  if (event.data.prefetch) preCache()
});
self.addEventListener('activate', (event) => {
  console.log('Service worker activated.');
  CHANNEL.postMessage({ type: 'init' });

  // Enable navigation preload if supported
  const enableNavigationPreload = async () => {
    if (self.registration.navigationPreload) {
      await self.registration.navigationPreload.enable();
    }
  };

  // Remove unwanted caches
  const clearOldCaches = async () => {
    const cacheNames = await caches.keys();
    await Promise.all(
      cacheNames.map(cache => {
        if (![STATIC].includes(cache)) {
          console.log('Service Worker: Clearing Old Cache');
          return caches.delete(cache);
        }
      })
    );
  };

  event.waitUntil(Promise.all([
    enableNavigationPreload(),
    clearOldCaches(),
    self.clients.claim()
  ]));
});

self.addEventListener('fetch', e => {
  // Handle Share Target file submission
  if (e.request.url.includes('pwa-share-handle')) {
    e.respondWith(
      (async () => {
        try {
          const formData = await e.request.formData();
          const file = formData.get('attachment');
          if (file && file instanceof File) {
            const cache = await caches.open('shared-files');

            const fileResponse = new Response(file, {
              headers: { 'Content-Type': file.type, 'File-name': file.name }
            });

            await cache.put('/pwa-share-handle', fileResponse);
          }
        } catch (error) {
          console.error("Cache handling sharing,", error, e.request.url);
        }
        return Response.redirect('/Record', 303);
      })()
    );
    return;
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

  /* Handle Navigation Requests - Network-First with Timeout (800ms) & Navigation Preload */
  if (e.request.mode === 'navigate') {
    e.respondWith(
      (async () => {
        const cache = await caches.open(STATIC);

        // Try to match the request in cache (support matching '/' or '/Record')
        let cachedResponse = await cache.match(e.request);
        if (!cachedResponse && e.request.url.endsWith('/')) {
          cachedResponse = await cache.match('/Record');
        } else if (!cachedResponse && e.request.url.endsWith('/Record')) {
          cachedResponse = await cache.match('/');
        }

        // Define network fetch using navigation preload
        const fetchNetwork = async () => {
          try {
            let networkResponse = await e.preloadResponse;
            if (!networkResponse) {
              networkResponse = await fetch(e.request);
            }

            // Redirect to login if session expired
            if (networkResponse.redirected || networkResponse.url.includes('/Auth/login') || networkResponse.url.includes('/Auth/Logout')) {
              return networkResponse;
            }

            if (networkResponse.ok) {
              await cache.put(e.request, networkResponse.clone());
            }
            return networkResponse;
          } catch (error) {
            console.error('Navigation fetch failed:', error);
            throw error;
          }
        };

        // If no cached response exists, we must wait for the network
        if (!cachedResponse) {
          return fetchNetwork().catch(async () => {
            const fallback = await cache.match('/error/notConnected');
            return fallback || Response.error();
          });
        }

        // Try network first but with an 800ms timeout fallback
        return new Promise((resolve) => {
          let timedOut = false;
          const timeoutId = setTimeout(() => {
            timedOut = true;
            console.log('Navigation request timed out, using cached response');
            resolve(cachedResponse);
          }, 800);

          fetchNetwork()
            .then((response) => {
              if (!timedOut) {
                clearTimeout(timeoutId);
                resolve(response);
              }
            })
            .catch(() => {
              if (!timedOut) {
                clearTimeout(timeoutId);
                resolve(cachedResponse);
              }
            });
        });
      })()
    );
    return;
  }

  /* Handle Normal Request fallback (CSS, JS, assets) : cache->Request->error */
  e.respondWith(
    caches.match(e.request).then(async cacheResponse => {
      let isCacheExpired = true;
      if (cacheResponse) {
        const maxAgeMatch = cacheResponse.headers
          ?.get('Cache-Control')
          ?.match(/max-age=(\d+)/);
        const currentTime = Date.now();
        if (cacheResponse.headers.get('Date')) {
          const fetchTime = new Date(cacheResponse.headers.get('Date') || 0).getTime();
          const maxAge = (maxAgeMatch) ? parseInt(maxAgeMatch[1], 10) * 1000 : CACHEMAXAGE;
          isCacheExpired = currentTime - fetchTime > maxAge;
        } else {
          const expiresHeader = cacheResponse.headers?.get('Expires');
          const expiresTime = new Date(expiresHeader).getTime();
          isCacheExpired = currentTime > expiresTime;
        }
      }

      if (cacheResponse && cacheResponse.ok && !isCacheExpired) return cacheResponse;

      try {
        const networkResponse = await fetch(e.request);
        const cacheControl = networkResponse.headers.get('Cache-Control');
        if (networkResponse.ok && !(cacheControl && cacheControl.includes('no-store'))) {
          const cacheDyn = await caches.open(STATIC);
          cacheDyn.put(e.request, networkResponse.clone());
        }
        return networkResponse;
      } catch (error) {
        console.error("Fetch failed,", error, e.request.url);
        if (cacheResponse) return cacheResponse;
        if (e.request.headers.get("Accept")?.includes("application/json")) return Response.error();
        return caches.open(STATIC).then(cache => cache.match('/error/notConnected')) || Response.error();
      }
    })
  );
});