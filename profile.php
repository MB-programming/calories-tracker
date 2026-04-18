<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$userName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ملفي الشخصي - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.bmi-gauge-section {
  display: flex; flex-direction: column; align-items: center;
  padding: 1rem 0 0.5rem;
}
.bmi-gauge-svg { overflow: visible; }
.bmi-needle-line {
  transform-origin: 110px 115px;
  transition: transform 1.2s cubic-bezier(0.4,0,0.2,1);
}
.photo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.photo-card {
  background: var(--bg-card2); border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden; position: relative;
  transition: transform 0.3s;
}
.photo-card:hover { transform: translateY(-3px); }
.photo-card img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
.photo-card-info {
  padding: 10px 12px; font-size: 0.82rem; color: var(--text-muted);
  display: flex; justify-content: space-between; align-items: center;
}
.photo-type-badge {
  position: absolute; top: 8px; right: 8px;
  background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
  color: #fff; border-radius: 8px; padding: 3px 10px; font-size: 0.78rem; font-weight: 600;
}
.wt-chart-wrap { display: flex; align-items: flex-end; gap: 4px; height: 100px; }
.wt-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; height: 100%; justify-content: flex-end; }
.wt-bar {
  width: 100%; border-radius: 4px 4px 0 0; min-height: 3px;
  background: linear-gradient(to top, var(--success), #2dd4a0);
  transition: height 0.8s cubic-bezier(0.4,0,0.2,1);
}
.wt-bar-lbl { font-size: 0.65rem; color: var(--text-muted); text-align: center; }
.profile-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px,1fr)); gap: 0.75rem; margin-bottom: 1.5rem; }
.profile-stat {
  background: var(--bg-card2); border: 1px solid var(--border);
  border-radius: 14px; padding: 14px; text-align: center;
  transition: transform 0.3s;
}
.profile-stat:hover { transform: translateY(-2px); }
.ps-val { font-size: 1.5rem; font-weight: 700; }
.ps-lbl { font-size: 0.78rem; color: var(--text-muted); margin-top: 3px; }
.photo-upload-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.mini-upload {
  border: 2px dashed var(--border); border-radius: 12px; padding: 1.2rem;
  text-align: center; cursor: pointer; position: relative;
  transition: all 0.3s; font-family: inherit;
}
.mini-upload:hover { border-color: var(--primary); background: rgba(108,99,255,0.04); }
.mini-upload input { position: absolute; inset:0; opacity:0; cursor:pointer; }
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
    <li><a href="workout.php"><i class="bi bi-lightning-fill"></i> التمارين</a></li>
    <li><a href="history.php"><i class="bi bi-calendar3"></i> السجل</a></li>
    <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> التقارير</a></li>
    <li><a href="profile.php" class="active"><i class="bi bi-person-fill"></i> ملفي</a></li>
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
      <div class="page-title"><i class="bi bi-person-fill"></i> ملفي الشخصي</div>
      <div class="page-subtitle">تتبع تقدمك وصحتك</div>
    </div>
    <button class="btn btn-primary" onclick="openLogWeightModal()"><i class="bi bi-clipboard2-check"></i> تسجيل وزن جديد</button>
  </div>

  <!-- Stats row -->
  <div class="profile-stats" id="profile-stats">
    <div class="profile-stat"><div class="ps-val" id="ps-weight">--</div><div class="ps-lbl">الوزن (كجم)</div></div>
    <div class="profile-stat"><div class="ps-val" id="ps-height">--</div><div class="ps-lbl">الطول (سم)</div></div>
    <div class="profile-stat"><div class="ps-val" id="ps-age">--</div><div class="ps-lbl">السن</div></div>
    <div class="profile-stat"><div class="ps-val" id="ps-goal-cal">--</div><div class="ps-lbl">هدف السعرات</div></div>
  </div>

  <div class="grid-2" style="margin-bottom:1.5rem">

    <!-- BMI Card -->
    <div class="card">
      <div class="card-title"><i class="bi bi-rulers"></i> مؤشر كتلة الجسم (BMI)</div>
      <div class="bmi-gauge-section">
        <svg class="bmi-gauge-svg" width="220" height="130" viewBox="0 0 220 130">
          <defs>
            <linearGradient id="bmiGradP" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%"   stop-color="#5B8DEF"/>
              <stop offset="30%"  stop-color="#43D98F"/>
              <stop offset="60%"  stop-color="#FFB347"/>
              <stop offset="100%" stop-color="#FF5757"/>
            </linearGradient>
          </defs>
          <path d="M 20 115 A 90 90 0 0 1 200 115"
            fill="none" stroke="var(--border)" stroke-width="18" stroke-linecap="round"/>
          <path id="p-bmi-fill"
            d="M 20 115 A 90 90 0 0 1 200 115"
            fill="none" stroke="url(#bmiGradP)" stroke-width="18" stroke-linecap="round"
            stroke-dasharray="283" stroke-dashoffset="283"/>
          <line id="p-bmi-needle"
            x1="110" y1="115" x2="110" y2="35"
            stroke="var(--text)" stroke-width="2.5" stroke-linecap="round"
            class="bmi-needle-line" style="transform:rotate(0deg)"/>
          <circle cx="110" cy="115" r="6" fill="var(--primary)"/>
        </svg>

        <div style="display:flex;justify-content:space-between;width:100%;padding:0 8px;font-size:0.7rem;color:var(--text-muted);margin-top:4px">
          <span>نحيف<br>&lt;18.5</span>
          <span>طبيعي<br>18.5-25</span>
          <span>زيادة<br>25-30</span>
          <span>سمنة<br>&gt;30</span>
        </div>

        <div style="font-size:2.8rem;font-weight:800;margin-top:12px" id="p-bmi-num">--</div>
        <div style="font-weight:700;font-size:1rem;margin-bottom:4px" id="p-bmi-cat">--</div>
        <div style="font-size:0.82rem;color:var(--text-muted)" id="p-bmi-hint"></div>
      </div>
    </div>

    <!-- Weight history chart -->
    <div class="card">
      <div class="card-title"><i class="bi bi-graph-up-arrow"></i> تاريخ الوزن</div>
      <div class="wt-chart-wrap" id="wt-chart">
        <div style="text-align:center;width:100%;color:var(--text-muted)"><div class="spinner"></div></div>
      </div>
      <div style="margin-top:1rem;font-size:0.85rem;color:var(--text-muted);text-align:center" id="wt-range-label"></div>
    </div>
  </div>

  <!-- Goal & fitness info -->
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-title"><i class="bi bi-bullseye"></i> هدفك ومعلوماتك</div>
    <div class="grid-2">
      <div>
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:4px">الهدف الرياضي</div>
        <div id="p-goal-label" style="font-weight:700;font-size:1.05rem">--</div>
      </div>
      <div>
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:4px">الجنس</div>
        <div id="p-gender-label" style="font-weight:700;font-size:1.05rem">--</div>
      </div>
    </div>
  </div>

  <!-- Body photos -->
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-title" style="justify-content:space-between">
      <span><i class="bi bi-camera-fill"></i> صور التقدم</span>
      <button class="btn btn-primary btn-sm" onclick="openUploadModal()"><i class="bi bi-plus-lg"></i> إضافة صورة</button>
    </div>

    <div class="tabs" style="margin-bottom:1rem">
      <button class="tab-btn active" onclick="filterPhotos('all')">الكل</button>
      <button class="tab-btn" onclick="filterPhotos('front')">من الأمام</button>
      <button class="tab-btn" onclick="filterPhotos('back')">من الخلف</button>
    </div>

    <div id="photos-grid" class="photo-grid">
      <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted)">
        <div class="spinner"></div>
      </div>
    </div>
  </div>

