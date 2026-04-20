<?php
$current = basename($_SERVER['PHP_SELF']);
$nav_items = [
  ['href' => '/index.php',   'icon' => 'bi-house-fill',     'label' => 'الرئيسية', 'file' => 'index.php'],
  ['href' => '/camera.php',  'icon' => 'bi-camera-fill',    'label' => 'تصوير',    'file' => 'camera.php'],
  ['href' => '/workout.php', 'icon' => 'bi-lightning-fill', 'label' => 'تمارين',   'file' => 'workout.php'],
  ['href' => '/history.php', 'icon' => 'bi-calendar3',      'label' => 'السجل',    'file' => 'history.php'],
  ['href' => '/profile.php', 'icon' => 'bi-person-fill',    'label' => 'ملفي',     'file' => 'profile.php'],
];
?>
<nav class="mobile-nav" aria-label="التنقل الرئيسي">
  <div class="mobile-nav-inner">
    <?php foreach ($nav_items as $item): ?>
      <a href="<?= $item['href'] ?>"
         class="mobile-nav-item<?= $current === $item['file'] ? ' active' : '' ?>"
         aria-label="<?= $item['label'] ?>">
        <i class="bi <?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
