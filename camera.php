<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/app_settings.php';
requireLogin();
$userName  = $_SESSION['user_name'];
$dailyGoal = $_SESSION['daily_goal'] ?? 2000;
$app = getAppSettings($pdo);
$APP_NAME = htmlspecialchars($app['name']);
$APP_ICON = htmlspecialchars($app['logo_icon']);
$APP_COLOR = htmlspecialchars($app['logo_color']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تصوير الطعام - <?= $APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-brand"><i class="bi bi-<?= $APP_ICON ?>"<?= $APP_COLOR ? ' style="color:'.$APP_COLOR.'"' : '' ?>></i><span><?= $APP_NAME ?></span></div>
  <ul class="navbar-nav">
    <li><a href="index.php"><i class="bi bi-house-fill"></i> الرئيسية</a></li>
    <li><a href="camera.php" class="active"><i class="bi bi-camera-fill"></i> تصوير الطعام</a></li>
    <li><a href="history.php"><i class="bi bi-calendar3"></i> السجل</a></li>
    <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> التقارير</a></li>
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
    <div class="page-title"><i class="bi bi-camera-fill"></i> تحليل الطعام بالذكاء الاصطناعي</div>
    <div class="page-subtitle">صوّر طعامك وسيحلله الذكاء الاصطناعي تلقائياً</div>
  </div>

  <div class="grid-2" style="align-items:start">
    <!-- Camera / Upload -->
    <div class="card">
      <div class="card-title"><i class="bi bi-camera"></i> التقاط صورة</div>

      <!-- Mode Tabs -->
      <div class="tabs" style="margin-bottom:1rem">
        <button class="tab-btn active" onclick="setMode('camera')"><i class="bi bi-camera"></i> الكاميرا</button>
        <button class="tab-btn" onclick="setMode('upload')"><i class="bi bi-folder2-open"></i> رفع صورة</button>
        <button class="tab-btn" onclick="setMode('text')"><i class="bi bi-chat-dots-fill"></i> اسأل / صف</button>
      </div>

      <!-- Camera Mode -->
      <div id="camera-mode">
        <div class="camera-container" id="camera-container">
          <video id="camera-video" autoplay playsinline muted></video>
          <canvas id="camera-canvas" style="display:none"></canvas>
          <div class="camera-overlay">
            <button class="capture-btn" onclick="capturePhoto()" id="capture-btn" title="التقاط">
              <i class="bi bi-camera-fill"></i>
            </button>
          </div>
          <div class="scan-line" id="scan-line"></div>
          <div class="analyzing-overlay" id="analyzing-overlay" style="display:none">
            <div class="spinner" style="width:48px;height:48px;border-width:4px"></div>
            <div class="analyzing-text pulse"><i class="bi bi-robot"></i> جاري تحليل الصورة...</div>
            <div style="color:var(--text-muted);font-size:0.85rem">يتعرف الذكاء الاصطناعي على طعامك</div>
          </div>
        </div>

        <div style="margin-top:1rem;display:flex;gap:10px">
          <button class="btn btn-secondary" id="switch-cam-btn" onclick="switchCamera()" style="display:none">
            <i class="bi bi-arrow-repeat"></i> تبديل الكاميرا
          </button>
          <button class="btn btn-danger btn-sm" onclick="stopCamera()" id="stop-cam-btn" style="display:none">
            <i class="bi bi-stop-fill"></i> إيقاف
          </button>
        </div>
      </div>

      <!-- Upload Mode -->
      <div id="upload-mode" style="display:none">
        <div class="upload-zone" id="upload-zone" onclick="document.getElementById('file-input').click()">
          <div style="font-size:3rem;margin-bottom:8px"><i class="bi bi-folder2-open"></i></div>
          <div style="font-weight:600">اسحب الصورة هنا أو اضغط للاختيار</div>
          <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px">JPG, PNG, WEBP - حتى 10MB</div>
        </div>
        <input type="file" id="file-input" accept="image/*" style="display:none" onchange="handleFileUpload(event)">
        <div id="upload-preview" style="display:none;margin-top:1rem">
          <img id="preview-img" class="img-preview" alt="معاينة">
          <div style="margin-top:1rem;display:flex;gap:10px">
            <button class="btn btn-primary" onclick="analyzeUploadedImage()" id="analyze-btn">
              <i class="bi bi-robot"></i> تحليل الصورة
            </button>
            <button class="btn btn-secondary" onclick="resetUpload()"><i class="bi bi-arrow-repeat"></i> اختر أخرى</button>
          </div>
        </div>
      </div>
      <!-- Text Query Mode -->
      <div id="text-mode" style="display:none">
        <div style="margin-bottom:0.75rem;color:var(--text-muted);font-size:0.9rem">
          <i class="bi bi-info-circle-fill"></i> صف الطعام أو اسأل عن السعرات — مثال: "طبق كوشري كبير" أو "كم سعرة في شاورما دجاج؟"
        </div>
        <textarea id="text-query-input" class="form-control" rows="4"
          placeholder="اكتب هنا... مثال: فطير مشلتت بالعسل قطعتين" style="resize:vertical"></textarea>
        <button class="btn btn-primary btn-full" style="margin-top:0.75rem" onclick="analyzeText()" id="text-analyze-btn">
          <i class="bi bi-robot"></i> تحليل
        </button>
      </div>
    </div>

    <!-- Analysis Result -->
    <div id="result-panel">
      <div class="card" style="text-align:center;padding:3rem 1.5rem">
        <div style="font-size:4rem;margin-bottom:1rem"><i class="bi bi-egg-fried"></i></div>
        <div style="font-size:1.1rem;font-weight:600;color:var(--text-muted)">في انتظار تصوير الطعام...</div>
        <div style="font-size:0.9rem;color:var(--text-muted);margin-top:8px">
          صوّر طعامك أو ارفع صورة وسيحدد الذكاء الاصطناعي السعرات الحرارية
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Camera Logs -->
  <div class="card" style="margin-top:1.5rem">
    <div class="card-title"><i class="bi bi-clock-fill"></i> آخر الوجبات المحللة</div>
    <div id="recent-logs" style="display:flex;flex-direction:column;gap:8px">
      <div style="text-align:center;padding:1.5rem;color:var(--text-muted)">لا توجد وجبات محللة بعد</div>
    </div>
  </div>
</div>

<!-- Confirm Add Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-check-circle-fill"></i> تأكيد إضافة الوجبة</div>
      <button class="modal-close" onclick="closeConfirmModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="confirm-content"></div>
    <div id="confirm-alert"></div>
    <form id="confirm-form">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="food_name" id="cf-name">
      <input type="hidden" name="calories" id="cf-cal">
      <input type="hidden" name="protein" id="cf-protein">
      <input type="hidden" name="carbs" id="cf-carbs">
      <input type="hidden" name="fat" id="cf-fat">
      <div class="form-row" style="margin-bottom:1rem">
        <div class="form-group">
          <label class="form-label">نوع الوجبة</label>
          <select name="meal_type" class="form-control">
            <option value="breakfast">فطار</option>
            <option value="lunch">غداء</option>
            <option value="dinner">عشاء</option>
            <option value="snack" selected>سناك</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">التاريخ</label>
          <input type="date" name="log_date" class="form-control" id="cf-date">
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-success" style="flex:1"><i class="bi bi-check-circle-fill"></i> إضافة للسجل</button>
        <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">إلغاء</button>
      </div>
    </form>
  </div>
</div>

<script>
let stream = null;
let facingMode = 'environment';
let capturedImageBase64 = null;
let uploadedImageBase64 = null;

async function startCamera() {
  try {
    if (stream) stopCamera();
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
    });
    const video = document.getElementById('camera-video');
    video.srcObject = stream;
    document.getElementById('stop-cam-btn').style.display = '';
    document.getElementById('scan-line').style.display = '';

    const devices = await navigator.mediaDevices.enumerateDevices();
    const cams = devices.filter(d => d.kind === 'videoinput');
    if (cams.length > 1) document.getElementById('switch-cam-btn').style.display = '';
  } catch(err) {
    showResultPanel(`<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> لا يمكن الوصول للكاميرا: ${err.message}</div>`, false);
  }
}

