<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$userName  = $_SESSION['user_name'];
$dailyGoal = $_SESSION['daily_goal'] ?? 2000;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-brand">
    <i class="bi bi-fire"></i>
    <span>Cal<span style="color:var(--secondary)">Track</span></span>
  </div>
  <ul class="navbar-nav">
    <li><a href="index.php" class="active"><i class="bi bi-house-fill"></i> الرئيسية</a></li>
    <li><a href="camera.php"><i class="bi bi-camera-fill"></i> تصوير</a></li>
    <li><a href="workout.php"><i class="bi bi-lightning-fill"></i> التمارين</a></li>
    <li><a href="history.php"><i class="bi bi-calendar3"></i> السجل</a></li>
    <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> التقارير</a></li>
    <li><a href="profile.php"><i class="bi bi-person-fill"></i> ملفي</a></li>
    <?php if (isAdmin()): ?>
    <li><a href="admin/index.php"><i class="bi bi-gear-fill"></i> الإدارة</a></li>
    <?php endif; ?>
  </ul>
  <div class="navbar-user">
    <div class="avatar"><?= mb_substr($userName,0,1) ?></div>
    <span><?= htmlspecialchars($userName) ?></span>
    <button class="btn btn-secondary btn-sm" onclick="logout()">خروج</button>
  </div>
</nav>

<div class="container" id="main-content" style="opacity:0">
  <!-- Header -->
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <div class="page-title">مرحباً، <?= htmlspecialchars($userName) ?></div>
      <div class="page-subtitle" id="date-label">جاري التحميل...</div>
    </div>
    <div style="display:flex;gap:10px">
      <button class="btn btn-primary" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> إضافة وجبة</button>
      <a href="camera.php" class="btn btn-secondary"><i class="bi bi-camera-fill"></i> تصوير</a>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="stats-grid" id="stats-grid">
    <div class="stat-card" style="--accent:var(--primary)">
      <div class="stat-icon" style="background:rgba(108,99,255,0.15)"><i class="bi bi-fire"></i></div>
      <div class="stat-value" id="stat-consumed">0</div>
      <div class="stat-label">سعرات مُستهلكة</div>
    </div>
    <div class="stat-card" style="--accent:var(--success)">
      <div class="stat-icon" style="background:rgba(67,217,143,0.15)"><i class="bi bi-bullseye"></i></div>
      <div class="stat-value" id="stat-remaining">0</div>
      <div class="stat-label">متبقي اليوم</div>
    </div>
    <div class="stat-card" style="--accent:var(--warning)">
      <div class="stat-icon" style="background:rgba(255,179,71,0.15)"><i class="bi bi-lightning-fill"></i></div>
      <div class="stat-value" id="stat-protein">0g</div>
      <div class="stat-label">بروتين</div>
    </div>
    <div class="stat-card" style="--accent:var(--secondary)">
      <div class="stat-icon" style="background:rgba(255,101,132,0.15)"><i class="bi bi-layers-fill"></i></div>
      <div class="stat-value" id="stat-carbs">0g</div>
      <div class="stat-label">كربوهيدرات</div>
    </div>
  </div>

  <div class="grid-2" style="margin-bottom:1.5rem">
    <!-- Progress Ring -->
    <div class="card" style="display:flex;flex-direction:column;align-items:center;gap:1rem">
      <div class="card-title"><i class="bi bi-bullseye"></i> تقدم اليوم</div>
      <div style="position:relative;display:inline-flex">
        <svg class="progress-ring-svg" width="160" height="160" viewBox="0 0 160 160">
          <defs>
            <linearGradient id="ringGradient" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#6C63FF"/>
              <stop offset="100%" stop-color="#FF6584"/>
            </linearGradient>
          </defs>
          <circle class="progress-ring-bg" cx="80" cy="80" r="68"/>
          <circle class="progress-ring-fill" id="ring-fill" cx="80" cy="80" r="68"
            stroke-dasharray="427" stroke-dashoffset="427"/>
        </svg>
        <div class="ring-center" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
          <div class="ring-value" id="ring-pct">0%</div>
          <div class="ring-label">من الهدف</div>
        </div>
      </div>
      <div style="text-align:center;color:var(--text-muted);font-size:0.85rem">
        الهدف: <strong style="color:var(--text)"><?= $dailyGoal ?></strong> كالوري
      </div>
    </div>

    <!-- 7-day chart -->
    <div class="card">
      <div class="card-title"><i class="bi bi-graph-up-arrow"></i> السعرات - آخر 7 أيام</div>
      <div class="chart-bar-wrap" id="week-chart"></div>
      <div style="display:flex;justify-content:center;gap:12px;margin-top:8px;font-size:0.8rem;color:var(--text-muted)">
        <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;border-radius:2px;background:var(--primary);display:inline-block"></span>السعرات</span>
        <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:1px;border-top:2px dashed var(--warning);display:inline-block"></span>الهدف</span>
      </div>
    </div>
  </div>

  <!-- Today's Meals -->
  <div class="card">
    <div class="card-title" style="justify-content:space-between">
      <span><i class="bi bi-egg-fried"></i> وجبات اليوم</span>
      <input type="date" id="date-picker" class="form-control" style="width:auto;padding:6px 12px;font-size:0.85rem"
        value="<?= date('Y-m-d') ?>" onchange="loadDay(this.value)">
    </div>
    <div id="meals-list" style="display:flex;flex-direction:column;gap:8px;margin-top:0.5rem">
      <div style="text-align:center;padding:2rem;color:var(--text-muted)">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</div>

