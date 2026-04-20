<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();

// If profile already complete, go to dashboard
$stmt = $pdo->prepare('SELECT profile_complete, name FROM users WHERE id=?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user['profile_complete']) {
    header('Location: /index.php');
    exit;
}
$userName = $user['name'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إعداد الملف الشخصي - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=4">
<style>
.setup-wrap {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: radial-gradient(ellipse at 70% 30%, rgba(108,99,255,0.18) 0%, transparent 60%),
              radial-gradient(ellipse at 20% 80%, rgba(255,101,132,0.12) 0%, transparent 50%),
              var(--bg);
  padding: 1.5rem;
}

.setup-card {
  width: 100%; max-width: 520px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 24px 64px rgba(0,0,0,0.45);
}

/* Step indicator */
.steps-bar {
  display: flex; align-items: center; justify-content: center;
  gap: 0; margin-bottom: 2rem;
}
.step-dot {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: var(--bg-card2);
  border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; font-weight: 700; color: var(--text-muted);
  transition: all 0.4s;
  position: relative; z-index: 1;
  flex-shrink: 0;
}
.step-dot.active {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-color: var(--primary);
  color: #fff;
  box-shadow: 0 0 16px rgba(108,99,255,0.5);
}
.step-dot.done {
  background: var(--success);
  border-color: var(--success);
  color: #fff;
}
.step-line {
  flex: 1; height: 2px;
  background: var(--border);
  transition: background 0.4s;
  max-width: 60px;
}
.step-line.done { background: var(--success); }

/* Step panels */
.step-panel { display: none; animation: fadeSlide 0.4s ease; }
.step-panel.active { display: block; }
@keyframes fadeSlide {
  from { opacity:0; transform: translateX(-16px); }
  to   { opacity:1; transform: translateX(0); }
}

.step-title {
  font-size: 1.5rem; font-weight: 700; margin-bottom: 6px;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.step-subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.8rem; }

/* Gender buttons */
.gender-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem; }
.gender-btn {
  padding: 1.2rem 1rem;
  border: 2px solid var(--border);
  border-radius: 14px;
  background: var(--bg-card2);
  cursor: pointer; text-align: center;
  transition: all 0.3s; color: var(--text);
  font-family: inherit; font-size: 0.95rem;
}
.gender-btn:hover { border-color: var(--primary); }
.gender-btn.selected { border-color: var(--primary); background: rgba(108,99,255,0.12); }
.gender-btn .gender-icon { font-size: 2.2rem; display: block; margin-bottom: 6px; }

/* Goal cards */
.goal-list { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; }
.goal-card {
  padding: 1.2rem 1.4rem;
  border: 2px solid var(--border);
  border-radius: 14px;
  background: var(--bg-card2);
  cursor: pointer; display: flex; align-items: center; gap: 14px;
  transition: all 0.3s; color: var(--text); font-family: inherit;
}
.goal-card:hover { border-color: var(--primary); transform: translateX(-4px); }
.goal-card.selected { border-color: var(--primary); background: rgba(108,99,255,0.12); }
.goal-icon { font-size: 2rem; flex-shrink: 0; }
.goal-text { text-align: right; }
.goal-title { font-weight: 700; font-size: 1rem; }
.goal-desc  { font-size: 0.82rem; color: var(--text-muted); margin-top: 2px; }

/* BMI Gauge */
.bmi-gauge-wrap {
  display: flex; flex-direction: column; align-items: center;
  padding: 1.5rem 0; gap: 0.5rem;
}
.bmi-svg { overflow: visible; }
.bmi-gauge-track {
  fill: none; stroke-width: 18; stroke-linecap: round;
}
.bmi-gauge-fill {
  fill: none; stroke-width: 18; stroke-linecap: round;
  transition: stroke-dashoffset 1.4s cubic-bezier(0.4,0,0.2,1),
              stroke 0.6s ease;
}
.bmi-value-big { font-size: 2.6rem; font-weight: 800; text-align: center; }
.bmi-label-cat { font-size: 1.05rem; font-weight: 700; text-align: center; margin-bottom: 4px; }
.bmi-scale {
  display: flex; justify-content: space-between;
  width: 100%; padding: 0 8px;
  font-size: 0.72rem; color: var(--text-muted);
  margin-top: 4px;
}
.bmi-detail-row {
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 0.75rem; width: 100%; margin-top: 1rem;
}
.bmi-detail-pill {
  background: var(--bg-card2); border: 1px solid var(--border);
  border-radius: 12px; padding: 10px; text-align: center;
}
.bmi-detail-val { font-size: 1.1rem; font-weight: 700; }
.bmi-detail-lbl { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }

