<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$userName = $_SESSION['user_name'];
$todayDow = (int)date('w');
$dayNames = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$todayAr  = $dayNames[$todayDow];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>التمارين - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.ex-card {
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 1rem 1.2rem;
  display: flex; align-items: center; gap: 12px;
  transition: all 0.3s; position: relative;
  animation: slideIn 0.35s ease both;
}
.ex-card:hover { border-color: var(--primary); transform: translateX(-4px); }
.ex-card.done  { border-color: var(--success); background: rgba(67,217,143,0.06); }
.ex-icon {
  width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: #fff;
}
.ex-info { flex: 1; }
.ex-name   { font-weight: 700; font-size: 0.97rem; }
.ex-meta   { font-size: 0.82rem; color: var(--text-muted); margin-top: 3px; }
.ex-last   { font-size: 0.78rem; color: var(--success); margin-top: 2px; }
.ex-badge  {
  background: rgba(108,99,255,0.15); color: var(--primary);
  border-radius: 8px; padding: 4px 10px; font-size: 0.8rem; font-weight: 700;
  white-space: nowrap;
}
.done-check {
  position: absolute; top: 10px; left: 12px;
  background: var(--success); color:#fff;
  border-radius: 50%; width:22px; height:22px;
  display:flex;align-items:center;justify-content:center;
  font-size:0.75rem; font-weight:700;
}
.day-tabs-wrap { overflow-x: auto; padding-bottom: 4px; margin-bottom: 1.2rem; }
.day-tabs { display: flex; gap: 6px; min-width: max-content; }
.day-tab {
  padding: 8px 16px; border-radius: 10px; border: 1.5px solid var(--border);
  background: var(--bg-card2); cursor: pointer; font-family: inherit;
  font-size: 0.88rem; font-weight: 600; color: var(--text-muted);
  transition: all 0.3s; white-space: nowrap;
}
.day-tab.today { border-color: var(--warning); color: var(--warning); }
.day-tab.active { background: var(--primary); border-color: var(--primary); color:#fff; box-shadow: 0 4px 12px rgba(108,99,255,0.4); }
.day-tab .ex-count { font-size:0.75rem; opacity:0.8; }
.workout-progress { margin-bottom: 1.5rem; }
.progress-bar-wrap {
  background: var(--bg-card2); border-radius: 8px;
  height: 10px; overflow: hidden; position: relative;
}
.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--success), #2dd4a0);
  border-radius: 8px;
  transition: width 0.7s cubic-bezier(0.4,0,0.2,1);
}
@keyframes slideIn {
  from { opacity:0; transform: translateX(16px); }
  to   { opacity:1; transform: translateX(0); }
}
.muscle-chip {
  display: inline-block;
  background: rgba(255,101,132,0.12); color: var(--secondary);
  border-radius: 6px; padding: 2px 8px; font-size: 0.75rem; font-weight: 600;
}
.week-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 6px; }
.week-cell {
  background: var(--bg-card2); border: 1px solid var(--border);
  border-radius: 10px; padding: 8px 4px; text-align: center; font-size: 0.78rem;
}
.week-cell.today-cell { border-color: var(--primary); background: rgba(108,99,255,0.1); }
.week-day-name { color: var(--text-muted); margin-bottom: 4px; }
.week-ex-count { font-weight: 700; font-size: 1rem; color: var(--primary); }
</style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">
    <i class="bi bi-fire"></i>
    <span>Cal<span style="color:var(--secondary)">Track</span></span>
  </div>
  <ul class="navbar-nav">
    <li><a href="index.php"><i class="bi bi-house-fill"></i> الرئيسية</a></li>
    <li><a href="camera.php"><i class="bi bi-camera-fill"></i> تصوير</a></li>
    <li><a href="workout.php" class="active"><i class="bi bi-lightning-fill"></i> التمارين</a></li>
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

  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
    <div>
      <div class="page-title"><i class="bi bi-lightning-fill"></i> تمارينك</div>
      <div class="page-subtitle">اليوم: <strong style="color:var(--warning)"><?= $todayAr ?></strong></div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-primary" onclick="openAddPlanModal()"><i class="bi bi-plus-lg"></i> إضافة تمرين</button>
      <button class="btn btn-secondary" onclick="showView('week')"><i class="bi bi-calendar3"></i> الخطة الأسبوعية</button>
    </div>
  </div>

  <!-- Progress bar -->
  <div class="workout-progress card" style="padding:1rem 1.2rem;margin-bottom:1.2rem">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem">
      <span>تقدم اليوم</span>
      <span id="progress-label" style="color:var(--success);font-weight:700">0 / 0 تمرين</span>
    </div>
    <div class="progress-bar-wrap">
      <div class="progress-bar-fill" id="progress-fill" style="width:0%"></div>
    </div>
  </div>

  <!-- Views -->
  <div id="view-day">
    <div class="day-tabs-wrap">
      <div class="day-tabs" id="day-tabs">
        <?php foreach($dayNames as $i => $dn): ?>
        <button type="button"
          class="day-tab <?= $i===$todayDow?'today':'' ?> <?= $i===$todayDow?'active':'' ?>"
          id="dtab-<?= $i ?>"
          onclick="loadDayPlan(<?= $i ?>)">
          <?= $dn ?>
          <div class="ex-count" id="dtab-count-<?= $i ?>"></div>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div id="day-exercises" style="display:flex;flex-direction:column;gap:10px">
      <div style="text-align:center;padding:2rem;color:var(--text-muted)"><div class="spinner"></div></div>
    </div>
  </div>

  <!-- Week overview -->
  <div id="view-week" style="display:none">
    <div class="card">
      <div class="card-title" style="justify-content:space-between">
        <span><i class="bi bi-calendar3"></i> الخطة الأسبوعية</span>
        <button class="btn btn-secondary btn-sm" onclick="showView('day')"><i class="bi bi-arrow-right-circle-fill"></i> عودة</button>
      </div>
      <div class="week-grid" id="week-overview"></div>
    </div>
    <div id="week-detail" style="margin-top:1rem;display:flex;flex-direction:column;gap:10px"></div>
  </div>

