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
<title>السجل - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand"><span>🔥</span><span>Cal<span style="color:var(--secondary)">Track</span></span></div>
  <ul class="navbar-nav">
    <li><a href="index.php">🏠 الرئيسية</a></li>
    <li><a href="camera.php">📸 تصوير الطعام</a></li>
    <li><a href="history.php" class="active">📅 السجل</a></li>
    <li><a href="reports.php">📊 التقارير</a></li>
    <?php if (isAdmin()): ?>
    <li><a href="admin/index.php">⚙️ الإدارة</a></li>
    <?php endif; ?>
  </ul>
  <div class="navbar-user">
    <div class="avatar"><?= mb_substr($userName,0,1) ?></div>
    <span><?= htmlspecialchars($userName) ?></span>
    <button class="btn btn-secondary btn-sm" onclick="logout()">خروج</button>
  </div>
</nav>

<div class="container" id="main-content" style="opacity:0">
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <div class="page-title">📅 سجل السعرات الحرارية</div>
      <div class="page-subtitle">تتبع وجباتك عبر الأيام</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <select id="range-select" class="form-control" style="width:auto" onchange="loadHistory()">
        <option value="7">آخر 7 أيام</option>
        <option value="14">آخر 14 يوم</option>
        <option value="30" selected>آخر 30 يوم</option>
        <option value="90">آخر 3 أشهر</option>
      </select>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="stats-grid" id="summary-stats" style="margin-bottom:1.5rem">
    <div class="stat-card" style="--accent:var(--primary)">
      <div class="stat-icon">📊</div>
      <div class="stat-value" id="avg-cal">-</div>
      <div class="stat-label">متوسط يومي (كالوري)</div>
    </div>
    <div class="stat-card" style="--accent:var(--success)">
      <div class="stat-icon">✅</div>
      <div class="stat-value" id="days-goal">-</div>
      <div class="stat-label">أيام تحقق الهدف</div>
    </div>
    <div class="stat-card" style="--accent:var(--warning)">
      <div class="stat-icon">📅</div>
      <div class="stat-value" id="days-logged">-</div>
      <div class="stat-label">أيام مسجلة</div>
    </div>
    <div class="stat-card" style="--accent:var(--secondary)">
      <div class="stat-icon">🏆</div>
      <div class="stat-value" id="max-day">-</div>
      <div class="stat-label">أعلى يوم (كالوري)</div>
    </div>
  </div>

  <!-- Chart -->
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-title">📈 مخطط السعرات</div>
    <div style="position:relative">
      <div class="chart-bar-wrap" id="history-chart" style="height:160px;overflow-x:auto;gap:3px;padding-bottom:4px;align-items:flex-end"></div>
      <div style="text-align:center;margin-top:8px;font-size:0.8rem;color:var(--text-muted)">
        الخط المتقطع = الهدف اليومي (<?= $dailyGoal ?> كالوري)
      </div>
    </div>
  </div>

  <!-- Daily History Table -->
  <div class="card">
    <div class="card-title">📋 تفاصيل يومية</div>
    <div class="table-wrap">
      <table id="history-table">
        <thead>
          <tr>
            <th>التاريخ</th>
            <th>السعرات</th>
            <th>بروتين</th>
            <th>كارب</th>
            <th>دهون</th>
            <th>الحالة</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="history-body">
          <tr><td colspan="7" style="text-align:center;padding:2rem"><div class="spinner" style="margin:auto"></div></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Day Detail Modal -->
<div class="modal-overlay" id="day-modal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title" id="day-modal-title">تفاصيل اليوم</div>
      <button class="modal-close" onclick="closeDayModal()">✕</button>
    </div>
    <div id="day-modal-content"></div>
  </div>
</div>

<script>
const DAILY_GOAL = <?= $dailyGoal ?>;

