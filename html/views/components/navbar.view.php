<style>
  nav {

    /* // margin-bottom: 2rem; */
    /* // min-height: var(--nav-min-height) !important; */
    .nav-container {
      position: relative;
    }

    .navbar {
      --nav-min-height: 6rem;
      --nav-background-color: var(--primary-color);
      /* --nav-background-color: var(--primary-color); */
      /* --nav-background-color: color-mix(in srgb, var(--primary-color) 50%, transparent); */
      --nav-box-shadow: 0px -2px 6px 5px rgba(var(--black-rgb), 0.3);
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
        flex: 1 1 auto;
        font-weight: 900;
        border: none;
        font-size: 1.75rem;
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
          font-size: 3.5rem;
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
          transition: all 0.2s;

          &:focus,
          &:active,
          &:hover {
            /* padding: 2.5rem; */
            font-size: 4rem;
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
          font-size: 2rem;
        }

      }
    }

    .navbar-collapse {
      box-shadow: 0px -5px 4px 3px rgba(var(--black-rgb), 0.3);
      border-radius: 5vh 5vh 0 0;
      bottom: 0;
      left: 0;
      padding-top: 2vh;
      position: absolute;
      width: 100%;
      background: var(--secondary-color);

      .navbar-more {

        /* min-height: 40dvh; */
        .nav-link {
          padding-block: 1rem;
          color: var(--white-color);
          font-weight: 500;

          &:hover,
          &:active,
          &:focus {
            font-weight: 700;
            border-block: 5px solid;
            border-image-source: linear-gradient(to left,
                transparent 20%,
                var(--white-color) 50%,
                transparent 80%);
            border-image-slice: 1;
            /* Ensures the gradient spans the entire border */
            /* background-color: var(--primary-color); */
          }
        }
      }
    }

  }
</style>
<div style="height: 18dvh;">
</div>
<nav class="container-full row fixed-bottom">
  <div class="col"></div>
  <div class="col-xxl-3 col-lg-5 p-0 nav-container">
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <div class="navbar-more d-flex px-5 justify-content-around flex-column mb-3">
        <a class="nav-link" href="<?= BASEURL ?>/Rekening/"><i class="fas fa-wallet"></i>&nbsp;&nbsp; Rekening</a>
        <a class="nav-link" href="<?= BASEURL ?>/Report/"><i class="fas fa-file-invoice"></i>&nbsp;&nbsp; Laporan</a>
        <a class="nav-link" href="<?= BASEURL ?>/Database/"><i class="fas fa-server"></i>&nbsp;&nbsp; Basis Data</a>
        <a class="nav-link" href="<?= BASEURL ?>/Users/"><i class="fas fa-users-cog"></i>&nbsp;&nbsp; Pengguna</a>
        <hr style="border: 2px solid var(--white-color);">
        <a class="nav-link" href="<?= BASEURL ?>/Auth/logout"><i class="fas fa-sign-out-alt"></i>&nbsp;&nbsp; Log Out</a>
      </div>
      <div style="height: 15dvh;"></div>
    </div>
    <div class="navbar navbar-light">
      <!-- Toggle button -->
      <a href="<?= BASEURL ?>\Main" class="nav-link my-tooltip" data-tooltip="Dashboard"><i class="fas fa-chart-pie"></i></a>
      <div class="nav-link">
        <a href="<?= BASEURL ?>\Record" class="special my-tooltip" data-tooltip="Transaksi"><i class="fas fa-money-bill-wave"></i></a>
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