let loadingPage;

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
});
function showAlert(type, message) {
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
let toDateShortMont = ($date) => {
  return (new Intl.DateTimeFormat("id", {
    weekday: "long",
    day: "numeric",
    month: "numeric",
    year: "numeric"
  })).format($date);
}
const removeCache = async () => {
  const cacheNames = await caches.keys();
  await Promise.all(
    cacheNames.map(cacheName => cacheName === 'private' && caches.delete(cacheName))
  );
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