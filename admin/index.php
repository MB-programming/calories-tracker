<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();
$userName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة الإدارة - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.admin-nav-link { display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:var(--text-muted);transition:var(--transition);cursor:pointer; }
.admin-nav-link:hover, .admin-nav-link.active { background:rgba(108,99,255,0.15);color:var(--primary); }
.section { display:none; }
.section.active { display:block; }
.setting-row { display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border); }
.setting-row:last-child { border-bottom:none; }
.setting-info { flex:1; }
.setting-label { font-weight:600;font-size:0.95rem; }
.setting-desc  { font-size:0.82rem;color:var(--text-muted);margin-top:3px; }
.setting-control { flex-shrink:0;margin-right:1.5rem; }
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand"><span>🔥</span><span>Cal<span style="color:var(--secondary)">Track</span></span> <span style="font-size:0.75rem;background:var(--primary);color:#fff;padding:2px 8px;border-radius:20px;margin-right:6px">Admin</span></div>
  <ul class="navbar-nav">
    <li><a href="../index.php">🏠 العودة للموقع</a></li>
  </ul>
  <div class="navbar-user">
    <div class="avatar"><?= mb_substr($userName,0,1) ?></div>
    <span><?= htmlspecialchars($userName) ?></span>
    <button class="btn btn-secondary btn-sm" onclick="logout()">خروج</button>
  </div>
</nav>

<div class="admin-layout">
  <!-- Sidebar -->
  <div class="admin-sidebar">
    <div style="margin-bottom:1.5rem;padding:0 0.5rem">
      <div style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;letter-spacing:1px">القائمة</div>
    </div>
    <ul class="sidebar-nav" style="list-style:none">
      <li><div class="admin-nav-link active" onclick="showSection('dashboard')">📊 لوحة التحكم</div></li>
      <li><div class="admin-nav-link" onclick="showSection('settings')">⚙️ إعدادات الذكاء الاصطناعي</div></li>
      <li><div class="admin-nav-link" onclick="showSection('users')">👥 إدارة المستخدمين</div></li>
      <li><div class="admin-nav-link" onclick="showSection('app-settings')">🛠️ إعدادات التطبيق</div></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="admin-content" id="main-content" style="opacity:0">

    <!-- Dashboard Section -->
    <section class="section active" id="section-dashboard">
      <div class="page-header">
        <div class="page-title">📊 لوحة التحكم</div>
        <div class="page-subtitle">نظرة عامة على الموقع</div>
      </div>

      <div class="stats-grid" id="admin-stats">
        <div class="stat-card" style="--accent:var(--primary)">
          <div class="stat-icon">👥</div>
          <div class="stat-value" id="st-users">-</div>
          <div class="stat-label">إجمالي المستخدمين</div>
        </div>
        <div class="stat-card" style="--accent:var(--success)">
          <div class="stat-icon">✅</div>
          <div class="stat-value" id="st-active">-</div>
          <div class="stat-label">نشطون اليوم</div>
        </div>
        <div class="stat-card" style="--accent:var(--warning)">
          <div class="stat-icon">📝</div>
          <div class="stat-value" id="st-logs">-</div>
          <div class="stat-label">تسجيلات اليوم</div>
        </div>
        <div class="stat-card" style="--accent:var(--secondary)">
          <div class="stat-icon">🔥</div>
          <div class="stat-value" id="st-cal">-</div>
          <div class="stat-label">كالوري مسجلة اليوم</div>
        </div>
      </div>

      <div class="card" style="margin-top:1.5rem">
        <div class="card-title">📈 السعرات - آخر 7 أيام (كل المستخدمين)</div>
        <div class="chart-bar-wrap" id="admin-chart" style="height:140px;gap:8px;align-items:flex-end"></div>
      </div>
    </section>

    <!-- AI Settings Section -->
    <section class="section" id="section-settings">
      <div class="page-header">
        <div class="page-title">⚙️ إعدادات الذكاء الاصطناعي</div>
        <div class="page-subtitle">تحكم في نموذج الذكاء الاصطناعي المستخدم لتحليل الصور</div>
      </div>

      <div class="card">
        <div id="settings-alert"></div>
        <form id="ai-settings-form">
          <div class="setting-row">
            <div class="setting-info">
              <div class="setting-label">مزود الذكاء الاصطناعي</div>
              <div class="setting-desc">اختر الخدمة المستخدمة لتحليل صور الطعام</div>
            </div>
            <div class="setting-control">
              <select name="ai_provider" class="form-control" style="width:220px" onchange="toggleProviderFields(this.value)">
                <option value="gemini">Google Gemini (مجاني)</option>
              </select>
            </div>
          </div>

          <div id="gemini-fields">
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">مفتاح Gemini API</div>
                <div class="setting-desc">احصل على مفتاح مجاني من <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--primary)">Google AI Studio</a></div>
              </div>
              <div class="setting-control">
                <input type="text" name="gemini_api_key" id="gemini-api-key" class="form-control"
                  style="width:280px" placeholder="AIzaSy...">
              </div>
            </div>

            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">نموذج Gemini</div>
                <div class="setting-desc">اختر دقة التحليل (Flash أسرع، Pro أدق)</div>
              </div>
              <div class="setting-control">
                <select name="gemini_model" id="gemini-model" class="form-control" style="width:220px">
                  <option value="gemini-1.5-flash">gemini-1.5-flash (مجاني - سريع)</option>
                  <option value="gemini-1.5-pro">gemini-1.5-pro (مجاني - أدق)</option>
                  <option value="gemini-2.0-flash">gemini-2.0-flash (أحدث)</option>
                  <option value="gemini-2.0-flash-lite">gemini-2.0-flash-lite (أسرع)</option>
                </select>
              </div>
            </div>
          </div>

          <div style="margin-top:1.5rem;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">💾 حفظ الإعدادات</button>
            <button type="button" class="btn btn-secondary" onclick="testConnection()">🔌 اختبار الاتصال</button>
          </div>
        </form>

        <div id="test-result" style="margin-top:1rem"></div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title">📘 دليل الإعداد</div>
        <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
          <li>اذهب إلى <strong style="color:var(--primary)">Google AI Studio</strong></li>
          <li>سجل الدخول بحسابك Google</li>
          <li>اضغط على "Get API Key" وأنشئ مفتاح جديد</li>
          <li>انسخ المفتاح والصقه في حقل "مفتاح Gemini API" أعلاه</li>
          <li>اضغط "حفظ الإعدادات" ثم "اختبار الاتصال"</li>
          <li>استخدم <strong>gemini-1.5-flash</strong> للاستخدام المجاني (حتى 15 طلب/دقيقة)</li>
        </ol>
      </div>
    </section>

    <!-- Users Section -->
    <section class="section" id="section-users">
      <div class="page-header">
        <div class="page-title">👥 إدارة المستخدمين</div>
        <div class="page-subtitle">عرض وإدارة جميع المستخدمين</div>
      </div>

      <div class="card">
        <div id="users-alert"></div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>الدور</th>
                <th>الهدف</th>
                <th>الوجبات</th>
                <th>اليوم</th>
                <th>الإجراءات</th>
              </tr>
            </thead>
            <tbody id="users-table">
              <tr><td colspan="8" style="text-align:center;padding:2rem"><div class="spinner" style="margin:auto"></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- App Settings Section -->
    <section class="section" id="section-app-settings">
      <div class="page-header">
        <div class="page-title">🛠️ إعدادات التطبيق</div>
        <div class="page-subtitle">إعدادات عامة للتطبيق</div>
      </div>

      <div class="card">
        <div id="app-settings-alert"></div>
        <form id="app-settings-form">
          <div class="form-group">
            <label class="form-label">اسم التطبيق</label>
            <input type="text" name="app_name" id="app-name" class="form-control" placeholder="CalTrack">
          </div>
          <div class="form-group">
            <label class="form-label">الهدف اليومي الافتراضي (كالوري)</label>
            <input type="number" name="default_daily_goal" id="default-goal" class="form-control" placeholder="2000" min="500" max="10000">
          </div>
          <button type="submit" class="btn btn-primary">💾 حفظ</button>
        </form>
      </div>
    </section>

  </div>