</div><!-- /container -->

<!-- Log Workout Modal -->
<div class="modal-overlay" id="log-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="log-modal-title"><i class="bi bi-lightning-fill"></i> تسجيل التمرين</div>
      <button class="modal-close" onclick="closeLogModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="log-alert"></div>
    <input type="hidden" id="log-exercise-id">
    <input type="hidden" id="log-plan-id">

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">عدد السيتات</label>
        <input type="number" id="log-sets" class="form-control" value="3" min="1">
      </div>
      <div class="form-group">
        <label class="form-label">عدد الريبات</label>
        <input type="number" id="log-reps" class="form-control" value="10" min="1">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">الوزن المستخدم (كجم) <span style="color:var(--text-muted);font-weight:400">- اختياري</span></label>
      <input type="number" id="log-weight" class="form-control" placeholder="50" step="0.5" min="0">
    </div>
    <div id="last-weight-hint" style="font-size:0.82rem;color:var(--success);margin-bottom:1rem"></div>
    <div class="form-group">
      <label class="form-label">ملاحظة</label>
      <input type="text" id="log-notes" class="form-control" placeholder="اختياري">
    </div>
    <button class="btn btn-success btn-full" onclick="submitLog()"><i class="bi bi-check-circle-fill"></i> سجّل التمرين</button>
  </div>
</div>

<!-- Add to Plan Modal -->
<div class="modal-overlay" id="add-plan-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-plus-lg"></i> إضافة تمرين للخطة</div>
      <button class="modal-close" onclick="closeAddPlanModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="add-plan-alert"></div>

    <div class="form-group">
      <label class="form-label">اختر التمرين</label>
      <select id="ap-exercise" class="form-control" style="margin-bottom:6px"></select>
      <button type="button" class="btn btn-secondary btn-sm" onclick="showCustomExForm()">
        <i class="bi bi-plus-lg"></i> تمرين مخصص جديد
      </button>
    </div>

    <div id="custom-ex-form" style="display:none;background:var(--bg-card2);border-radius:10px;padding:12px;margin-bottom:1rem">
      <div class="form-group">
        <label class="form-label">اسم التمرين</label>
        <input type="text" id="new-ex-name" class="form-control" placeholder="اسم التمرين">
      </div>
      <div class="form-group" style="margin-bottom:0.5rem">
        <label class="form-label">المجموعة العضلية</label>
        <input type="text" id="new-ex-muscle" class="form-control" placeholder="مثال: الصدر">
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="addCustomExercise()">حفظ التمرين</button>
    </div>

    <div class="form-group">
      <label class="form-label">اليوم</label>
      <select id="ap-day" class="form-control">
        <?php foreach($dayNames as $i=>$dn): ?>
        <option value="<?=$i?>" <?=$i===$todayDow?'selected':''?>><?=$dn?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">عدد السيتات</label>
        <input type="number" id="ap-sets" class="form-control" value="3" min="1">
      </div>
      <div class="form-group">
        <label class="form-label">عدد الريبات</label>
        <input type="number" id="ap-reps" class="form-control" value="10" min="1">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">الوزن المستهدف (كجم)</label>
      <input type="number" id="ap-weight" class="form-control" value="0" step="0.5" min="0">
    </div>
    <button class="btn btn-primary btn-full" onclick="addToPlan()"><i class="bi bi-plus-lg"></i> أضف للخطة</button>
  </div>
