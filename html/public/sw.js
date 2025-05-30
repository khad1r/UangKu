const CHANNEL = new BroadcastChannel('backgroundJob');
/* CACHE */
const CACHENAME = 'private'
const STATIC = 'static-v1.0'
const CACHEMAXAGE = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds
const preFetch = async () => {
  try {
    const response = await fetch('/Main', {
      method: 'OPTIONS',
      headers: {
        'Accept': 'application/json', // Specify the type you expect
      }
    });
    if (response.ok) {
      const PRECACHE = await response.json();
      await caches.open(CACHENAME).then(cache => cache.addAll(PRECACHE.private));
      await caches.open(STATIC).then(cache => cache.addAll(PRECACHE.public));
    }
  } catch (error) {
    console.error('failed to precache:', error);
  }
}
self.addEventListener('install', (event) => {
  console.log('Service worker installed.');
  event.waitUntil(preFetch())
});
self.addEventListener('message', async (event) => {
  if (event.data.prefetch) preFetch()
});
self.addEventListener('activate', (event) => {
  console.log('Service worker activated.');
  CHANNEL.postMessage({ type: 'init' });
  // Remove unwanted caches
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (![CACHENAME, STATIC].includes(cache)) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        }))
    }))
});
self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET' || !e.request.url.startsWith('http')) {
    e.respondWith(fetch(e.request).catch(error => {
      if (e.request.url.startsWith(self.location.origin)) {
        if (e.request.headers.get("Accept")?.includes("application/json")) return Response.error();
        return caches.open(STATIC).then(cache => cache.match('/ShowError/notConnected') || Promise.reject('No fallback available'));
      }
      return Promise.reject(error);
    }));
    return;
  }

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
          const cacheDyn = await caches.open(CACHENAME)
          cacheDyn.put(e.request, networkResponse.clone())
        }
        return networkResponse;
      } catch (error) {
        console.error("Fetch failed,", error, e.request.url);
        caches.delete(e.request);
        if (e.request.headers.get("Accept")?.includes("application/json")) return Response.error();
        return caches.open(STATIC).then(cache => cache.match('/ShowError/notConnected')) || Response.error();
      }
    }).catch(async (error) => {
      console.error("Cache handling failed,", error, e.request.url);
      return caches.open(STATIC).then(cache => cache.match('/ShowError/notConnected')) || Response.error();
    }) // Fallback to cache on fetch error
  );
});



/* PUSH Event */
const showNotification = (title, options) =>
  new Promise(resolve => {
    CHANNEL.postMessage({ type: 'notify', data: { title, body: options.body } });
    self.registration.showNotification(title, options).then(() => resolve());
  });
self.addEventListener('push', async event => {
  // const res = JSON.parse(event.data.text());
  const { title, options } = await event.data.json();
  options.icon = "/assets/img/icon.ico"
  options.badge = "/assets/img/icon.ico"
  options.data.url = "/Main"
  event.waitUntil(showNotification(title, options));
});
self.addEventListener('notificationclick', (event) => {
  event.notification.close(); // Close the notification
  const { url } = event.notification.data;
  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then((clientList) => {
      // If there's at least one open window/tab
      if (clientList.length > 0) {
        const client = clientList[0]; // Pick the first one
        return client.navigate(url) && client.focus(); // Redirect and focus
      }
      // If no open client, open a new window
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});