<?php
$baseUrl = BASEURL . '/Main';

$links = [
  'back' => ['url' => $baseUrl, 'icon' => 'flip fa-arrow-right-from-bracket', 'tooltip' => 'Kembali'],
  'qris' => ['url' => $baseUrl . '/Qris', 'icon' => 'fa-qrcode', 'tooltip' => 'Qris'],
  'graph' => ['url' => $baseUrl . '/Graph', 'icon' => 'fa-chart-line', 'tooltip' => 'Grafik'],
  'setting' => ['url' => $baseUrl . '/Setting', 'icon' => 'fa-bars', 'tooltip' => 'Menu'],
];
?>
<div class="menu d-flex flex-row justify-content-end">

  <?php $link = (isset($data['navPage']) && $data['navPage'] == 'graph') ? $links['back'] : $links['graph'] ?>
  <a href="<?= $link['url'] ?>" class="my-tooltip" data-tooltip="<?= $link['tooltip'] ?>">
    <i class="fa-solid <?= $link['icon'] ?>"></i>
  </a>

  <?php $link = (isset($data['navPage']) && $data['navPage'] == 'qris') ? $links['back'] : $links['qris'] ?>
  <a href="<?= $link['url'] ?>" class="my-tooltip" data-tooltip="<?= $link['tooltip'] ?>">
    <i class="fa-solid <?= $link['icon'] ?>"></i>
  </a>

  <?php $link = (isset($data['navPage']) && $data['navPage'] == 'setting') ? $links['back'] : $links['setting'] ?>
  <a href="<?= $link['url'] ?>" class="ms-auto my-tooltip" data-tooltip="<?= $link['tooltip'] ?>">
    <i class="fa-solid <?= $link['icon'] ?>"></i>
  </a>
</div>