</div>

<!-- Exercise History Modal -->
<div class="modal-overlay" id="hist-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="hist-title"><i class="bi bi-graph-up-arrow"></i> تاريخ التمرين</div>
      <button class="modal-close" onclick="document.getElementById('hist-modal').classList.remove('open')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="hist-content">
      <div class="spinner"></div>
    </div>
  </div>
</div>

<script>
const TODAY_DOW   = <?= $todayDow ?>;
const DAY_NAMES   = <?= json_encode($dayNames) ?>;
let activeDayTab  = TODAY_DOW;
let allPlanData   = {};
let todayLoggedIds = new Set();
let exercises     = [];

const muscleIcons = {
  'الصدر':'<i class="bi bi-person-arms-up"></i>',
  'الظهر':'<i class="bi bi-arrow-up-circle-fill"></i>',
  'البايسبس':'<i class="bi bi-person-arms-up"></i>',
  'التريسبس':'<i class="bi bi-person-arms-up"></i>',
  'الأكتاف':'<i class="bi bi-arrow-up-square-fill"></i>',
  'الأرجل':'<i class="bi bi-activity"></i>',
  'الساق':'<i class="bi bi-activity"></i>',
  'الكور':'<i class="bi bi-bullseye"></i>',
  'البطن':'<i class="bi bi-fire"></i>',
};

window.addEventListener('load', async () => {
  document.getElementById('main-content').style.opacity = '1';
  await Promise.all([loadAllPlan(), loadTodayLogs(), loadExercises()]);
  loadDayPlan(TODAY_DOW);
  buildWeekOverview();
});

async function loadAllPlan() {
  const res  = await fetch('api/workout.php?action=get_plan');
  const data = await res.json();
  if (!data.success) return;
  allPlanData = {};
  data.data.forEach(item => {
    const d = item.day_of_week;
    if (!allPlanData[d]) allPlanData[d] = [];
    allPlanData[d].push(item);
  });
  for (let i=0;i<7;i++) {
    const cnt = (allPlanData[i] || []).length;
    document.getElementById('dtab-count-' + i).textContent = cnt ? `(${cnt})` : '';
  }
}

async function loadTodayLogs() {
  const res  = await fetch('api/workout.php?action=get_today_logs');
  const data = await res.json();
  if (!data.success) return;
  todayLoggedIds = new Set(data.data.map(l => parseInt(l.exercise_id)));
  updateProgress(activeDayTab);
}

async function loadExercises() {
  const res  = await fetch('api/workout.php?action=get_exercises');
  const data = await res.json();
  if (!data.success) return;
  exercises = data.data;
  populateExerciseSelect();
}

function loadDayPlan(day) {
  activeDayTab = day;
  document.querySelectorAll('.day-tab').forEach((t,i) =>
    t.classList.toggle('active', i === day)
  );

  const plan = allPlanData[day] || [];
  const wrap = document.getElementById('day-exercises');

  if (!plan.length) {
    wrap.innerHTML = `
      <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted)">
        <div style="font-size:3rem;margin-bottom:12px"><i class="bi bi-calendar-x"></i></div>
        <div style="font-weight:600;margin-bottom:8px">لا توجد تمارين هذا اليوم</div>
        <div style="font-size:0.85rem;margin-bottom:1.5rem">استرح أو أضف تمارين لـ ${DAY_NAMES[day]}</div>
        <button class="btn btn-primary btn-sm" onclick="openAddPlanModal(${day})"><i class="bi bi-plus-lg"></i> إضافة تمرين</button>
      </div>`;
    updateProgress(day);
    return;
  }

  wrap.innerHTML = plan.map(item => {
    const done    = day === TODAY_DOW && todayLoggedIds.has(parseInt(item.exercise_id));
    const icon    = muscleIcons[item.muscle_group] || '<i class="bi bi-lightning-fill"></i>';
    const lastWt  = item.last_weight ? `آخر وزن: <strong style="color:var(--success)">${item.last_weight} كجم</strong> (${item.last_date})` : 'لم تُسجَّل بعد';
    return `
      <div class="ex-card ${done?'done':''}" id="exc-${item.id}">
        ${done ? '<div class="done-check"><i class="bi bi-check"></i></div>' : ''}
        <div class="ex-icon">${icon}</div>
        <div class="ex-info">
          <div class="ex-name">${escHtml(item.exercise_name)}</div>
          <div class="ex-meta">
            <span class="muscle-chip">${escHtml(item.muscle_group||'')}</span>
            &nbsp; ${item.sets} سيتات × ${item.reps} ريبة
            ${item.target_weight > 0 ? ` &nbsp;·&nbsp; هدف: ${item.target_weight} كجم` : ''}
          </div>
          <div class="ex-last">${lastWt}</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end">
          ${day === TODAY_DOW ? `
          <button class="btn btn-${done?'secondary':'success'} btn-sm"
            onclick="openLogModal(${item.exercise_id},'${escAttr(item.exercise_name)}',${item.id},${item.sets},${item.reps},${item.last_weight||0})">
            ${done ? '<i class="bi bi-pencil-fill"></i> تعديل' : '<i class="bi bi-check-circle-fill"></i> سجّل'}
          </button>` : ''}
          <button class="btn btn-secondary btn-sm" onclick="showHistory(${item.exercise_id},'${escAttr(item.exercise_name)}')">
            <i class="bi bi-graph-up-arrow"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-icon" style="width:32px;height:32px"
            onclick="removeFromPlan(${item.id})" title="حذف"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </div>`;
  }).join('');

  updateProgress(day);
}

