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
<title>التقارير - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.report-card { background: var(--bg-card2); border:1px solid var(--border); border-radius:14px; padding:1.25rem; transition: var(--transition); }
.report-card:hover { border-color:var(--primary); transform:translateY(-2px); }
.trend-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700; }
.trend-up    { background:rgba(255,87,87,0.15); color:var(--danger); }
.trend-down  { background:rgba(67,217,143,0.15); color:var(--success); }
.trend-stable{ background:rgba(136,136,170,0.15); color:var(--text-muted); }
.progress-bar-wrap { height:8px;background:var(--border);border-radius:4px;overflow:hidden;margin-top:8px; }
.progress-bar-fill { height:100%;border-radius:4px;transition:width 1s cubic-bezier(0.4,0,0.2,1); }
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand"><i class="bi bi-fire"></i><span>Cal<span style="color:var(--secondary)">Track</span></span></div>
  <ul class="navbar-nav">
    <li><a href="index.php"><i class="bi bi-house-fill"></i> الرئيسية</a></li>
    <li><a href="camera.php"><i class="bi bi-camera-fill"></i> تصوير الطعام</a></li>
    <li><a href="history.php"><i class="bi bi-calendar3"></i> السجل</a></li>
    <li><a href="reports.php" class="active"><i class="bi bi-bar-chart-fill"></i> التقارير</a></li>
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
  <div class="page-header">
    <div class="page-title"><i class="bi bi-bar-chart-fill"></i> التقارير والتحليلات</div>
    <div class="page-subtitle">تتبع تطورك الأسبوعي والشهري</div>
  </div>

  <!-- View Tabs -->
  <div class="tabs" style="max-width:360px;margin-bottom:1.5rem">
    <button class="tab-btn active" onclick="showView('weekly')"><i class="bi bi-calendar3"></i> أسبوعي</button>
    <button class="tab-btn" onclick="showView('monthly')"><i class="bi bi-calendar-month"></i> شهري</button>
  </div>

  <!-- Weekly View -->
  <div id="view-weekly">
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-title"><i class="bi bi-calendar3"></i> التقرير الأسبوعي - آخر 12 أسبوع</div>
      <div id="weekly-loading" style="text-align:center;padding:2rem"><div class="spinner" style="margin:auto"></div></div>
      <div id="weekly-content" style="display:none">
        <div id="weekly-chart" class="chart-bar-wrap" style="height:180px;margin-bottom:1.5rem;gap:6px;align-items:flex-end"></div>
        <div id="weekly-cards" style="display:flex;flex-direction:column;gap:10px"></div>
      </div>
    </div>
  </div>

  <!-- Monthly View -->
  <div id="view-monthly" style="display:none">
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-title"><i class="bi bi-calendar-month"></i> التقرير الشهري - آخر 12 شهر</div>
      <div id="monthly-loading" style="text-align:center;padding:2rem"><div class="spinner" style="margin:auto"></div></div>
      <div id="monthly-content" style="display:none">
        <div id="monthly-chart" class="chart-bar-wrap" style="height:180px;margin-bottom:1.5rem;gap:8px;align-items:flex-end"></div>
        <div id="monthly-cards" style="display:flex;flex-direction:column;gap:10px"></div>
      </div>
    </div>
  </div>

  <!-- Insights Card -->
  <div class="card" id="insights-card" style="display:none">
    <div class="card-title"><i class="bi bi-lightbulb-fill"></i> تحليل ذكي</div>
    <div id="insights-content" style="display:flex;flex-direction:column;gap:10px"></div>
  </div>
</div>

<script>
const DAILY_GOAL = <?= $dailyGoal ?>;
let weeklyData = null;
let monthlyData = null;

function showView(view) {
  document.getElementById('view-weekly').style.display  = view === 'weekly'  ? '' : 'none';
  document.getElementById('view-monthly').style.display = view === 'monthly' ? '' : 'none';
  document.querySelectorAll('.tabs .tab-btn').forEach((b,i) =>
    b.classList.toggle('active', (i===0&&view==='weekly')||(i===1&&view==='monthly'))
  );
  if (view === 'weekly' && !weeklyData) loadWeekly();
  if (view === 'monthly' && !monthlyData) loadMonthly();
}