/* Photo upload zone */
.photo-zone {
  border: 2px dashed var(--border);
  border-radius: 14px; padding: 1.5rem;
  text-align: center; cursor: pointer;
  transition: all 0.3s; position: relative;
}
.photo-zone:hover { border-color: var(--primary); background: rgba(108,99,255,0.04); }
.photo-zone input[type=file] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.photo-zone-icon { font-size: 2.5rem; margin-bottom: 8px; }
.photo-zone-text { font-size: 0.9rem; color: var(--text-muted); }
.photo-preview {
  width: 100%; max-height: 200px; object-fit: cover;
  border-radius: 10px; display: none; margin-top: 10px;
}
.photo-tabs { display: flex; gap: 8px; margin-bottom: 1rem; }
.photo-tab {
  flex: 1; padding: 10px; border: 2px solid var(--border);
  border-radius: 10px; background: var(--bg-card2);
  cursor: pointer; font-family: inherit; font-size: 0.9rem;
  font-weight: 600; color: var(--text-muted); transition: all 0.3s;
}
.photo-tab.active { border-color: var(--primary); color: var(--primary); background: rgba(108,99,255,0.1); }

.nav-btns { display: flex; gap: 10px; margin-top: 1.5rem; }
.skip-btn { background: none; border: none; color: var(--text-muted); font-size: 0.9rem; cursor: pointer; padding: 10px; }
.skip-btn:hover { color: var(--text); }
</style>
</head>
<body>
<div class="setup-wrap">
  <div class="setup-card" id="setup-card">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:1.5rem">
      <span style="font-size:2.5rem">🔥</span>
      <div style="font-weight:700;color:var(--primary);font-size:1.2rem">مرحباً، <?= htmlspecialchars($userName) ?>!</div>
      <div style="color:var(--text-muted);font-size:0.85rem">دعنا نعرّفك على نفسك لنصمم تجربتك</div>
    </div>

    <!-- Steps bar -->
    <div class="steps-bar" id="steps-bar">
      <div class="step-dot active" id="dot-1">1</div>
      <div class="step-line"        id="line-1"></div>
      <div class="step-dot"         id="dot-2">2</div>
      <div class="step-line"        id="line-2"></div>
      <div class="step-dot"         id="dot-3">3</div>
      <div class="step-line"        id="line-3"></div>
      <div class="step-dot"         id="dot-4">4</div>
    </div>

    <div id="alert-box"></div>

    <!-- ── Step 1: Personal info ────────────────────────────── -->
    <div class="step-panel active" id="step-1">
      <div class="step-title">معلوماتك الشخصية</div>
      <div class="step-subtitle">سنحسب لك الـ BMI والسعرات المناسبة</div>

      <div class="gender-row">
        <button type="button" class="gender-btn" data-gender="male" onclick="selectGender('male')">
          <span class="gender-icon">👨</span>
          ذكر
        </button>
        <button type="button" class="gender-btn" data-gender="female" onclick="selectGender('female')">
          <span class="gender-icon">👩</span>
          أنثى
        </button>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">السن (سنة)</label>
          <input type="number" id="inp-age" class="form-control" placeholder="25" min="5" max="120">
        </div>
        <div class="form-group">
          <label class="form-label">الوزن الحالي (كجم)</label>
          <input type="number" id="inp-weight" class="form-control" placeholder="70" step="0.1" min="20" max="400">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">الطول (سم)</label>
        <input type="number" id="inp-height" class="form-control" placeholder="175" min="50" max="300">
      </div>

      <div class="nav-btns">
        <button class="btn btn-primary" style="flex:1" onclick="goStep2()">التالي ←</button>
      </div>
    </div>

    <!-- ── Step 2: Fitness goal ──────────────────────────────── -->
    <div class="step-panel" id="step-2">
      <div class="step-title">ما هدفك؟</div>
      <div class="step-subtitle">اختر هدفك الرئيسي وسنضبط كل شيء تلقائياً</div>

      <div class="goal-list">
        <button type="button" class="goal-card" data-goal="lose_weight" onclick="selectGoal('lose_weight')">
          <span class="goal-icon">🔥</span>
          <div class="goal-text">
            <div class="goal-title">خسارة الوزن</div>
            <div class="goal-desc">حرق الدهون والوصول لوزن مثالي · 1500 كالوري/يوم</div>
          </div>
        </button>
        <button type="button" class="goal-card" data-goal="gain_weight" onclick="selectGoal('gain_weight')">
          <span class="goal-icon">💪</span>
          <div class="goal-text">
            <div class="goal-title">زيادة الوزن والعضلات</div>
            <div class="goal-desc">بناء الجسم ورفع الكتلة العضلية · 2800 كالوري/يوم</div>
          </div>
        </button>
        <button type="button" class="goal-card" data-goal="maintain" onclick="selectGoal('maintain')">
          <span class="goal-icon">⚖️</span>
          <div class="goal-text">
            <div class="goal-title">الحفاظ على الوزن</div>
            <div class="goal-desc">الحفاظ على الشكل الحالي والصحة العامة · 2000 كالوري/يوم</div>
          </div>
        </button>
      </div>

      <div class="nav-btns">
        <button class="btn btn-secondary btn-sm" onclick="goStep(1)" style="padding:10px 16px">→ رجوع</button>
        <button class="btn btn-primary" style="flex:1" onclick="goStep3()">التالي ←</button>
      </div>
    </div>

    <!-- ── Step 3: BMI result ─────────────────────────────────── -->
    <div class="step-panel" id="step-3">
      <div class="step-title">نتيجة مؤشر كتلة جسمك</div>
      <div class="step-subtitle">حساب الـ BMI بناءً على بياناتك</div>

      <div class="bmi-gauge-wrap">
        <!-- SVG arc gauge -->
        <svg class="bmi-svg" width="220" height="130" viewBox="0 0 220 130">
          <defs>
            <linearGradient id="bmiGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%"   stop-color="#5B8DEF"/>
              <stop offset="30%"  stop-color="#43D98F"/>
              <stop offset="60%"  stop-color="#FFB347"/>
              <stop offset="100%" stop-color="#FF5757"/>
            </linearGradient>
          </defs>
          <!-- Track arc: half circle centered at 110,115 radius 90 -->
          <path id="bmi-track"
            d="M 20 115 A 90 90 0 0 1 200 115"
            class="bmi-gauge-track" stroke="var(--border)" fill="none"/>
          <!-- Fill arc -->
          <path id="bmi-fill"
            d="M 20 115 A 90 90 0 0 1 200 115"
            class="bmi-gauge-fill" stroke="url(#bmiGrad)" fill="none"
            stroke-dasharray="283" stroke-dashoffset="283"/>
          <!-- Needle -->
          <line id="bmi-needle"
            x1="110" y1="115" x2="110" y2="35"
            stroke="var(--text)" stroke-width="2.5" stroke-linecap="round"
            style="transform-origin:110px 115px; transform:rotate(0deg); transition:transform 1.4s cubic-bezier(0.4,0,0.2,1)"/>
          <circle cx="110" cy="115" r="6" fill="var(--primary)"/>
        </svg>

        <div class="bmi-scale">
          <span>نحيف<br>&lt;18.5</span>
          <span>طبيعي<br>18.5-25</span>
          <span>زيادة<br>25-30</span>
          <span>سمنة<br>&gt;30</span>
        </div>

        <div class="bmi-value-big" id="bmi-number" style="color:var(--primary)">--</div>
        <div class="bmi-label-cat" id="bmi-cat">جاري الحساب...</div>

        <div class="bmi-detail-row" id="bmi-details">
          <div class="bmi-detail-pill">
            <div class="bmi-detail-val" id="det-weight">--</div>
            <div class="bmi-detail-lbl">الوزن (كجم)</div>
          </div>
          <div class="bmi-detail-pill">
            <div class="bmi-detail-val" id="det-height">--</div>
            <div class="bmi-detail-lbl">الطول (سم)</div>
          </div>
          <div class="bmi-detail-pill">
            <div class="bmi-detail-val" id="det-goal">--</div>
            <div class="bmi-detail-lbl">هدفك اليومي</div>
          </div>
        </div>
      </div>

      <div id="save-status"></div>

      <div class="nav-btns">
        <button class="btn btn-secondary btn-sm" onclick="goStep(2)" style="padding:10px 16px">→ رجوع</button>
        <button class="btn btn-primary" style="flex:1" id="save-continue-btn" onclick="goStep4()" disabled>
          التالي ← (صور الجسم)
        </button>
      </div>
    </div>

    <!-- ── Step 4: Body photos (optional) ────────────────────── -->
    <div class="step-panel" id="step-4">
      <div class="step-title">صور التقدم <span style="font-size:0.85rem;color:var(--text-muted);font-weight:400">(اختياري)</span></div>
      <div class="step-subtitle">صوّر جسمك الآن لمقارنة تقدمك بعد كل فترة</div>

      <div class="photo-tabs">
        <button type="button" class="photo-tab active" id="ptab-front" onclick="switchPhotoTab('front')">
          🧍 من الأمام
        </button>
        <button type="button" class="photo-tab" id="ptab-back" onclick="switchPhotoTab('back')">
          🚶 من الخلف
        </button>
      </div>

      <!-- Front photo zone -->
      <div id="pzone-front">
        <div class="photo-zone" id="dropzone-front">
          <input type="file" accept="image/*" capture="environment" onchange="previewPhoto(this,'front')">
          <div class="photo-zone-icon">📷</div>
          <div class="photo-zone-text">اضغط لالتقاط أو رفع صورة من الأمام</div>
        </div>
        <img id="preview-front" class="photo-preview" alt="صورة الأمام">
      </div>

      <!-- Back photo zone -->
      <div id="pzone-back" style="display:none">
        <div class="photo-zone" id="dropzone-back">
          <input type="file" accept="image/*" capture="environment" onchange="previewPhoto(this,'back')">
          <div class="photo-zone-icon">📷</div>
          <div class="photo-zone-text">اضغط لالتقاط أو رفع صورة من الخلف</div>
        </div>
        <img id="preview-back" class="photo-preview" alt="صورة الخلف">
      </div>

      <div id="upload-status" style="margin-top:0.75rem"></div>

      <div class="nav-btns">
        <button class="btn btn-secondary btn-sm" onclick="goStep(3)" style="padding:10px 16px">→ رجوع</button>
        <button class="btn btn-primary" style="flex:1" id="finish-btn" onclick="finishSetup()">
          🚀 ابدأ رحلتك!
        </button>
      </div>
      <div style="text-align:center;margin-top:10px">
        <button class="skip-btn" onclick="location.href='index.php'">تخطي الصور الآن</button>
      </div>
    </div>

  </div><!-- /setup-card -->