function updateProgress(day) {
  if (day !== TODAY_DOW) return;
  const plan  = allPlanData[day] || [];
  const total = plan.length;
  const done  = plan.filter(i => todayLoggedIds.has(parseInt(i.exercise_id))).length;
  const pct   = total > 0 ? Math.round(done/total*100) : 0;
  document.getElementById('progress-label').textContent = `${done} / ${total} تمرين`;
  document.getElementById('progress-fill').style.width  = pct + '%';
}

function openLogModal(exerciseId, name, planId, sets, reps, lastWeight) {
  document.getElementById('log-modal-title').innerHTML = '<i class="bi bi-lightning-fill"></i> ' + name;
  document.getElementById('log-exercise-id').value = exerciseId;
  document.getElementById('log-plan-id').value     = planId;
  document.getElementById('log-sets').value  = sets;
  document.getElementById('log-reps').value  = reps;
  document.getElementById('log-weight').value = lastWeight > 0 ? lastWeight : '';
  document.getElementById('log-alert').innerHTML   = '';
  document.getElementById('last-weight-hint').textContent =
    lastWeight > 0 ? `آخر وزن سجّلته: ${lastWeight} كجم` : '';
  document.getElementById('log-modal').classList.add('open');
}
function closeLogModal() { document.getElementById('log-modal').classList.remove('open'); }