async function loadWeekly() {
  const res  = await fetch('api/food.php?action=weekly_report');
  const data = await res.json();
  if (!data.success) return;
  weeklyData = data.weeks;

  document.getElementById('weekly-loading').style.display = 'none';
  document.getElementById('weekly-content').style.display = '';

  if (weeklyData.length === 0) {
    document.getElementById('weekly-content').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted)">لا توجد بيانات كافية بعد</div>';
    return;
  }

  const maxAvg = Math.max(...weeklyData.map(w => parseFloat(w.avg_calories)), DAILY_GOAL);

  const chart = document.getElementById('weekly-chart');
  chart.innerHTML = weeklyData.map(w => {
    const h    = Math.max(4, (parseFloat(w.avg_calories) / maxAvg) * 160);
    const over = parseFloat(w.avg_calories) > DAILY_GOAL;
    const label = `${new Date(w.week_start+'T00:00:00').getDate()}/${new Date(w.week_start+'T00:00:00').getMonth()+1}`;
    return `<div class="chart-bar-col" style="min-width:36px" title="أسبوع ${label}: ${Math.round(w.avg_calories)} كالوري">
      <div style="font-size:0.65rem;color:var(--text-muted)">${Math.round(w.avg_calories)}</div>
      <div class="chart-bar" style="height:${h}px;background:${over ? 'linear-gradient(to top,var(--danger),var(--secondary))' : 'linear-gradient(to top,var(--primary),var(--success))'}"></div>
      <div class="chart-bar-label">${label}</div>
    </div>`;
  }).join('');

  const cards = document.getElementById('weekly-cards');
  cards.innerHTML = [...weeklyData].reverse().map((w, i) => {
    const trend = w.trend || 'stable';
    const trendIcon = trend === 'up' ? '<i class="bi bi-arrow-up"></i>' : trend === 'down' ? '<i class="bi bi-arrow-down"></i>' : '<i class="bi bi-dash"></i>';
    const pct  = Math.min(100, Math.round((parseFloat(w.avg_calories) / DAILY_GOAL) * 100));
    const over = parseFloat(w.avg_calories) > DAILY_GOAL;

    return `<div class="report-card" style="animation:fadeIn 0.4s ${i*0.05}s both">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
        <div>
          <div style="font-weight:700">${formatDateAr(w.week_start)} — ${formatDateAr(w.week_end)}</div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px">${w.days_logged} أيام مسجلة</div>
        </div>
        <div style="text-align:left">
          <div style="font-size:1.3rem;font-weight:700;color:${over ? 'var(--danger)' : 'var(--success)'}">${Math.round(w.avg_calories)}</div>
          <div style="font-size:0.75rem;color:var(--text-muted)">متوسط يومي</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:0.85rem;color:var(--text-muted)">${pct}% من الهدف</span>
        ${i < weeklyData.length - 1 && w.change !== 0 ? `
          <span class="trend-badge trend-${trend}">${trendIcon} ${Math.abs(w.change)} كالوري</span>
        ` : '<span class="trend-badge trend-stable"><i class="bi bi-dash"></i> مستقر</span>'}
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:${pct}%;background:${over ? 'var(--danger)' : pct > 85 ? 'var(--warning)' : 'var(--success)'}"></div>
      </div>
    </div>`;
  }).join('');

  generateInsights(weeklyData, 'weekly');
}

async function loadMonthly() {
  const res  = await fetch('api/food.php?action=monthly_report');
  const data = await res.json();
  if (!data.success) return;
  monthlyData = data.months;

  document.getElementById('monthly-loading').style.display = 'none';
  document.getElementById('monthly-content').style.display = '';

  if (monthlyData.length === 0) {
    document.getElementById('monthly-content').innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted)">لا توجد بيانات كافية بعد</div>';
    return;
  }

  const maxAvg = Math.max(...monthlyData.map(m => parseFloat(m.avg_calories)), DAILY_GOAL);

  const chart = document.getElementById('monthly-chart');
  chart.innerHTML = monthlyData.map(m => {
    const h    = Math.max(4, (parseFloat(m.avg_calories) / maxAvg) * 160);
    const over = parseFloat(m.avg_calories) > DAILY_GOAL;
    const shortLabel = m.month_label.substring(0, m.month_label.indexOf(' '));
    return `<div class="chart-bar-col" style="min-width:40px" title="${m.month_label}: ${Math.round(m.avg_calories)} كالوري">
      <div style="font-size:0.65rem;color:var(--text-muted)">${Math.round(m.avg_calories)}</div>
      <div class="chart-bar" style="height:${h}px;background:${over ? 'linear-gradient(to top,var(--danger),var(--secondary))' : 'linear-gradient(to top,var(--primary),var(--success))'}"></div>
      <div class="chart-bar-label">${shortLabel}</div>
    </div>`;
  }).join('');

  const cards = document.getElementById('monthly-cards');
  cards.innerHTML = [...monthlyData].reverse().map((m, i) => {
    const trend = m.trend || 'stable';
    const trendIcon = trend === 'up' ? '<i class="bi bi-arrow-up"></i>' : trend === 'down' ? '<i class="bi bi-arrow-down"></i>' : '<i class="bi bi-dash"></i>';
    const pct  = Math.min(120, Math.round((parseFloat(m.avg_calories) / DAILY_GOAL) * 100));
    const over = parseFloat(m.avg_calories) > DAILY_GOAL;
    const goalDiff = m.goal_diff > 0
      ? `<span style="color:var(--danger)">+${Math.round(m.goal_diff)} فوق الهدف</span>`
      : `<span style="color:var(--success)">${Math.round(Math.abs(m.goal_diff))} تحت الهدف</span>`;

    return `<div class="report-card" style="animation:fadeIn 0.4s ${i*0.05}s both">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
        <div>
          <div style="font-weight:700;font-size:1.05rem">${m.month_label}</div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px">
            ${m.days_logged} أيام مسجلة &nbsp;|&nbsp; ${goalDiff}
          </div>
        </div>
        <div style="text-align:left">
          <div style="font-size:1.4rem;font-weight:700;color:${over ? 'var(--danger)' : 'var(--success)'}">${Math.round(m.avg_calories)}</div>
          <div style="font-size:0.75rem;color:var(--text-muted)">متوسط يومي</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">
        <div style="text-align:center;background:var(--bg-card);border-radius:8px;padding:6px">
          <div style="font-weight:700;color:var(--warning)">${parseFloat(m.total_protein/m.days_logged||0).toFixed(0)}g</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">متوسط بروتين</div>
        </div>
        <div style="text-align:center;background:var(--bg-card);border-radius:8px;padding:6px">
          <div style="font-weight:700;color:var(--secondary)">${parseFloat(m.total_carbs/m.days_logged||0).toFixed(0)}g</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">متوسط كارب</div>
        </div>
        <div style="text-align:center;background:var(--bg-card);border-radius:8px;padding:6px">
          <div style="font-weight:700;color:var(--success)">${parseFloat(m.total_fat/m.days_logged||0).toFixed(0)}g</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">متوسط دهون</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:0.85rem;color:var(--text-muted)">${Math.min(pct,100)}% من الهدف</span>
        ${m.change !== 0 ? `
          <span class="trend-badge trend-${trend}">${trendIcon} ${Math.abs(m.change)} مقارنة بالشهر السابق</span>
        ` : '<span class="trend-badge trend-stable"><i class="bi bi-dash"></i> بداية البيانات</span>'}
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:${Math.min(pct,100)}%;background:${over ? 'var(--danger)' : pct > 85 ? 'var(--warning)' : 'var(--success)'}"></div>
      </div>
    </div>`;
  }).join('');

  generateInsights(monthlyData, 'monthly');
}