<!-- Add Meal Modal -->
<div class="modal-overlay" id="add-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-plus-lg"></i> إضافة وجبة يدوياً</div>
      <button class="modal-close" onclick="closeAddModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="modal-alert"></div>
    <form id="add-form">
      <div class="form-group">
        <label class="form-label">اسم الطعام</label>
        <input type="text" name="food_name" class="form-control" placeholder="مثال: شاورما دجاج" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">السعرات <i class="bi bi-fire"></i></label>
          <input type="number" name="calories" class="form-control" placeholder="350" min="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">نوع الوجبة</label>
          <select name="meal_type" class="form-control">
            <option value="breakfast">فطار</option>
            <option value="lunch">غداء</option>
            <option value="dinner">عشاء</option>
            <option value="snack" selected>سناك</option>
          </select>
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">بروتين (g)</label>
          <input type="number" name="protein" class="form-control" placeholder="0" step="0.1" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">كارب (g)</label>
          <input type="number" name="carbs" class="form-control" placeholder="0" step="0.1" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">دهون (g)</label>
          <input type="number" name="fat" class="form-control" placeholder="0" step="0.1" min="0">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">التاريخ</label>
        <input type="date" name="log_date" class="form-control" id="modal-date">
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1"><i class="bi bi-floppy-fill"></i> حفظ</button>
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">إلغاء</button>
      </div>
    </form>
  </div>
</div>

<script>
const DAILY_GOAL = <?= $dailyGoal ?>;
let currentDate = new Date().toISOString().split('T')[0];

const days = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
const months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
const now = new Date();
document.getElementById('date-label').textContent =
  `${days[now.getDay()]}، ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;

async function loadDay(date) {
  currentDate = date;
  document.getElementById('date-picker').value = date;
  const res  = await fetch(`api/food.php?action=today&date=${date}`);
  const data = await res.json();
  if (!data.success) return;

  const { logs, totals } = data;

  const remaining = Math.max(0, DAILY_GOAL - totals.calories);
  animateNumber('stat-consumed', totals.calories);
  animateNumber('stat-remaining', remaining);
  document.getElementById('stat-protein').textContent = totals.protein.toFixed(1) + 'g';
  document.getElementById('stat-carbs').textContent   = totals.carbs.toFixed(1) + 'g';

  const pct = Math.min(1, totals.calories / DAILY_GOAL);
  const circ = 427;
  document.getElementById('ring-fill').style.strokeDashoffset = circ - (circ * pct);
  document.getElementById('ring-pct').textContent = Math.round(pct * 100) + '%';

  const list = document.getElementById('meals-list');
  if (logs.length === 0) {
    list.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--text-muted)">
      <div style="font-size:2.5rem;margin-bottom:8px"><i class="bi bi-egg-fried"></i></div>
      <div>لم تسجل وجبات بعد اليوم</div>
      <div style="font-size:0.85rem;margin-top:4px">ابدأ بإضافة وجبتك الأولى!</div>
    </div>`;
    return;
  }

  const mealIcons = {
    breakfast:'<i class="bi bi-sunrise"></i>',
    lunch:'<i class="bi bi-sun-fill"></i>',
    dinner:'<i class="bi bi-moon-fill"></i>',
    snack:'<i class="bi bi-apple"></i>'
  };
  list.innerHTML = logs.map(log => `
    <div class="food-item" id="food-${log.id}" style="opacity:0;transform:translateX(20px)">
      <div class="food-item-icon">${mealIcons[log.meal_type] || '<i class="bi bi-egg-fried"></i>'}</div>
      <div class="food-item-info">
        <div class="food-item-name">${escHtml(log.food_name)}</div>
        <div class="food-item-meta">
          <i class="bi bi-lightning-fill"></i> ${log.protein}g &nbsp;
          <i class="bi bi-layers-fill"></i> ${log.carbs}g &nbsp;
          <i class="bi bi-droplet-fill"></i> ${log.fat}g
          &nbsp;|&nbsp; ${log.meal_type === 'breakfast' ? 'فطار' : log.meal_type === 'lunch' ? 'غداء' : log.meal_type === 'dinner' ? 'عشاء' : 'سناك'}
        </div>
      </div>
      <div class="food-item-cal">${log.calories}</div>
      <button class="food-item-del" onclick="deleteLog(${log.id})" title="حذف"><i class="bi bi-trash3-fill"></i></button>
    </div>
  `).join('');

  document.querySelectorAll('.food-item').forEach((el, i) => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.4s, transform 0.4s';
      el.style.opacity = '1';
      el.style.transform = 'translateX(0)';
    }, i * 60);
  });
}