async function submitLog() {
  const fd = new FormData();
  fd.append('action',          'log_workout');
  fd.append('exercise_id',     document.getElementById('log-exercise-id').value);
  fd.append('plan_id',         document.getElementById('log-plan-id').value);
  fd.append('sets_completed',  document.getElementById('log-sets').value);
  fd.append('reps_completed',  document.getElementById('log-reps').value);
  fd.append('weight_used',     document.getElementById('log-weight').value || 0);
  fd.append('notes',           document.getElementById('log-notes').value);

  const res  = await fetch('api/workout.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    closeLogModal();
    const exId = parseInt(document.getElementById('log-exercise-id').value);
    todayLoggedIds.add(exId);
    await loadAllPlan();
    loadDayPlan(activeDayTab);
  } else {
    document.getElementById('log-alert').innerHTML =
      `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
}

async function removeFromPlan(planId) {
  if (!confirm('هل تريد حذف هذا التمرين من الخطة؟')) return;
  const fd = new FormData();
  fd.append('action','remove_from_plan');
  fd.append('plan_id', planId);
  await fetch('api/workout.php', { method:'POST', body:fd });
  await loadAllPlan();
  loadDayPlan(activeDayTab);
  buildWeekOverview();
}

function openAddPlanModal(preDay) {
  document.getElementById('add-plan-alert').innerHTML = '';
  document.getElementById('custom-ex-form').style.display = 'none';
  if (preDay !== undefined) document.getElementById('ap-day').value = preDay;
  document.getElementById('add-plan-modal').classList.add('open');
}
function closeAddPlanModal() { document.getElementById('add-plan-modal').classList.remove('open'); }

function showCustomExForm() {
  const f = document.getElementById('custom-ex-form');
  f.style.display = f.style.display === 'none' ? '' : 'none';
}

function populateExerciseSelect() {
  const sel = document.getElementById('ap-exercise');
  const grouped = {};
  exercises.forEach(e => {
    const g = e.muscle_group || 'أخرى';
    if (!grouped[g]) grouped[g] = [];
    grouped[g].push(e);
  });
  sel.innerHTML = Object.entries(grouped).map(([g, exs]) =>
    `<optgroup label="${escHtml(g)}">${exs.map(e => `<option value="${e.id}">${escHtml(e.name)}</option>`).join('')}</optgroup>`
  ).join('');
}

async function addCustomExercise() {
  const name   = document.getElementById('new-ex-name').value.trim();
  const muscle = document.getElementById('new-ex-muscle').value.trim();
  if (!name) return;
  const fd = new FormData();
  fd.append('action','add_exercise'); fd.append('name',name); fd.append('muscle_group',muscle);
  const res  = await fetch('api/workout.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    exercises.push({ id:data.id, name:data.name, muscle_group:data.muscle_group });
    populateExerciseSelect();
    document.getElementById('ap-exercise').value = data.id;
    document.getElementById('new-ex-name').value = '';
    document.getElementById('new-ex-muscle').value = '';
    document.getElementById('custom-ex-form').style.display = 'none';
  }
}

async function addToPlan() {
  const fd = new FormData();
  fd.append('action',        'add_to_plan');
  fd.append('exercise_id',   document.getElementById('ap-exercise').value);
  fd.append('day_of_week',   document.getElementById('ap-day').value);
  fd.append('sets',          document.getElementById('ap-sets').value);
  fd.append('reps',          document.getElementById('ap-reps').value);
  fd.append('target_weight', document.getElementById('ap-weight').value || 0);

  const res  = await fetch('api/workout.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    closeAddPlanModal();
    await loadAllPlan();
    loadDayPlan(activeDayTab);
    buildWeekOverview();
  } else {
    document.getElementById('add-plan-alert').innerHTML =
      `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
}

function buildWeekOverview() {
  const grid = document.getElementById('week-overview');
  grid.innerHTML = DAY_NAMES.map((dn, i) => {
    const cnt = (allPlanData[i] || []).length;
    return `<div class="week-cell ${i===TODAY_DOW?'today-cell':''}" onclick="jumpToDay(${i})" style="cursor:pointer">
      <div class="week-day-name">${dn.slice(0,3)}</div>
      <div class="week-ex-count">${cnt || '-'}</div>
      <div style="font-size:0.7rem;color:var(--text-muted)">${cnt?'تمرين':''}</div>
    </div>`;
  }).join('');
}

function jumpToDay(day) {
  showView('day');
  loadDayPlan(day);
}

async function showHistory(exerciseId, name) {
  document.getElementById('hist-title').innerHTML = '<i class="bi bi-graph-up-arrow"></i> ' + name;
  document.getElementById('hist-content').innerHTML = '<div class="spinner" style="margin:1rem auto"></div>';
  document.getElementById('hist-modal').classList.add('open');

  const res  = await fetch(`api/workout.php?action=get_exercise_history&exercise_id=${exerciseId}`);
  const data = await res.json();
  if (!data.success || !data.data.length) {
    document.getElementById('hist-content').innerHTML =
      '<div style="text-align:center;padding:1rem;color:var(--text-muted)">لا توجد سجلات بعد</div>';
    return;
  }

  document.getElementById('hist-content').innerHTML = `
    <div style="display:flex;flex-direction:column;gap:8px">
      ${data.data.map(l => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--bg-card2);border-radius:10px;border:1px solid var(--border)">
          <div>
            <div style="font-weight:600;font-size:0.9rem">${l.log_date}</div>
            <div style="font-size:0.8rem;color:var(--text-muted)">${l.sets_completed} سيتات × ${l.reps_completed} ريبة</div>
          </div>
          <div style="font-size:1.1rem;font-weight:700;color:var(--success)">${l.weight_used > 0 ? l.weight_used + ' كجم' : '--'}</div>
        </div>`).join('')}
    </div>`;
}

function showView(v) {
  document.getElementById('view-day').style.display  = v==='day'  ? '' : 'none';
  document.getElementById('view-week').style.display = v==='week' ? '' : 'none';
  if (v==='week') buildWeekOverview();
}

function escHtml(s) {
  return String(s||'').replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])
  );
}
function escAttr(s) { return escHtml(s); }

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('api/auth.php', { method:'POST', body:fd });
  location.href = 'login.php';
}
</script>
<script src="assets/js/theme.js"></script>
</body>
</html>