function generateInsights(data, type) {
  if (data.length < 2) return;

  const insights = [];
  const latest = data[data.length - 1];
  const prev   = data[data.length - 2];

  const avgCal = parseFloat(latest.avg_calories);
  const diff   = avgCal - parseFloat(prev.avg_calories);

  if (Math.abs(diff) > 50) {
    insights.push({
      icon: diff > 0 ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-stars"></i>',
      color: diff > 0 ? 'var(--warning)' : 'var(--success)',
      text: diff > 0
        ? `سعراتك ارتفعت بمقدار <strong>${Math.round(diff)}</strong> كالوري مقارنة ${type === 'weekly' ? 'بالأسبوع' : 'بالشهر'} السابق`
        : `رائع! سعراتك انخفضت بمقدار <strong>${Math.round(Math.abs(diff))}</strong> كالوري مقارنة ${type === 'weekly' ? 'بالأسبوع' : 'بالشهر'} السابق`
    });
  }

  if (avgCal > DAILY_GOAL * 1.1) {
    insights.push({
      icon: '<i class="bi bi-x-circle-fill" style="color:var(--danger)"></i>', color: 'var(--danger)',
      text: `متوسطك اليومي <strong>${Math.round(avgCal)}</strong> يتجاوز هدفك بنسبة ${Math.round((avgCal/DAILY_GOAL - 1)*100)}% - حاول تقليل حجم الوجبات`
    });
  } else if (avgCal < DAILY_GOAL * 0.7) {
    insights.push({
      icon: '<i class="bi bi-exclamation-circle-fill" style="color:var(--warning)"></i>', color: 'var(--warning)',
      text: `سعراتك منخفضة جداً (${Math.round(avgCal)} كالوري). تأكد من تسجيل جميع وجباتك أو استشر خبير تغذية`
    });
  } else {
    insights.push({
      icon: '<i class="bi bi-check-circle-fill" style="color:var(--success)"></i>', color: 'var(--success)',
      text: `متوسطك اليومي <strong>${Math.round(avgCal)}</strong> قريب من هدفك - استمر على هذا النهج!`
    });
  }

  if (latest.days_logged < 5 && type === 'weekly') {
    insights.push({
      icon: '<i class="bi bi-pencil-square" style="color:var(--primary)"></i>', color: 'var(--primary)',
      text: 'حاول تسجيل وجباتك يومياً للحصول على تقارير أدق'
    });
  }

  const insightsCard = document.getElementById('insights-card');
  insightsCard.style.display = '';
  document.getElementById('insights-content').innerHTML = insights.map(ins => `
    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:var(--bg-card2);border-radius:10px;border:1px solid var(--border)">
      <span style="font-size:1.3rem;flex-shrink:0">${ins.icon}</span>
      <div style="font-size:0.9rem;color:var(--text)">${ins.text}</div>
    </div>
  `).join('');
}

function formatDateAr(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  const months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
  return `${d.getDate()} ${months[d.getMonth()]}`;
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
  }
  loadWeekly();
});

const style = document.createElement('style');
style.textContent = '@keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }';
document.head.appendChild(style);
</script>
<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js"></script>
</body>
</html>