</div><!-- /container -->

<!-- Log Weight Modal -->
<div class="modal-overlay" id="log-weight-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-clipboard2-check"></i> تسجيل وزن جديد</div>
      <button class="modal-close" onclick="closeLogWeightModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="wt-alert"></div>
    <div class="form-group">
      <label class="form-label">الوزن الحالي (كجم)</label>
      <input type="number" id="wt-input" class="form-control" placeholder="70.5" step="0.1" min="20" max="400">
    </div>
    <div class="form-group">
      <label class="form-label">ملاحظة (اختياري)</label>
      <input type="text" id="wt-notes" class="form-control" placeholder="مثال: بعد التمرين">
    </div>
    <button class="btn btn-primary btn-full" onclick="logWeight()"><i class="bi bi-floppy-fill"></i> حفظ</button>
  </div>
</div>

<!-- Upload photo Modal -->
<div class="modal-overlay" id="upload-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-camera-fill"></i> إضافة صورة جديدة</div>
      <button class="modal-close" onclick="closeUploadModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="up-alert"></div>
    <div class="form-group">
      <label class="form-label">نوع الصورة</label>
      <select id="up-type" class="form-control">
        <option value="front"><i class="bi bi-person-fill"></i> من الأمام</option>
        <option value="back"><i class="bi bi-person-walking"></i> من الخلف</option>
      </select>
    </div>
    <div class="photo-zone" style="margin-bottom:1rem">
      <input type="file" id="up-file" accept="image/*" capture="environment" onchange="previewUpload(this)">
      <div class="photo-zone-icon" id="up-icon"><i class="bi bi-camera"></i></div>
      <div class="photo-zone-text" id="up-text">اضغط لاختيار صورة</div>
    </div>
    <img id="up-preview" style="display:none;width:100%;max-height:200px;object-fit:cover;border-radius:10px;margin-bottom:1rem" alt="">
    <div class="form-group">
      <label class="form-label">ملاحظة (اختياري)</label>
      <input type="text" id="up-notes" class="form-control" placeholder="مثال: الأسبوع الأول">
    </div>
    <button class="btn btn-primary btn-full" id="up-btn" onclick="uploadPhoto()">رفع الصورة</button>
  </div>
