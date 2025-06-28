<style>
  .header {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    img {
      width: 15dvw;

      @media (orientation: portrait) {
        width: 40dvw;
      }

      /* height: 10dvh; */
    }
  }
</style>
<div class="container">
  <div class="header">
    <img src="<?= BASEURL ?>/assets/img/Logo.png" class="">
    <span>
      <h4 class="text-center text-secondary fw-bold"><?= $data['subTitle'] ?? '' ?></h4>
    </span>
  </div>
</div>