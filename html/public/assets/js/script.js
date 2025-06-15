let loadingPage;
// let pdfjslib = {
//   load: ''
// }
document.addEventListener("DOMContentLoaded", function () {
  loadingPage = document.querySelector(".loading-Page");
  if (document.querySelector(".modal.addEdit")) {
    var myModal = new bootstrap.Modal(
      document.querySelector(".modal.addEdit"),
      {}
    );
    myModal.show();
  }
  if ('undefined' === typeof (wait)) loadingPage.style.display = "none";
  window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
});

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll('input.date-format').forEach(input => {
    const format = () => {
      const date = new Date(input.value);
      input.type = "text";
      if (!isNaN(date)) {
        input.dataset.raw = input.value; // Save raw value like "2025-05-26"
        input.value = toDateShortMonth(date);
      } else {
        input.dataset.raw = input.dataset.raw ?? '';
        input.value = input.dataset.raw ?? '';
      }
    }
    input.addEventListener('focus', e => {
      e.target.value = e.target.dataset.raw
      e.target.type = 'date'
      e.target.showPicker()
    })

    input.addEventListener("blur", format);
    format()
  })
})
function showAlert(message, type = 'primary') {
  const toastTemplate = document.querySelector("#toast-template");
  const toast = toastTemplate.cloneNode(true);

  toast.id = ''; // Clear the ID for the new toast

  // Add the appropriate class for the alert type
  toast.classList.add(`text-bg-${type}`);

  // Set the message
  toast.querySelector('.toast-body>span').innerHTML = message;

  document.querySelector('.toast-container').prepend(toast)
  const bsToast = bootstrap.Toast.getOrCreateInstance(toast)
  // Show the toast
  bsToast.show();
  // setTimeout(() => bsToast.hide(), 5000)
}
function showAlert(message, type = 'primary', title = '') {

  const toastTemplate = document.querySelector("#toast-template");
  const toast = toastTemplate.cloneNode(true);
  toast.id = ''; // Clear the ID for the new toast
  // Add the appropriate class for the alert type
  toast.classList.add(`border-${type}`);
  toast.querySelector('.toast-header').classList.add(`bg-${type}`);
  // // Set the message
  toast.querySelector('.toast-body').innerHTML = message;
  toast.querySelector('strong').innerHTML = title;

  document.querySelector('#toast-container').prepend(toast)
  $(toast).toast('show')
  // setTimeout(() => bsToast.hide(), 5000)
}

const formattedNumber = new Intl.NumberFormat('id-ID')
let formatDate = ($date, options) => {
  return (new Intl.DateTimeFormat("id", options)).format($date)
}
let toMonthShort = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    month: "short",
  })).format($date);
}
let toDateNumeric = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    day: "numeric",
    month: "numeric",
    year: "numeric"
  })).format($date);
}
let toDate = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    day: "numeric",
  })).format($date);
}
let toYear = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    year: "numeric"
  })).format($date);
}
let toWeekday = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    weekday: "long",
  })).format($date);
}
let toDateShortMonth = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    weekday: "long",
    day: "numeric",
    month: "numeric",
    year: "numeric"
  })).format($date);
}
// Helper functions.
var webAuthnHelper = {
  // array buffer to base64
  atb: b => {
    let u = new Uint8Array(b), s = "";
    for (let i = 0; i < u.byteLength; i++) { s += String.fromCharCode(u[i]); }
    return btoa(s);
  },
  // base64 to array buffer
  bta: o => {
    let pre = "=?BINARY?B?", suf = "?=";
    for (let k in o) {
      if (typeof o[k] == "string") {
        let s = o[k];
        if (s.substring(0, pre.length) == pre && s.substring(s.length - suf.length) == suf) {
          let b = window.atob(s.substring(pre.length, s.length - suf.length)),
            u = new Uint8Array(b.length);
          for (let i = 0; i < b.length; i++) { u[i] = b.charCodeAt(i); }
          o[k] = u.buffer;
        }
      } else { webAuthnHelper.bta(o[k]); }
    }
  }
};
const CHANNEL = new BroadcastChannel('backgroundJob');
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
    }
    return serviceWorker;
  } else {
    console.error("ServiceWorkers are not supported by your browser!")
    return;
  };
}
async function getFileFromCache(cachedName) {
  const cache = await caches.open('cached-files');
  const response = await cache.match(cachedName);
  if (!response) return null;
  const {
    file,
    fileName,
    type
  } = await response.json();
  file = {
    blob: new Blob([file], {
      type
    }),
    fileName: fileName
  };
  await caches.delete(cachedName); // Clear cache
  return file
}
// getServiceWorker()