</div>

<script>
let allPhotos = [];
let currentFilter = 'all';

async function loadProfile() {
  const res  = await fetch('api/profile.php?action=get_profile');
  const data = await res.json();
  if (!data.success) return;
  const p = data.data;

  document.getElementById('ps-weight').textContent = p.latest_weight ? p.latest_weight + ' كجم' : '--';
  document.getElementById('ps-height').textContent = p.height ? p.height + ' سم' : '--';
  document.getElementById('ps-age').textContent    = p.age || '--';
  document.getElementById('ps-goal-cal').textContent = p.daily_goal ? p.daily_goal + ' كال' : '--';

  const goalMap  = { lose_weight:'خسارة وزن', gain_weight:'بناء عضلات', maintain:'الحفاظ' };
  const genMap   = { male:'ذكر', female:'أنثى' };
  document.getElementById('p-goal-label').textContent   = goalMap[p.fitness_goal]   || '--';
  document.getElementById('p-gender-label').textContent = genMap[p.gender] || '--';

  if (p.bmi) renderBMI(p.bmi);
}

function renderBMI(bmi) {
  const numEl  = document.getElementById('p-bmi-num');
  const catEl  = document.getElementById('p-bmi-cat');
  const hintEl = document.getElementById('p-bmi-hint');
  const fill   = document.getElementById('p-bmi-fill');
  const needle = document.getElementById('p-bmi-needle');

  let cat, color, hint;
  if (bmi < 16)        { cat='نحيف جداً';         color='#5B8DEF'; hint='يُنصح باستشارة طبيب'; }
  else if (bmi < 18.5) { cat='نحيف';              color='#7BA7E8'; hint='تحتاج لزيادة وزنك'; }
  else if (bmi < 25)   { cat='وزن مثالي';         color='#43D98F'; hint='أنت في النطاق الصحي!'; }
  else if (bmi < 30)   { cat='زيادة في الوزن';    color='#FFB347'; hint='يُنصح بالتحكم في السعرات'; }
  else if (bmi < 35)   { cat='سمنة من الدرجة الأولى'; color='#FF7B54'; hint='يُنصح بمتابعة طبيب'; }
  else                 { cat='سمنة مرتفعة';       color='#FF5757'; hint='يُنصح باستشارة طبيب'; }

  const clamp   = Math.min(Math.max(bmi, 10), 40);
  const ratio   = (clamp - 10) / 30;
  const dashOff = 283 * (1 - ratio);
  const needleAngle = (ratio * 180) - 90;

  let t0 = null;
  const step = ts => {
    if (!t0) t0 = ts;
    const p = Math.min((ts-t0)/1000, 1);
    numEl.textContent = (10 + (bmi-10)*p).toFixed(1);
    numEl.style.color = color;
    if (p < 1) requestAnimationFrame(step);
    else { numEl.textContent = bmi.toFixed(1); }
  };
  requestAnimationFrame(step);

  catEl.textContent  = cat;
  catEl.style.color  = color;
  hintEl.textContent = hint;

  setTimeout(() => {
    fill.style.strokeDashoffset = dashOff;
    needle.style.transform = `rotate(${needleAngle}deg)`;
  }, 300);
}

async function loadWeightHistory() {
  const res  = await fetch('api/profile.php?action=get_weight_history');
  const data = await res.json();
  if (!data.success || !data.data.length) {
    document.getElementById('wt-chart').innerHTML =
      '<div style="text-align:center;width:100%;color:var(--text-muted)">لا توجد سجلات وزن بعد</div>';
    return;
  }

  const entries = data.data;
  const weights = entries.map(e => parseFloat(e.weight));
  const minW = Math.min(...weights) - 1;
  const maxW = Math.max(...weights) + 1;
  const range = maxW - minW || 1;

  const chart = document.getElementById('wt-chart');
  chart.innerHTML = entries.map((e, i) => {
    const h = ((e.weight - minW) / range) * 90 + 10;
    const d = new Date(e.log_date);
    const lbl = (d.getMonth()+1) + '/' + d.getDate();
    return `<div class="wt-bar-col" title="${e.weight} كجم - ${e.log_date}">
      <div style="font-size:0.65rem;color:var(--success);font-weight:700">${i===entries.length-1?e.weight:''}</div>
      <div class="wt-bar" style="height:${h}%"></div>
      <div class="wt-bar-lbl">${lbl}</div>
    </div>`;
  }).join('');

  if (entries.length >= 2) {
    const first = entries[0], last = entries[entries.length-1];
    const diff  = (last.weight - first.weight).toFixed(1);
    const sign  = diff > 0 ? '+' : '';
    const clr   = diff < 0 ? 'var(--success)' : diff > 0 ? 'var(--danger)' : 'var(--text-muted)';
    document.getElementById('wt-range-label').innerHTML =
      `من ${first.log_date} إلى ${last.log_date} &nbsp;·&nbsp; <span style="color:${clr};font-weight:700">${sign}${diff} كجم</span>`;
  }
}