function stopCamera() {
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  document.getElementById('stop-cam-btn').style.display = 'none';
  document.getElementById('switch-cam-btn').style.display = 'none';
}

function switchCamera() {
  facingMode = facingMode === 'environment' ? 'user' : 'environment';
  startCamera();
}

async function capturePhoto() {
  const video  = document.getElementById('camera-video');
  const canvas = document.getElementById('camera-canvas');
  canvas.width  = video.videoWidth  || 640;
  canvas.height = video.videoHeight || 480;
  canvas.getContext('2d').drawImage(video, 0, 0);
  capturedImageBase64 = canvas.toDataURL('image/jpeg', 0.85);
  await analyzeImage(capturedImageBase64);
}

function setMode(mode) {
  document.getElementById('camera-mode').style.display  = mode==='camera' ? '' : 'none';
  document.getElementById('upload-mode').style.display  = mode==='upload' ? '' : 'none';
  document.getElementById('text-mode').style.display    = mode==='text'   ? '' : 'none';
  document.querySelectorAll('.tabs .tab-btn').forEach((b,i) =>
    b.classList.toggle('active', (i===0&&mode==='camera')||(i===1&&mode==='upload')||(i===2&&mode==='text'))
  );
  if (mode === 'camera') startCamera(); else stopCamera();
}