</div>

<script>
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.admin-nav-link').forEach(l => l.classList.remove('active'));
  document.getElementById('section-' + name).classList.add('active');
  event.currentTarget.classList.add('active');

  if (name === 'users') loadUsers();
  if (name === 'settings') loadSettings();
  if (name === 'app-settings') loadSettings();
}

async function loadDashboard() {
  const res  = await fetch('../api/admin.php?action=stats');
  const data = await res.json();
  if (!data.success) return;
  const s = data.stats;
  document.getElementById('st-users').textContent  = s.total_users;
  document.getElementById('st-active').textContent = s.active_today;
  document.getElementById('st-logs').textContent   = s.today_logs;
  document.getElementById('st-cal').textContent    = parseInt(s.today_calories).toLocaleString();

  // Chart
  if (s.week_chart && s.week_chart.length > 0) {
    const chart = document.getElementById('admin-chart');
    const max   = Math.max(...s.week_chart.map(d => parseInt(d.total)), 1);
    const days  = ['أح','إث','ث','أر','خ','ج','س'];
    chart.innerHTML = s.week_chart.map(d => {
      const h   = Math.max(4, (parseInt(d.total) / max) * 120);
      const date = new Date(d.log_date + 'T00:00:00');
      return `<div class="chart-bar-col" style="min-width:36px">
        <div style="font-size:0.7rem;color:var(--text-muted)">${parseInt(d.total).toLocaleString()}</div>
        <div class="chart-bar" style="height:${h}px"></div>
        <div class="chart-bar-label">${days[date.getDay()]}</div>
      </div>`;
    }).join('');
  }
}