</div>

<script>
let selectedGender = null;
let selectedGoal   = null;
let currentStep    = 1;
let profileSaved   = false;
const photoFiles   = { front: null, back: null };

// ── Navigation helpers ──────────────────────────────────────────────────────
function goStep(n) {
  document.getElementById('step-' + currentStep).classList.remove('active');
  document.getElementById('dot-' + currentStep).classList.remove('active');
  document.getElementById('dot-' + currentStep).classList.add('done');
  if (n > currentStep && currentStep < 4) {
    document.getElementById('line-' + currentStep).classList.add('done');
  }
  currentStep = n;
  document.getElementById('step-' + n).classList.add('active');
  document.getElementById('dot-' + n).classList.add('active');
  document.getElementById('dot-' + n).classList.remove('done');
}

function showAlert(msg, type = 'error') {
  document.getElementById('alert-box').innerHTML =
    `<div class="alert alert-${type}">${type==='error'?'❌':'✅'} ${msg}</div>`;
  setTimeout(() => { document.getElementById('alert-box').innerHTML = ''; }, 4000);
}

// ── Step 1 → Step 2 ─────────────────────────────────────────────────────────
function selectGender(g) {
  selectedGender = g;
  document.querySelectorAll('.gender-btn').forEach(b =>
    b.classList.toggle('selected', b.dataset.gender === g)
  );
}

