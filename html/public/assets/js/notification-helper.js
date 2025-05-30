const CHANNEL = new BroadcastChannel('backgroundJob');
const SERVER = "/Main/subscribe";
const SERVICEWORKER = "/sw.js";

const getNotificationState = () => localStorage.getItem('notification') === 'true' && Notification.permission === 'granted'

const enableNotification = async () => {
  try {
    const permission = await Notification.requestPermission();
    if (permission !== 'granted')
      throw new Error("Mohon memberikan izin notifikasi secara manual");

    const serviceWorker = await getServiceWorker();
    if (!serviceWorker)
      throw new Error("Perangkat/Browser Anda tidak mendukung notifikasi");

    const vapidKey = await getVapidKey();
    if (!vapidKey)
      throw new Error("Gagal mendapatkan informasi pendukung notifikasi");

    const subscription = await generateSubscription(serviceWorker, vapidKey);
    if (!subscription) {
      subscription.unsubscribe()
      throw new Error("Gagal terhubung dengan layanan notifikasi");
    }

    localStorage.setItem('notification', true);
    localStorage.setItem('notification_id', sessionStorage.getItem('notification_id'));
    return true;
  } catch (error) {
    localStorage.setItem('notification', false);
    throw error;
  }
}
const disableNotification = async () => {
  try {
    const serviceWorker = await getServiceWorker()
    const subscription = await getSubscription(serviceWorker)
    if (subscription) subscription.unsubscribe().then(() => {
      sendSubscription(null, subscription)
      localStorage.setItem('notification', false);
    })
    return true
  } catch (error) {
    console.error(error)
    return false
  }
}
const urlB64ToUint8Array = (base64String) => {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  return new Uint8Array([...rawData].map(char => char.charCodeAt(0)));
}
const getVapidKey = async () => {
  const response = await fetch(SERVER, {
    headers: {
      'Accept': 'application/json', // Specify the type you expect
    },
  });
  if (!response.ok) throw new Error('Gagal mendapatkan informasi pendukung notifikasi');
  return response.status === 200 ? await response.json().then(json => json.PUBLIC_KEY) : false;
}
const sendSubscription = async (subscription = null, old = null) => {
  const subscription_data = {
    ...(subscription && { data: subscription }),
    ...(old && { remove: [old] })
  };
  const response = await fetch(SERVER, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(subscription_data),
  });

  return response.ok; // `res.ok` is true for response status in the range 200-299
}
const getSubscription = async (serviceWorker) => {
  return await serviceWorker.pushManager.getSubscription();
}
const generateSubscription = async (swRegistration, vapidKey) => {
  const applicationServerKey = urlB64ToUint8Array(vapidKey);
  // const pushSubscription = await getSubscription(swRegistration);
  // if (!pushSubscription) {
  const subscription = await swRegistration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: applicationServerKey,
  });
  const saved = await sendSubscription(subscription).catch(() => false);
  if (saved) return subscription;
  else subscription.unsubscribe()
  // } return pushSubscription
};
const getServiceWorker = async () => {
  if ('serviceWorker' in navigator) {
    let serviceWorker = await navigator.serviceWorker.getRegistration();
    // Register the Service Worker if not already registered or active
    if (!serviceWorker || !serviceWorker.active) {
      try {
        serviceWorker = await navigator.serviceWorker.register(SERVICEWORKER);
        console.log("Service Worker registered with scope:", serviceWorker.scope);
        return serviceWorker
      } catch (error) {
        console.error("Service Worker registration failed:", error);
        return;
      }
    } else if (sessionStorage.getItem('prefetch') !== 'true') {
      serviceWorker.active.postMessage({ prefetch: true })
      sessionStorage.setItem('prefetch', true);
    }
    return serviceWorker;
  } else {
    console.error("ServiceWorkers are not supported by your browser!")
    return;
  };
}


document.addEventListener("DOMContentLoaded", async () => {
  if (
    Notification.permission === 'granted'
    && sessionStorage.getItem('notification_id') !== localStorage.getItem('notification_id')
    && sessionStorage.getItem('isInput')
  ) {
    sessionStorage.removeItem('isInput')
    await disableNotification()
    enableNotification().catch(error => showAlert('danger', `Gagal.<br><small>${error.message}</small>`))
  }
  CHANNEL.addEventListener('message', (event) => {
    if (event.data.type === 'notify') showAlert('success', event.data.data.body);
    if (event.data.type === 'init') {
      sessionStorage.setItem('prefetch', true);
      if (Notification.permission === 'default' || !getNotificationState())
        enableNotification().catch(error => showAlert('danger', `Gagal.<br><small>${error.message}</small>`))
    }
  });
});