async function loadPhotos() {
  const res  = await fetch('api/profile.php?action=get_photos');
  const data = await res.json();
  if (!data.success) return;
  allPhotos = data.data;
  renderPhotos(allPhotos);
}

function renderPhotos(photos) {
  const grid = document.getElementById('photos-grid');
  if (!photos.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted)">لا توجد صور بعد.<br>سجّل تقدمك بصورة الجسم!</div>';
    return;
  }
  grid.innerHTML = photos.map(p => `
    <div class="photo-card">
      <span class="photo-type-badge">${p.photo_type==='front'
        ? '<i class="bi bi-person-fill"></i> أمام'
        : '<i class="bi bi-person-walking"></i> خلف'}</span>
      <img src="${p.photo_path}" alt="صورة جسم" onerror="this.src='assets/img/no-photo.png'">
      <div class="photo-card-info">
        <span>${p.taken_at}</span>
        ${p.weight_at_time ? `<span style="color:var(--success);font-weight:600">${p.weight_at_time} كجم</span>` : ''}
      </div>
    </div>
  `).join('');
}

function filterPhotos(type) {
  currentFilter = type;
  document.querySelectorAll('.tab-btn').forEach((b,i) =>
    b.classList.toggle('active', ['all','front','back'][i]===type)
  );
  renderPhotos(type === 'all' ? allPhotos : allPhotos.filter(p => p.photo_type === type));
}

function openLogWeightModal() {
  document.getElementById('log-weight-modal').classList.add('open');
  document.getElementById('wt-input').value = '';
  document.getElementById('wt-notes').value = '';
  document.getElementById('wt-alert').innerHTML = '';
}
function closeLogWeightModal() {
  document.getElementById('log-weight-modal').classList.remove('open');
}
async function logWeight() {
  const wt = parseFloat(document.getElementById('wt-input').value);
  if (!wt || wt < 20 || wt > 400) {
    document.getElementById('wt-alert').innerHTML = '<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> أدخل وزناً صحيحاً</div>';
    return;
  }
  const fd = new FormData();
  fd.append('action','log_weight');
  fd.append('weight', wt);
  fd.append('notes',  document.getElementById('wt-notes').value);
  const res  = await fetch('api/profile.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) {
    closeLogWeightModal();
    loadProfile();
    loadWeightHistory();
  } else {
    document.getElementById('wt-alert').innerHTML = `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
}

function openUploadModal() {
  document.getElementById('upload-modal').classList.add('open');
  document.getElementById('up-alert').innerHTML = '';
  document.getElementById('up-preview').style.display = 'none';
}
function closeUploadModal() { document.getElementById('upload-modal').classList.remove('open'); }

function previewUpload(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('up-preview');
    img.src = e.target.result; img.style.display = 'block';
    document.getElementById('up-icon').innerHTML = '<i class="bi bi-check-circle-fill" style="color:var(--success)"></i>';
    document.getElementById('up-text').textContent = 'تم اختيار الصورة';
  };
  reader.readAsDataURL(input.files[0]);
}

async function uploadPhoto() {
  const file = document.getElementById('up-file').files[0];
  if (!file) {
    document.getElementById('up-alert').innerHTML = '<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> اختر صورة أولاً</div>';
    return;
  }
  const btn = document.getElementById('up-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;margin:0"></div> جاري الرفع...';

  const fd = new FormData();
  fd.append('action',     'save_photo');
  fd.append('photo_type', document.getElementById('up-type').value);
  fd.append('photo',      file);
  fd.append('notes',      document.getElementById('up-notes').value);

  const res  = await fetch('api/profile.php', { method:'POST', body:fd });
  const data = await res.json();
  btn.disabled = false; btn.textContent = 'رفع الصورة';

  if (data.success) {
    closeUploadModal();
    loadPhotos();
  } else {
    document.getElementById('up-alert').innerHTML = `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
}

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('api/profile.php', { method:'POST', body:fd });
  location.href = 'login.php';
}

window.addEventListener('load', async () => {
  const main = document.getElementById('main-content');
  main.style.transition = 'opacity 0.5s';
  main.style.opacity = '1';

  await Promise.all([loadProfile(), loadWeightHistory(), loadPhotos()]);
});
</script>
</body>
</html>