function goStep2() {
  const age    = parseInt(document.getElementById('inp-age').value);
  const weight = parseFloat(document.getElementById('inp-weight').value);
  const height = parseFloat(document.getElementById('inp-height').value);

  if (!selectedGender)         return showAlert('اختر الجنس');
  if (!age || age<5 || age>120)           return showAlert('أدخل سناً صحيحاً (5-120)');
  if (!weight || weight<20 || weight>400) return showAlert('أدخل وزناً صحيحاً (20-400 كجم)');
  if (!height || height<50 || height>300) return showAlert('أدخل طولاً صحيحاً (50-300 سم)');

  goStep(2);
}

// ── Step 2 → Step 3 ─────────────────────────────────────────────────────────
function selectGoal(g) {
  selectedGoal = g;
  document.querySelectorAll('.goal-card').forEach(c =>
    c.classList.toggle('selected', c.dataset.goal === g)
  );
}

async function goStep3() {
  if (!selectedGoal) return showAlert('اختر هدفك أولاً');
  goStep(3);
  await saveProfileAndShowBMI();
}

// ── Save profile + animate BMI ───────────────────────────────────────────────
async function saveProfileAndShowBMI() {
  const btn = document.getElementById('save-continue-btn');
  document.getElementById('save-status').innerHTML =
    '<div class="alert alert-info">⏳ جاري حفظ بياناتك...</div>';

  const fd = new FormData();
  fd.append('action',       'save_profile');
  fd.append('age',          document.getElementById('inp-age').value);
  fd.append('weight',       document.getElementById('inp-weight').value);
  fd.append('height',       document.getElementById('inp-height').value);
  fd.append('gender',       selectedGender);
  fd.append('fitness_goal', selectedGoal);

  try {
    const res  = await fetch('api/profile.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (!data.success) {
      document.getElementById('save-status').innerHTML =
        `<div class="alert alert-error">❌ ${data.message}</div>`;
      return;
    }

    profileSaved = true;
    document.getElementById('save-status').innerHTML = '';
    btn.disabled = false;

    animateBMI(data.bmi, parseFloat(document.getElementById('inp-weight').value),
               parseFloat(document.getElementById('inp-height').value), data.daily_goal);

  } catch(e) {
    document.getElementById('save-status').innerHTML =
      '<div class="alert alert-error">❌ خطأ في الاتصال</div>';
  }
}