async function analyzeText() {
  const input = document.getElementById('text-query-input');
  const query = input.value.trim();
  if (!query) return;

  const btn = document.getElementById('text-analyze-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0"></div> جاري التحليل...';

  const fd = new FormData();
  fd.append('text_query', query);

  try {
    const res  = await fetch('api/analyze.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) {
      showResultPanel(`<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`, false);
    } else {
      showAnalysisResult(data.data, null, data.consensus_info || null);
    }
  } catch(err) {
    showResultPanel(`<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> خطأ في الاتصال بالخادم</div>`, false);
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-robot"></i> تحليل';
}

function handleFileUpload(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (ev) => {
    uploadedImageBase64 = ev.target.result;
    document.getElementById('preview-img').src = uploadedImageBase64;
    document.getElementById('upload-preview').style.display = '';
    document.getElementById('upload-zone').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function resetUpload() {
  uploadedImageBase64 = null;
  document.getElementById('file-input').value = '';
  document.getElementById('upload-preview').style.display = 'none';
  document.getElementById('upload-zone').style.display = '';
}

async function analyzeUploadedImage() {
  if (!uploadedImageBase64) return;
  const btn = document.getElementById('analyze-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:18px;height:18px;border-width:2px;margin:0"></div> جاري التحليل...';
  await analyzeImage(uploadedImageBase64);
  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-robot"></i> تحليل الصورة';
}

async function analyzeImage(base64) {
  document.getElementById('analyzing-overlay').style.display = 'flex';

  const fd = new FormData();
  fd.append('image_base64', base64);

  try {
    const res  = await fetch('api/analyze.php', { method: 'POST', body: fd });
    const data = await res.json();

    document.getElementById('analyzing-overlay').style.display = 'none';

    if (!data.success) {
      showResultPanel(`<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`, false);
      return;
    }

    showAnalysisResult(data.data, base64, data.consensus_info || null);
  } catch(err) {
    document.getElementById('analyzing-overlay').style.display = 'none';
    showResultPanel(`<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> خطأ في الاتصال بالخادم</div>`, false);
  }
}

function showAnalysisResult(r, imageBase64, consensusInfo) {
  const confIcon = r.confidence === 'high'
    ? '<i class="bi bi-check-circle-fill" style="color:var(--success)"></i> دقة عالية'
    : r.confidence === 'medium'
      ? '<i class="bi bi-exclamation-circle-fill" style="color:var(--warning)"></i> دقة متوسطة'
      : '<i class="bi bi-exclamation-triangle-fill" style="color:var(--danger)"></i> تقدير تقريبي';

  let consensusBadge = '';
  if (consensusInfo) {
    if (consensusInfo.mode === 'consensus' && consensusInfo.providers_used >= 2) {
      const spreadColor = consensusInfo.cal_spread_pct < 15 ? 'var(--success)' : consensusInfo.cal_spread_pct < 35 ? 'var(--warning)' : 'var(--danger)';
      const providerList = (consensusInfo.providers || []).map(p =>
        `<span style="font-size:0.75rem;background:var(--bg-card2);border:1px solid var(--border);border-radius:6px;padding:2px 8px">${p.provider} <strong style="color:var(--primary)">${p.calories}</strong></span>`
      ).join(' ');
      consensusBadge = `
        <div style="background:rgba(108,99,255,0.08);border:1px solid rgba(108,99,255,0.25);border-radius:10px;padding:10px 14px;margin-bottom:1rem;font-size:0.83rem">
          <div style="font-weight:700;color:var(--primary);margin-bottom:6px">
            <i class="bi bi-diagram-3-fill"></i> نتيجة Consensus — ${consensusInfo.providers_used} نماذج
            <span style="font-weight:400;color:${spreadColor};margin-right:8px">(فرق ${consensusInfo.cal_spread_pct}%)</span>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:4px">${providerList}</div>
        </div>`;
    } else if (consensusInfo.mode === 'single') {
      consensusBadge = `
        <div style="background:rgba(255,179,71,0.08);border:1px solid rgba(255,179,71,0.2);border-radius:10px;padding:8px 12px;margin-bottom:1rem;font-size:0.82rem;color:var(--warning)">
          <i class="bi bi-exclamation-triangle-fill"></i> نجح مزود واحد فقط من ${consensusInfo.providers_total} — الثقة منخفضة
        </div>`;
    }
  }

  const html = `
    <div class="card" style="border-color:var(--success)">
      <div style="display:flex;gap:1rem;margin-bottom:1rem;align-items:center">
        ${imageBase64 ? `<img src="${imageBase64}" style="width:80px;height:80px;object-fit:cover;border-radius:12px;flex-shrink:0">` : ''}
        <div>
          <div style="font-size:1.2rem;font-weight:700">${escHtml(r.food_name)}</div>
          <div style="color:var(--text-muted);font-size:0.9rem;margin-top:4px">${escHtml(r.description || '')}</div>
          <span class="badge badge-success" style="margin-top:6px">${confIcon}</span>
        </div>
      </div>

      ${consensusBadge}

      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;margin-bottom:1.5rem">
        <div class="macro-pill" style="border-color:var(--primary)">
          <div class="macro-value" style="color:var(--primary)">${r.calories}</div>
          <div class="macro-label"><i class="bi bi-fire"></i> كالوري</div>
        </div>
        <div class="macro-pill">
          <div class="macro-value" style="color:var(--warning)">${r.protein}g</div>
          <div class="macro-label"><i class="bi bi-lightning-fill"></i> بروتين</div>
        </div>
        <div class="macro-pill">
          <div class="macro-value" style="color:var(--secondary)">${r.carbs}g</div>
          <div class="macro-label"><i class="bi bi-layers-fill"></i> كارب</div>
        </div>
        <div class="macro-pill">
          <div class="macro-value" style="color:var(--success)">${r.fat}g</div>
          <div class="macro-label"><i class="bi bi-droplet-fill"></i> دهون</div>
        </div>
      </div>

      <button class="btn btn-primary btn-full" onclick="openConfirmModal(${JSON.stringify(r).replace(/"/g,'&quot;')})">
        <i class="bi bi-plus-lg"></i> إضافة للسجل
      </button>
    </div>
  `;
  document.getElementById('result-panel').innerHTML = html;
}

function showResultPanel(html, isCard = true) {
  document.getElementById('result-panel').innerHTML = isCard
    ? `<div class="card">${html}</div>`
    : html;
}

function openConfirmModal(r) {
  document.getElementById('cf-name').value    = r.food_name;
  document.getElementById('cf-cal').value     = r.calories;
  document.getElementById('cf-protein').value = r.protein;
  document.getElementById('cf-carbs').value   = r.carbs;
  document.getElementById('cf-fat').value     = r.fat;
  document.getElementById('cf-date').value    = new Date().toISOString().split('T')[0];
  document.getElementById('confirm-content').innerHTML = `
    <div class="alert alert-info" style="margin-bottom:1rem">
      <i class="bi bi-egg-fried"></i> <strong>${escHtml(r.food_name)}</strong> — ${r.calories} كالوري
    </div>
  `;
  document.getElementById('confirm-alert').innerHTML = '';
  document.getElementById('confirm-modal').classList.add('open');
}

function closeConfirmModal() {
  document.getElementById('confirm-modal').classList.remove('open');
}

document.getElementById('confirm-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const res  = await fetch('api/food.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    closeConfirmModal();
    showResultPanel(`<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> تمت إضافة الوجبة بنجاح!</div>
      <div class="card" style="margin-top:1rem;text-align:center">
        <div style="font-size:3rem;margin-bottom:8px"><i class="bi bi-stars"></i></div>
        <div style="font-weight:600">تمت الإضافة!</div>
        <a href="index.php" class="btn btn-primary" style="margin-top:1rem">عرض اليوم</a>
      </div>`);
    loadRecentLogs();
  } else {
    document.getElementById('confirm-alert').innerHTML =
      `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
});

async function loadRecentLogs() {
  const res  = await fetch(`api/food.php?action=today&date=${new Date().toISOString().split('T')[0]}`);
  const data = await res.json();
  if (!data.success || data.logs.length === 0) return;

  const icons = {
    breakfast:'<i class="bi bi-sunrise"></i>',
    lunch:'<i class="bi bi-sun-fill"></i>',
    dinner:'<i class="bi bi-moon-fill"></i>',
    snack:'<i class="bi bi-apple"></i>'
  };
  const recent = document.getElementById('recent-logs');
  recent.innerHTML = data.logs.slice(0,5).map(log => `
    <div class="food-item">
      <div class="food-item-icon">${icons[log.meal_type]||'<i class="bi bi-egg-fried"></i>'}</div>
      <div class="food-item-info">
        <div class="food-item-name">${escHtml(log.food_name)}</div>
        <div class="food-item-meta">
          <i class="bi bi-lightning-fill"></i> ${log.protein}g &nbsp;
          <i class="bi bi-layers-fill"></i> ${log.carbs}g &nbsp;
          <i class="bi bi-droplet-fill"></i> ${log.fat}g
        </div>
      </div>
      <div class="food-item-cal">${log.calories}</div>
    </div>
  `).join('');
}

// Drag & Drop
const uploadZone = document.getElementById('upload-zone');
if (uploadZone) {
  uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
  uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
  uploadZone.addEventListener('drop', e => {
    e.preventDefault(); uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      const input = document.getElementById('file-input');
      const dt = new DataTransfer(); dt.items.add(file);
      input.files = dt.files;
      handleFileUpload({ target: input });
    }
  });
}

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('api/auth.php', {method:'POST', body:fd});
  location.href = 'login.php';
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])
  );
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('main-content').style.opacity = '1';
  startCamera();
  loadRecentLogs();
});

window.addEventListener('beforeunload', stopCamera);
</script>
<?php include 'includes/mobile_nav.php'; ?>
</body>
</html>