async function loadHistory() {
  const days = parseInt(document.getElementById('range-select').value);
  const res  = await fetch(`api/food.php?action=history&days=${days}`);
  const data = await res.json();
  if (!data.success) return;

  const rows = data.data;

  // Summary
  if (rows.length > 0) {
    const avg = Math.round(rows.reduce((s,r) => s + parseInt(r.total_calories), 0) / rows.length);
    const daysGoal = rows.filter(r => parseInt(r.total_calories) <= DAILY_GOAL).length;
    const maxCal   = Math.max(...rows.map(r => parseInt(r.total_calories)));
    document.getElementById('avg-cal').textContent  = avg.toLocaleString();
    document.getElementById('days-goal').textContent = daysGoal;
    document.getElementById('days-logged').textContent = rows.length;
    document.getElementById('max-day').textContent  = maxCal.toLocaleString();
  }

  // Chart
  const chart = document.getElementById('history-chart');
  const maxCal = Math.max(...rows.map(r => parseInt(r.total_calories)), DAILY_GOAL);
  chart.innerHTML = rows.map(r => {
    const h   = Math.max(4, (parseInt(r.total_calories) / maxCal) * 140);
    const over = parseInt(r.total_calories) > DAILY_GOAL;
    const date  = new Date(r.log_date + 'T00:00:00');
    const label = `${date.getDate()}/${date.getMonth()+1}`;
    return `<div class="chart-bar-col" style="min-width:24px" title="${r.log_date}: ${r.total_calories} كالوري">
      <div class="chart-bar" style="height:${h}px;background:${over ? 'linear-gradient(to top,var(--danger),var(--secondary))' : 'linear-gradient(to top,var(--primary),var(--success))'}"></div>
      <div class="chart-bar-label" style="font-size:0.65rem">${label}</div>
    </div>`;
  }).join('');

  // Table
  const tbody = document.getElementById('history-body');
  if (rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">لا توجد بيانات في هذه الفترة</td></tr>`;
    return;
  }

  tbody.innerHTML = [...rows].reverse().map(r => {
    const over = parseInt(r.total_calories) > DAILY_GOAL;
    const diff = parseInt(r.total_calories) - DAILY_GOAL;
    return `<tr style="animation:fadeIn 0.3s">
      <td><strong>${formatDate(r.log_date)}</strong></td>
      <td><strong style="color:${over ? 'var(--danger)' : 'var(--success)'}">${parseInt(r.total_calories).toLocaleString()}</strong></td>
      <td>${parseFloat(r.total_protein).toFixed(1)}g</td>
      <td>${parseFloat(r.total_carbs).toFixed(1)}g</td>
      <td>${parseFloat(r.total_fat).toFixed(1)}g</td>
      <td>
        ${over
          ? `<span class="badge badge-danger">▲ ${diff.toLocaleString()} فوق الهدف</span>`
          : `<span class="badge badge-success">✓ ضمن الهدف</span>`}
      </td>
      <td><button class="btn btn-sm btn-secondary" onclick="loadDayDetail('${r.log_date}')">عرض</button></td>
    </tr>`;
  }).join('');
}

async function loadDayDetail(date) {
  document.getElementById('day-modal-title').textContent = '📅 ' + formatDate(date);
  document.getElementById('day-modal-content').innerHTML = '<div class="spinner" style="margin:2rem auto"></div>';
  document.getElementById('day-modal').classList.add('open');

  const res  = await fetch(`api/food.php?action=today&date=${date}`);
  const data = await res.json();
  if (!data.success) return;

  const { logs, totals } = data;
  const icons = { breakfast:'🌅', lunch:'☀️', dinner:'🌙', snack:'🍎' };

  document.getElementById('day-modal-content').innerHTML = `
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;margin-bottom:1.5rem">
      <div class="macro-pill"><div class="macro-value" style="color:var(--primary)">${totals.calories}</div><div class="macro-label">كالوري</div></div>
      <div class="macro-pill"><div class="macro-value" style="color:var(--warning)">${parseFloat(totals.protein).toFixed(1)}g</div><div class="macro-label">بروتين</div></div>
      <div class="macro-pill"><div class="macro-value" style="color:var(--secondary)">${parseFloat(totals.carbs).toFixed(1)}g</div><div class="macro-label">كارب</div></div>
      <div class="macro-pill"><div class="macro-value" style="color:var(--success)">${parseFloat(totals.fat).toFixed(1)}g</div><div class="macro-label">دهون</div></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      ${logs.map(log => `
        <div class="food-item">
          <div class="food-item-icon">${icons[log.meal_type]||'🍽️'}</div>
          <div class="food-item-info">
            <div class="food-item-name">${escHtml(log.food_name)}</div>
            <div class="food-item-meta">🥩 ${log.protein}g &nbsp; 🍞 ${log.carbs}g &nbsp; 🧈 ${log.fat}g</div>
          </div>
          <div class="food-item-cal">${log.calories}</div>
        </div>
      `).join('')}
    </div>
  `;
}

function closeDayModal() { document.getElementById('day-modal').classList.remove('open'); }

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  const days   = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
  const months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  return `${days[d.getDay()]} ${d.getDate()} ${months[d.getMonth()]}`;
}

function escHtml(str) {
  return String(str||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('api/auth.php', {method:'POST', body:fd});
  location.href = 'login.php';
}

window.addEventListener('load', () => {
  const main = document.getElementById('main-content');
  main.style.opacity = '1';
  if (window.Motion) {
    window.Motion.animate('#main-content', { opacity:[0,1], y:[20,0] }, { duration:0.5, easing:[0.4,0,0.2,1] });
    window.Motion.animate('.stat-card', { opacity:[0,1], scale:[0.95,1] },
      { duration:0.4, delay:window.Motion.stagger(0.08), easing:[0.4,0,0.2,1] });
  }
  loadHistory();
});

// Add CSS for fadeIn
const style = document.createElement('style');
style.textContent = '@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }';
document.head.appendChild(style);
</script>
<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js"></script>
</body>
</html>
