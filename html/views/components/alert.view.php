  <!-- <div class="toast-container p-3 mb-3">

  </div>
  <div id="toast-template" class="toast align-items-center border-0 hide" role="alert" aria-live="assertive"
    aria-atomic="true" data-bs-dismiss="toast" aria-label="Close">
    <div class="toast-body d-flex flex-row justify-content-between align-items-center">
      <span></span>
      <button type="button" class="btn">
        <i class="fa-regular fa-circle-xmark"></i>
      </button>
    </div>
  </div> -->

  <div id="toast-container" style="position: fixed; bottom: 1rem; right: 1rem;">
    <div id="toast-template" class="toast hide border" role="alert" style="min-width: 25vw;" aria-live="assertive" aria-atomic="true" data-delay="5000">
      <div class="toast-header">
        <strong class="mr-auto text-white">Bootstrap</strong>
        <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body" style="color: black;">
        See? Just like this.
      </div>
    </div>
  </div>