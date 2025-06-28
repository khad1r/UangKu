<style>
  nav {

    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1030;

    /* // margin-bottom: 2rem; */
    /* // min-height: var(--nav-min-height) !important; */
    .nav-container {
      position: relative;
      /* font-size: max(.9em, 16px); */
    }

    .navbar {
      --nav-min-height: 6rem;
      --nav-background-color: var(--primary-color);
      /* --nav-background-color: var(--primary-color); */
      /* --nav-background-color: color-mix(in srgb, var(--primary-color) 50%, transparent); */
      --nav-box-shadow: 0px -2px 6px 5px color-mix(in srgb, var(--black-color) 30%, transparent);
      box-shadow: var(--nav-box-shadow);
      -webkit-box-shadow: var(--nav-box-shadow);
      -moz-box-shadow: var(--nav-box-shadow);
      background: var(--nav-background-color);
      border-radius: 10vh 10vh 0 0;
      min-height: var(--nav-min-height) !important;
      z-index: 999;
      display: flex;
      padding-inline: 2rem;
      justify-content: space-evenly;
      flex-wrap: nowrap;
      flex-direction: row;

      .nav-link {
        flex: 1 1 0;
        font-weight: 900;
        border: none;
        font-size: 1.5em;
        color: var(--white-color) !important;
        position: relative;
        transition: all 0.2s;

        &,
        .special {
          display: flex;
          justify-content: center;
          align-items: center;
          flex-direction: column;
        }

        .special {
          color: inherit;
          font-size: 1.5em;
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -85%);
          /* height: 7rem; */
          /* width: 7rem; */
          padding: .75em;
          border-radius: 50%;
          background-color: var(--secondary-color-alt);
          box-shadow: var(--nav-box-shadow);
          /* border: .7rem solid var(--nav-background-color); */
          border: .3rem solid var(--bg-color);
          transition: all 0.1s;

          &:focus,
          &:active,
          &:hover {
            /* padding: 2.5rem; */
            font-size: 1.52em;
          }

          &.my-tooltip {
            &::after {
              /* color: var(--white-color); */
              font-size: 0.3em;
              font-weight: 700;
              /* border: .3rem solid var(--bg-color); */
              top: 65%;
            }
          }
        }

        &:focus,
        &:active,
        &:hover {
          font-size: 1.7em;
        }

      }
    }

    .navbar-collapse {
      box-shadow: 0px -5px 4px 3px color-mix(in srgb, var(--black-color) 30%, transparent);
      border-radius: 5vh 5vh 0 0;
      bottom: 0;
      left: 0;
      padding-top: 2vh;
      position: absolute;
      width: 100%;
      background: var(--white-color);

      .navbar-more {

        /* min-height: 40dvh; */
        .nav-link {
          padding-block: 1rem;
          padding-inline: 3rem;
          color: var(--secondary-color);
          font-weight: 500;

          &:hover,
          &:active,
          &:focus {
            color: var(--primary-color);
            font-weight: 700;
            border-block: 5px solid;
            border-image-source: linear-gradient(to left,
                transparent 20%,
                var(--primary-color) 50%,
                transparent 80%);
            border-image-slice: 1;
          }
        }
      }

      &.show::before {
        content: '';
        z-index: -1;
        background: repeating-linear-gradient(45deg,
            color-mix(in srgb, var(--black-color) 20%, transparent),
            color-mix(in srgb, var(--black-color) 20%, transparent) 1px,
            color-mix(in srgb, var(--black-color) 30%, transparent) 1px,
            color-mix(in srgb, var(--black-color) 30%, transparent) 20px);
        backdrop-filter: blur(3px);
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
      }
    }
  }

  body:has(.navbar-collapse.show) {
    overflow-y: hidden;
  }
</style>
<div style="height: 18dvh;">
</div>
<nav class="container-full row">
  <div class="col"></div>
  <div class="col-xxl-3 col-lg-5 p-0 nav-container">
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <div class="navbar-more d-flex justify-content-around flex-column mb-3">
        <a class="nav-link" href="<?= BASEURL ?>/Rekening/"><i class="fas fa-wallet"></i>&nbsp;&nbsp; Rekening</a>
        <a class="nav-link" href="<?= BASEURL ?>/Report/"><i class="fas fa-file-invoice"></i>&nbsp;&nbsp; Laporan & Evaluasi</a>
        <a class="nav-link" href="<?= BASEURL ?>/Users/"><i class="fas fa-user-shield"></i>&nbsp;&nbsp; Keamanan</a>
        <a class="nav-link" href="<?= BASEURL ?>/Database/"><i class="fas fa-server"></i>&nbsp;&nbsp; Basis Data</a>
        <hr style="border: 2px solid var(--white-color);">
        <a class="nav-link" href="<?= BASEURL ?>/Auth/logout"><i class="fas fa-sign-out-alt"></i>&nbsp;&nbsp; Log Out</a>
      </div>
      <div style="height: 15dvh;"></div>
    </div>
    <div class="navbar navbar-light">
      <!-- Toggle button -->
      <a href="<?= BASEURL ?>/Transaction" class="nav-link my-tooltip" data-tooltip="Dashboard"><i class="fas fa-chart-pie"></i></a>
      <div class="nav-link">
        <a href="<?= BASEURL ?>/Record" class="special my-tooltip" data-tooltip="Transaksi"><i class="fas fa-money-bill-wave"></i></a>
      </div>
      <button class="nav-link my-tooltip" data-tooltip="Menu" type="button"
        data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
        <i class="fas fa-bars"></i>
      </button>
      <!-- Toggle button -->
    </div>
  </div>
  <div class="col"></div>
</nav>
<script>
  document.addEventListener('DOMContentLoaded', e => {
    const Menu = document.querySelector('.navbar-collapse')
    Menu.addEventListener('click', function(event) {
      var rect = Menu.getBoundingClientRect();
      var isInDialog = (rect.top <= event.clientY && event.clientY <= rect.top + rect.height &&
        rect.left <= event.clientX && event.clientX <= rect.left + rect.width);
      if (!isInDialog) {
        bootstrap.Collapse.getInstance(Menu).hide()
      }
    })
  })
</script>