async function loadWeekChart() {
  const res  = await fetch('api/food.php?action=history&days=7');
  const data = await res.json();
  if (!data.success) return;

  const chart = document.getElementById('week-chart');
  const maxCal = Math.max(...data.data.map(d => d.total_calories), DAILY_GOAL);
  const dayNames = ['أح','إث','ث','أر','خ','ج','س'];

  const map = {};
  data.data.forEach(d => { map[d.log_date] = d.total_calories; });

  const bars = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const key = d.toISOString().split('T')[0];
    bars.push({ label: dayNames[d.getDay()], cal: map[key] || 0 });
  }

  chart.innerHTML = bars.map(b => {
    const h = Math.max(4, (b.cal / maxCal) * 100);
    return `<div class="chart-bar-col">
      <div style="font-size:0.7rem;color:var(--primary);font-weight:700">${b.cal||''}</div>
      <div class="chart-bar" style="height:${h}%" title="${b.cal} كالوري"></div>
      <div class="chart-bar-label">${b.label}</div>
    </div>`;
  }).join('');
}

async function deleteLog(id) {
  if (!confirm('حذف هذا الطعام؟')) return;
  const fd = new FormData();
  fd.append('action','delete'); fd.append('id', id);
  await fetch('api/food.php', {method:'POST', body:fd});
  const el = document.getElementById('food-' + id);
  if (el) { el.style.opacity='0'; el.style.transform='translateX(20px)'; setTimeout(()=>el.remove(),300); }
  loadDay(currentDate);
}

function openAddModal() {
  document.getElementById('modal-date').value = currentDate;
  document.getElementById('modal-alert').innerHTML = '';
  document.getElementById('add-modal').classList.add('open');
}

function closeAddModal() {
  document.getElementById('add-modal').classList.remove('open');
  document.getElementById('add-form').reset();
}

document.getElementById('add-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','add');
  const res  = await fetch('api/food.php', {method:'POST', body:fd});
  const data = await res.json();
  if (data.success) {
    closeAddModal();
    loadDay(currentDate);
    loadWeekChart();
  } else {
    document.getElementById('modal-alert').innerHTML =
      `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
});

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('api/auth.php', {method:'POST', body:fd});
  location.href = 'login.php';
}

function animateNumber(id, target) {
  const el = document.getElementById(id);
  const start = parseInt(el.textContent) || 0;
  const duration = 600;
  const startTime = performance.now();
  const update = (now) => {
    const p = Math.min(1, (now - startTime) / duration);
    el.textContent = Math.round(start + (target - start) * p);
    if (p < 1) requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}

function escHtml(str) {
  return str.replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])
  );
}

window.addEventListener('load', () => {
  const main = document.getElementById('main-content');
  main.style.opacity = '1';
  if (window.Motion) {
    window.Motion.animate('#main-content',
      { opacity: [0,1], y: [20,0] },
      { duration: 0.5, easing: [0.4,0,0.2,1] }
    );
    window.Motion.animate('#stats-grid .stat-card',
      { opacity: [0,1], y: [20,0] },
      { duration: 0.4, delay: window.Motion.stagger(0.1), easing: [0.4,0,0.2,1] }
    );
  }
  loadDay(currentDate);
  loadWeekChart();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js"></script>
</body>
</html>