// ── BMI animation ────────────────────────────────────────────────────────────
function animateBMI(bmi, weight, height, dailyGoal) {
  const numEl  = document.getElementById('bmi-number');
  const catEl  = document.getElementById('bmi-cat');
  const fill   = document.getElementById('bmi-fill');
  const needle = document.getElementById('bmi-needle');

  // Category
  let cat, color;
  if (bmi < 18.5)      { cat = 'نحيف جداً';       color = '#5B8DEF'; }
  else if (bmi < 25)   { cat = 'وزن طبيعي ✅';    color = '#43D98F'; }
  else if (bmi < 30)   { cat = 'زيادة في الوزن';  color = '#FFB347'; }
  else                 { cat = 'سمنة';             color = '#FF5757'; }

  // Arc fill: BMI 10→40 maps to dashoffset 283→0
  const clamp   = Math.min(Math.max(bmi, 10), 40);
  const ratio   = (clamp - 10) / 30;
  const dashOff = 283 * (1 - ratio);

  // Needle: -90deg (left/10) to +90deg (right/40)
  const needleAngle = (ratio * 180) - 90;

  // Animate number count-up
  let start = 10, dur = 1200, t0 = null;
  const step = (ts) => {
    if (!t0) t0 = ts;
    const p = Math.min((ts - t0) / dur, 1);
    const v = start + (bmi - start) * p;
    numEl.textContent = v.toFixed(1);
    numEl.style.color = color;
    if (p < 1) requestAnimationFrame(step);
    else { numEl.textContent = bmi.toFixed(1); catEl.textContent = cat; catEl.style.color = color; }
  };
  requestAnimationFrame(step);

  // After short delay apply arc + needle
  setTimeout(() => {
    fill.style.strokeDashoffset = dashOff;
    needle.style.transform = `rotate(${needleAngle}deg)`;
  }, 200);

  // Detail pills
  document.getElementById('det-weight').textContent = weight + ' كجم';
  document.getElementById('det-height').textContent = height + ' سم';
  document.getElementById('det-goal').textContent   = dailyGoal + ' كالوري';

  const goalMap = { lose_weight: 'خسارة وزن 🔥', gain_weight: 'زيادة عضلات 💪', maintain: 'الحفاظ ⚖️' };
  document.getElementById('det-goal').textContent = goalMap[selectedGoal] || dailyGoal + ' كالوري';
}

