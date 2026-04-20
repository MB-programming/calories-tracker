<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="mobile-nav" aria-label="التنقل الرئيسي">
  <div class="mobile-nav-inner">
    <a href="/index.php"   class="mobile-nav-item<?= $current==='index.php'   ? ' active':'' ?>">
      <i class="bi bi-house-fill"></i><span>اليوم</span>
    </a>
    <a href="/history.php" class="mobile-nav-item<?= $current==='history.php' ? ' active':'' ?>">
      <i class="bi bi-calendar3"></i><span>السجل</span>
    </a>

    <div class="mobile-nav-center">
      <a href="/camera.php" title="تصوير الطعام"><i class="bi bi-plus-lg"></i></a>
    </div>

    <a href="/workout.php" class="mobile-nav-item<?= $current==='workout.php' ? ' active':'' ?>">
      <i class="bi bi-lightning-fill"></i><span>تمارين</span>
    </a>
    <a href="/profile.php" class="mobile-nav-item<?= $current==='profile.php' ? ' active':'' ?>">
      <i class="bi bi-person-fill"></i><span>ملفي</span>
    </a>
  </div>
</nav>
