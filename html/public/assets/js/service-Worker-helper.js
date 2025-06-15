const CHANNEL = new BroadcastChannel('backgroundJob');
const SERVER = "/Transaction/subscribe";
const SERVICEWORKER = "/sw.js";

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