async function loadSettings() {
  const res  = await fetch('../api/admin.php?action=get_settings');
  const data = await res.json();
  if (!data.success) return;
  const s = data.settings;

  const providerEl = document.querySelector('[name="ai_provider"]');
  const modelEl    = document.getElementById('gemini-model');
  const apiKeyEl   = document.getElementById('gemini-api-key');
  const appNameEl  = document.getElementById('app-name');
  const goalEl     = document.getElementById('default-goal');

  if (providerEl && s.ai_provider) providerEl.value     = s.ai_provider;
  if (modelEl    && s.gemini_model)  modelEl.value       = s.gemini_model;
  if (apiKeyEl   && s.gemini_api_key) apiKeyEl.value     = s.gemini_api_key;
  if (appNameEl  && s.app_name)      appNameEl.value     = s.app_name;
  if (goalEl     && s.default_daily_goal) goalEl.value   = s.default_daily_goal;
}

async function loadUsers() {
  const res  = await fetch('../api/admin.php?action=get_users');
  const data = await res.json();
  if (!data.success) return;

  const tbody = document.getElementById('users-table');
  tbody.innerHTML = data.users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td><strong>${escHtml(u.name)}</strong></td>
      <td style="color:var(--text-muted)">${escHtml(u.email)}</td>
      <td>
        <span class="badge ${u.role === 'admin' ? 'badge-primary' : 'badge-success'}">
          ${u.role === 'admin' ? '⚙️ مشرف' : '👤 مستخدم'}
        </span>
      </td>
      <td>${u.daily_goal}</td>
      <td>${u.total_logs}</td>
      <td>${parseInt(u.today_calories).toLocaleString()}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-sm btn-secondary" onclick="toggleRole(${u.id}, '${u.role}')">
            ${u.role === 'admin' ? '⬇️ رجوع مستخدم' : '⬆️ ترقية مشرف'}
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.id}, '${escHtml(u.name)}')">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function toggleRole(id, currentRole) {
  const newRole = currentRole === 'admin' ? 'user' : 'admin';
  const fd = new FormData();
  fd.append('action','update_user_role'); fd.append('id',id); fd.append('role',newRole);
  await fetch('../api/admin.php', {method:'POST', body:fd});
  loadUsers();
}

async function deleteUser(id, name) {
  if (!confirm(`حذف المستخدم "${name}"؟ سيتم حذف جميع بياناته.`)) return;
  const fd = new FormData();
  fd.append('action','delete_user'); fd.append('id',id);
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  if (data.success) {
    loadUsers();
  } else {
    document.getElementById('users-alert').innerHTML = `<div class="alert alert-error">❌ ${data.message}</div>`;
  }
}

document.getElementById('ai-settings-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','save_settings');
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('settings-alert').innerHTML =
    `<div class="alert alert-${data.success ? 'success' : 'error'}">${data.success ? '✅' : '❌'} ${data.message}</div>`;
});

document.getElementById('app-settings-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','save_settings');
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('app-settings-alert').innerHTML =
    `<div class="alert alert-${data.success ? 'success' : 'error'}">${data.success ? '✅' : '❌'} ${data.message}</div>`;
});

async function testConnection() {
  const btn = event.target;
  btn.disabled = true; btn.textContent = '⏳ جاري الاختبار...';
  document.getElementById('test-result').innerHTML = '';

  const fd = new FormData();
  // Send a tiny test image (1px white pixel) to test API connectivity
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = 'white'; ctx.fillRect(0,0,1,1);
  fd.append('image_base64', canvas.toDataURL('image/jpeg'));

  try {
    const res  = await fetch('../api/analyze.php', {method:'POST', body:fd});
    const data = await res.json();
    // Either success or "can't recognize food" means API works
    if (data.success || data.message?.includes('التعرف')) {
      document.getElementById('test-result').innerHTML =
        '<div class="alert alert-success">✅ الاتصال يعمل بشكل صحيح!</div>';
    } else if (data.message?.includes('مفتاح API')) {
      document.getElementById('test-result').innerHTML =
        '<div class="alert alert-error">❌ مفتاح API غير صحيح أو غير محفوظ</div>';
    } else {
      document.getElementById('test-result').innerHTML =
        `<div class="alert alert-success">✅ الاتصال يعمل (${data.message})</div>`;
    }
  } catch(err) {
    document.getElementById('test-result').innerHTML =
      '<div class="alert alert-error">❌ خطأ في الاتصال - تأكد من إعدادات الخادم</div>';
  }

  btn.disabled = false; btn.textContent = '🔌 اختبار الاتصال';
}

function toggleProviderFields(provider) {
  document.getElementById('gemini-fields').style.display = provider === 'gemini' ? '' : 'none';
}

function escHtml(str) {
  return String(str||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

async function logout() {
  const fd = new FormData(); fd.append('action','logout');
  await fetch('../api/auth.php', {method:'POST', body:fd});
  location.href = '../login.php';
}

window.addEventListener('load', () => {
  const main = document.getElementById('main-content');
  main.style.opacity = '1';
  if (window.Motion) {
    window.Motion.animate('#main-content', { opacity:[0,1], y:[20,0] }, { duration:0.5, easing:[0.4,0,0.2,1] });
    window.Motion.animate('.stat-card', { opacity:[0,1], scale:[0.95,1] },
      { duration:0.4, delay:window.Motion.stagger(0.1), easing:[0.4,0,0.2,1] });
  }
  loadDashboard();
  loadSettings();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js"></script>
</body>
</html>