// ── Step 3 → Step 4 ─────────────────────────────────────────────────────────
function goStep4() {
  if (!profileSaved) return;
  goStep(4);
}

// ── Photo tab switch ─────────────────────────────────────────────────────────
function switchPhotoTab(type) {
  ['front','back'].forEach(t => {
    document.getElementById('pzone-' + t).style.display = t === type ? '' : 'none';
    document.getElementById('ptab-' + t).classList.toggle('active', t === type);
  });
}

function previewPhoto(input, type) {
  const file = input.files[0];
  if (!file) return;
  photoFiles[type] = file;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('preview-' + type);
    img.src = e.target.result;
    img.style.display = 'block';
    document.getElementById('dropzone-' + type).querySelector('.photo-zone-icon').textContent = '✅';
    document.getElementById('dropzone-' + type).querySelector('.photo-zone-text').textContent = 'تم اختيار الصورة';
  };
  reader.readAsDataURL(file);
}

// ── Finish: upload photos then redirect ──────────────────────────────────────
async function finishSetup() {
  const btn = document.getElementById('finish-btn');
  btn.disabled = true;
  btn.textContent = '⏳ جاري الحفظ...';

  const uploads = [];
  for (const type of ['front','back']) {
    if (photoFiles[type]) uploads.push(uploadPhoto(type, photoFiles[type]));
  }

  if (uploads.length > 0) {
    document.getElementById('upload-status').innerHTML =
      '<div class="alert alert-info">⏳ جاري رفع الصور...</div>';
    await Promise.all(uploads);
    document.getElementById('upload-status').innerHTML =
      '<div class="alert alert-success">✅ تم رفع الصور بنجاح!</div>';
    await new Promise(r => setTimeout(r, 800));
  }

  location.href = 'index.php';
}

async function uploadPhoto(type, file) {
  const fd = new FormData();
  fd.append('action',     'save_photo');
  fd.append('photo_type', type);
  fd.append('photo',      file);
  try { await fetch('api/profile.php', { method: 'POST', body: fd }); } catch(e) {}
}

// Entrance animation
window.addEventListener('load', () => {
  const card = document.getElementById('setup-card');
  card.style.opacity = '0';
  card.style.transform = 'translateY(30px)';
  requestAnimationFrame(() => {
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
  });
});
</script>
</body>
</html>
