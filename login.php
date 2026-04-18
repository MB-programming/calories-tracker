<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول - CalTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js" defer></script>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card" id="auth-card">
    <div class="auth-logo">
      <span class="icon">🔥</span>
      <h1>CalTrack</h1>
      <p>تتبع سعراتك الحرارية بذكاء</p>
    </div>

    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('login')">تسجيل الدخول</button>
      <button class="tab-btn" onclick="showTab('register')">حساب جديد</button>
    </div>

    <div id="alert-box"></div>

    <!-- Login Form -->
    <div id="tab-login">
      <form id="login-form">
        <div class="form-group">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">كلمة المرور</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="login-btn">
          <span>🚀</span> دخول
        </button>
      </form>
    </div>

    <!-- Register Form -->
    <div id="tab-register" style="display:none">
      <form id="register-form">
        <div class="form-group">
          <label class="form-label">الاسم الكامل</label>
          <input type="text" name="name" class="form-control" placeholder="اسمك" required>
        </div>
        <div class="form-group">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">كلمة المرور</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label">الهدف اليومي من السعرات (كالوري)</label>
          <input type="number" name="daily_goal" class="form-control" value="2000" min="500" max="10000">
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="register-btn">
          <span>✨</span> إنشاء الحساب
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function showAlert(msg, type='error') {
  document.getElementById('alert-box').innerHTML =
    `<div class="alert alert-${type}">${type==='success'?'✅':'❌'} ${msg}</div>`;
}

function showTab(tab) {
  document.getElementById('tab-login').style.display    = tab==='login'    ? '' : 'none';
  document.getElementById('tab-register').style.display = tab==='register' ? '' : 'none';
  document.querySelectorAll('.tab-btn').forEach((b,i) =>
    b.classList.toggle('active', (i===0&&tab==='login')||(i===1&&tab==='register'))
  );
  document.getElementById('alert-box').innerHTML = '';
}

document.getElementById('login-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.innerHTML = '<div class="spinner" style="width:20px;height:20px;border-width:2px;margin:0"></div>';
  const fd = new FormData(e.target);
  fd.append('action','login');
  const res = await fetch('api/auth.php', {method:'POST',body:fd});
  const data = await res.json();
  if (data.success) {
    showAlert('تم تسجيل الدخول بنجاح! جاري التحويل...','success');
    setTimeout(() => location.href = data.redirect || 'index.php', 800);
  } else {
    showAlert(data.message);
    btn.disabled = false; btn.innerHTML = '<span>🚀</span> دخول';
  }
});

document.getElementById('register-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('register-btn');
  btn.disabled = true; btn.innerHTML = '<div class="spinner" style="width:20px;height:20px;border-width:2px;margin:0"></div>';
  const fd = new FormData(e.target);
  fd.append('action','register');
  const res = await fetch('api/auth.php', {method:'POST',body:fd});
  const data = await res.json();
  if (data.success) {
    showAlert('تم إنشاء الحساب! جاري إعداد ملفك الشخصي...','success');
    setTimeout(() => location.href = data.redirect || 'setup_profile.php', 900);
  } else {
    showAlert(data.message);
    btn.disabled = false; btn.innerHTML = '<span>✨</span> إنشاء الحساب';
  }
});

// Framer Motion entrance animation
window.addEventListener('load', () => {
  if (window.Motion) {
    window.Motion.animate('#auth-card',
      { opacity: [0, 1], y: [30, 0], scale: [0.95, 1] },
      { duration: 0.6, easing: [0.4, 0, 0.2, 1] }
    );
  }
});
</script>
</body>
</html>
