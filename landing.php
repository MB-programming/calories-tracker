<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CalTrack — تتبع سعراتك بذكاء</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
  --primary: #6C63FF;
  --primary-dark: #574fd6;
  --secondary: #FF6584;
  --success: #43D98F;
  --warning: #FFB347;
  --bg: #0F0F1A;
  --bg-card: #1A1A2E;
  --bg-card2: #16213E;
  --text: #E0E0FF;
  --text-muted: #8888aa;
  --border: #2a2a4a;
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family: 'Cairo', 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  overflow-x: hidden;
  direction: rtl;
}

a { text-decoration: none; color: inherit; }

/* ─── Navbar ─── */
.lp-nav {
  position: fixed; top: 0; inset-inline: 0;
  z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 2rem; height: 64px;
  background: rgba(15,15,26,0.8);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(108,99,255,0.15);
}
.lp-brand {
  display: flex; align-items: center; gap: 10px;
  font-size: 1.3rem; font-weight: 700; color: var(--primary);
}
.lp-nav-links { display: flex; align-items: center; gap: 8px; list-style: none; }
.lp-nav-links a {
  padding: 8px 16px; border-radius: 10px;
  font-size: 0.9rem; color: var(--text-muted);
  transition: 0.3s;
}
.lp-nav-links a:hover { color: var(--primary); background: rgba(108,99,255,0.1); }
.btn-cta {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff !important;
  padding: 9px 20px; border-radius: 10px;
  font-weight: 600; font-size: 0.9rem;
  box-shadow: 0 4px 15px rgba(108,99,255,0.4);
  transition: 0.3s !important;
}
.btn-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(108,99,255,0.5) !important; }

/* ─── Hero ─── */
.hero {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  text-align: center;
  padding: 6rem 2rem 4rem;
  position: relative;
  overflow: hidden;
}

.hero-bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.orb1 { width: 600px; height: 600px; background: rgba(108,99,255,0.12); top: -100px; right: -150px; }
.orb2 { width: 400px; height: 400px; background: rgba(255,101,132,0.08); bottom: -50px; left: -100px; }
.orb3 { width: 300px; height: 300px; background: rgba(67,217,143,0.07); top: 40%; left: 50%; transform: translate(-50%,-50%); }

.hero-content { position: relative; z-index: 2; max-width: 800px; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.3);
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 0.85rem; color: var(--primary); font-weight: 600;
  margin-bottom: 1.5rem;
}

.hero-title {
  font-size: clamp(2.8rem, 6vw, 5rem);
  font-weight: 900;
  line-height: 1.1;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #fff 0%, var(--primary) 60%, var(--secondary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  font-size: 1.15rem;
  color: var(--text-muted);
  line-height: 1.7;
  max-width: 560px;
  margin: 0 auto 2.5rem;
}

.hero-actions {
  display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
}

.btn-hero-primary {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  padding: 14px 32px; border-radius: 14px;
  font-size: 1.05rem; font-weight: 700;
  box-shadow: 0 8px 30px rgba(108,99,255,0.5);
  transition: 0.3s;
}
.btn-hero-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(108,99,255,0.6); }

.btn-hero-secondary {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.05);
  border: 1.5px solid rgba(255,255,255,0.15);
  color: var(--text);
  padding: 14px 32px; border-radius: 14px;
  font-size: 1.05rem; font-weight: 600;
  transition: 0.3s;
}
.btn-hero-secondary:hover { border-color: var(--primary); color: var(--primary); background: rgba(108,99,255,0.08); }

/* ─── Phone 3D mockup ─── */
.hero-phone-wrap {
  margin-top: 4rem;
  perspective: 1200px;
}
.hero-phone {
  width: 280px;
  margin: 0 auto;
  background: linear-gradient(145deg, #1e1e3a, #12122a);
  border: 2px solid rgba(108,99,255,0.3);
  border-radius: 36px;
  padding: 16px;
  box-shadow:
    0 60px 120px rgba(0,0,0,0.6),
    0 0 60px rgba(108,99,255,0.2),
    inset 0 1px 0 rgba(255,255,255,0.05);
  transform: rotateX(8deg) rotateY(-4deg);
  transition: transform 0.4s ease;
}
.hero-phone:hover { transform: rotateX(0deg) rotateY(0deg); }

.phone-screen {
  background: var(--bg);
  border-radius: 26px;
  padding: 1rem;
  overflow: hidden;
}

.phone-top-bar {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 0.75rem;
  font-size: 0.7rem; color: var(--text-muted);
}

.phone-stat-row {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 8px; margin-bottom: 8px;
}
.phone-stat {
  background: var(--bg-card);
  border-radius: 10px; padding: 8px;
  text-align: center;
}
.phone-stat-val { font-size: 1rem; font-weight: 700; color: var(--primary); }
.phone-stat-lbl { font-size: 0.6rem; color: var(--text-muted); }

.phone-food-item {
  display: flex; align-items: center; gap: 6px;
  background: var(--bg-card);
  border-radius: 8px; padding: 6px 8px;
  margin-bottom: 5px; font-size: 0.65rem;
}
.phone-food-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  flex-shrink: 0;
}

/* ─── Features Section ─── */
.section-wrap { padding: 6rem 2rem; max-width: 1200px; margin: 0 auto; }

.section-label {
  display: inline-block;
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.25);
  border-radius: 20px;
  padding: 4px 14px;
  font-size: 0.8rem; font-weight: 700; color: var(--primary);
  text-transform: uppercase; letter-spacing: 1px;
  margin-bottom: 1rem;
}

.section-title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.section-subtitle {
  color: var(--text-muted);
  font-size: 1rem;
  line-height: 1.7;
  max-width: 500px;
  margin-bottom: 3rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.feature-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 2rem;
  position: relative; overflow: hidden;
  transition: 0.3s cubic-bezier(0.4,0,0.2,1);
}
.feature-card::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(108,99,255,0.04) 0%, transparent 60%);
  opacity: 0; transition: 0.3s;
}
.feature-card:hover { transform: translateY(-6px); border-color: rgba(108,99,255,0.4); box-shadow: 0 20px 60px rgba(108,99,255,0.15); }
.feature-card:hover::before { opacity: 1; }

.feature-icon {
  width: 56px; height: 56px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem;
  margin-bottom: 1.25rem;
}

.feature-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
.feature-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }

/* ─── How It Works ─── */
.steps-wrap {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 2rem;
  counter-reset: step;
}

.step-card {
  text-align: center;
  position: relative;
}

.step-num {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; font-weight: 900; color: #fff;
  margin: 0 auto 1rem;
  box-shadow: 0 8px 24px rgba(108,99,255,0.4);
}

.step-title { font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
.step-desc { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

/* ─── Stats / Social Proof ─── */
.stats-row {
  display: flex; flex-wrap: wrap; justify-content: center;
  gap: 3rem;
  padding: 4rem 2rem;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}

.stat-item { text-align: center; }
.stat-big { font-size: 3rem; font-weight: 900; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.stat-lbl { font-size: 0.9rem; color: var(--text-muted); margin-top: 4px; }

/* ─── Providers Section ─── */
.providers-grid {
  display: flex; flex-wrap: wrap; justify-content: center;
  gap: 1rem;
}

.provider-pill {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 12px 20px;
  font-size: 0.9rem; font-weight: 600;
  transition: 0.3s;
}
.provider-pill:hover { border-color: var(--primary); color: var(--primary); }
.provider-pill .icon { font-size: 1.3rem; }

.free-badge {
  background: rgba(67,217,143,0.15);
  color: #43D98F;
  border: 1px solid rgba(67,217,143,0.3);
  border-radius: 8px;
  padding: 2px 8px;
  font-size: 0.72rem; font-weight: 700;
}

/* ─── CTA ─── */
.cta-section {
  text-align: center;
  padding: 6rem 2rem;
  position: relative; overflow: hidden;
}

.cta-card {
  max-width: 700px; margin: 0 auto;
  background: linear-gradient(135deg, rgba(108,99,255,0.12), rgba(255,101,132,0.08));
  border: 1px solid rgba(108,99,255,0.25);
  border-radius: 28px;
  padding: 4rem 3rem;
  position: relative;
}

.cta-title { font-size: clamp(1.8rem, 3.5vw, 2.5rem); font-weight: 800; margin-bottom: 1rem; }
.cta-subtitle { color: var(--text-muted); font-size: 1rem; margin-bottom: 2rem; line-height: 1.7; }

/* ─── Footer ─── */
footer {
  border-top: 1px solid var(--border);
  padding: 2rem;
  text-align: center;
  color: var(--text-muted);
  font-size: 0.88rem;
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
  .lp-nav-links { display: none; }
  .hero { padding: 5rem 1.5rem 3rem; }
  .hero-phone { width: 240px; }
  .hero-actions { flex-direction: column; align-items: center; }
  .stats-row { gap: 2rem; }
  .cta-card { padding: 2.5rem 1.5rem; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="lp-nav" id="lp-nav">
  <div class="lp-brand">
    <i class="bi bi-fire"></i>
    <span>Cal<span style="color:var(--secondary)">Track</span></span>
  </div>
  <ul class="lp-nav-links">
    <li><a href="#features">المميزات</a></li>
    <li><a href="#how">كيف يعمل</a></li>
    <li><a href="#providers">الذكاء الاصطناعي</a></li>
    <li><a href="login.php" class="btn-cta"><i class="bi bi-box-arrow-in-right"></i> ابدأ مجاناً</a></li>
  </ul>
</nav>

<!-- Hero -->
<section class="hero" id="hero-section">
  <div class="hero-bg-orb orb1"></div>
  <div class="hero-bg-orb orb2"></div>
  <div class="hero-bg-orb orb3"></div>

  <div class="hero-content" id="hero-content">
    <div class="hero-badge">
      <i class="bi bi-stars"></i> مدعوم بالذكاء الاصطناعي — مجاناً تماماً
    </div>
    <h1 class="hero-title">تتبع سعراتك<br>بذكاء حقيقي</h1>
    <p class="hero-subtitle">
      صوّر طعامك وسيحلله الذكاء الاصطناعي فوراً — السعرات، البروتين، الكارب، والدهون.
      بدون تخمين، بدون تعب.
    </p>
    <div class="hero-actions">
      <a href="login.php" class="btn-hero-primary">
        <i class="bi bi-rocket-takeoff-fill"></i> ابدأ مجاناً الآن
      </a>
      <a href="#features" class="btn-hero-secondary">
        <i class="bi bi-play-circle-fill"></i> اكتشف المميزات
      </a>
    </div>

    <!-- Phone Mockup -->
    <div class="hero-phone-wrap" id="phone-wrap">
      <div class="hero-phone" id="hero-phone">
        <div class="phone-screen">
          <div class="phone-top-bar">
            <span><i class="bi bi-fire"></i> CalTrack</span>
            <span>1,840 / 2,000</span>
          </div>
          <div class="phone-stat-row">
            <div class="phone-stat">
              <div class="phone-stat-val" style="color:var(--primary)">1,840</div>
              <div class="phone-stat-lbl">كالوري</div>
            </div>
            <div class="phone-stat">
              <div class="phone-stat-val" style="color:var(--warning)">92g</div>
              <div class="phone-stat-lbl">بروتين</div>
            </div>
          </div>
          <div class="phone-food-item"><div class="phone-food-dot"></div><span>فطار — فول مدمس</span><span style="margin-right:auto;color:var(--primary);font-weight:700">320</span></div>
          <div class="phone-food-item"><div class="phone-food-dot" style="background:var(--secondary)"></div><span>غداء — دجاج مشوي</span><span style="margin-right:auto;color:var(--primary);font-weight:700">680</span></div>
          <div class="phone-food-item"><div class="phone-food-dot" style="background:var(--success)"></div><span>عشاء — سلطة</span><span style="margin-right:auto;color:var(--primary);font-weight:700">420</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Bar -->
<div class="stats-row" id="stats-row">
  <div class="stat-item"><div class="stat-big" id="cnt-users">5,000+</div><div class="stat-lbl">مستخدم نشط</div></div>
  <div class="stat-item"><div class="stat-big">1M+</div><div class="stat-lbl">وجبة محللة</div></div>
  <div class="stat-item"><div class="stat-big">5</div><div class="stat-lbl">نماذج ذكاء اصطناعي</div></div>
  <div class="stat-item"><div class="stat-big">100%</div><div class="stat-lbl">مجاني</div></div>
</div>

<!-- Features -->
<section id="features">
  <div class="section-wrap">
    <div class="section-label"><i class="bi bi-stars"></i> المميزات</div>
    <h2 class="section-title">كل ما تحتاجه في مكان واحد</h2>
    <p class="section-subtitle">من تصوير الطعام إلى تحليل التقارير — كل شيء مدعوم بالذكاء الاصطناعي</p>

    <div class="features-grid" id="features-grid">
      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(108,99,255,0.15);color:var(--primary)">
          <i class="bi bi-camera-fill"></i>
        </div>
        <div class="feature-title">تحليل الصور الفوري</div>
        <div class="feature-desc">صوّر طعامك وسيتعرف الذكاء الاصطناعي عليه ويحسب السعرات والمغذيات في ثوانٍ.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(255,101,132,0.15);color:var(--secondary)">
          <i class="bi bi-chat-dots-fill"></i>
        </div>
        <div class="feature-title">اسأل بالنص</div>
        <div class="feature-desc">اكتب "كم سعرة في كوشري كبير؟" أو صف وجبتك بالكلمات وسيجيبك الذكاء الاصطناعي مباشرة.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(67,217,143,0.15);color:var(--success)">
          <i class="bi bi-bar-chart-fill"></i>
        </div>
        <div class="feature-title">تقارير تفصيلية</div>
        <div class="feature-desc">تتبع تقدمك الأسبوعي والشهري مع رسوم بيانية تفاعلية لكل مغذٍّ.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(255,179,71,0.15);color:var(--warning)">
          <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="feature-title">تتبع التمارين</div>
        <div class="feature-desc">سجّل تماريناتك اليومية وتابع السعرات المحروقة مع هدفك اليومي.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(108,99,255,0.15);color:var(--primary)">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="feature-title">ملف شخصي ذكي</div>
        <div class="feature-desc">احسب BMI وتتبع الوزن بمرور الوقت مع صور المقارنة.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background:rgba(255,101,132,0.15);color:var(--secondary)">
          <i class="bi bi-moon-fill"></i>
        </div>
        <div class="feature-title">وضع ليلي ونهاري</div>
        <div class="feature-desc">اختر المظهر الذي يريحك — وضع داكن أنيق أو وضع فاتح مريح للعين.</div>
      </div>
    </div>
  </div>
</section>

<!-- How It Works -->
<section id="how" style="background:rgba(26,26,46,0.5)">
  <div class="section-wrap" style="text-align:center">
    <div class="section-label" style="margin:0 auto 1rem"><i class="bi bi-lightning-fill"></i> كيف يعمل</div>
    <h2 class="section-title">ثلاث خطوات فقط</h2>
    <p class="section-subtitle" style="margin:0 auto 3rem">أبسط طريقة لتتبع ما تأكله</p>

    <div class="steps-wrap" id="steps-wrap">
      <div class="step-card">
        <div class="step-num">1</div>
        <div class="step-title">سجّل حساباً مجانياً</div>
        <div class="step-desc">أنشئ حسابك في ثوانٍ، حدد هدفك اليومي من السعرات.</div>
      </div>
      <div class="step-card">
        <div class="step-num">2</div>
        <div class="step-title">صوّر طعامك أو اسأل</div>
        <div class="step-desc">افتح الكاميرا، التقط صورة وجبتك — أو اكتب اسمها والذكاء الاصطناعي يتولى الباقي.</div>
      </div>
      <div class="step-card">
        <div class="step-num">3</div>
        <div class="step-title">تتبع وتحسّن</div>
        <div class="step-desc">راقب سعراتك اليومية، وتقدمك الأسبوعي، واصل نحو هدفك.</div>
      </div>
    </div>
  </div>
</section>

<!-- AI Providers -->
<section id="providers">
  <div class="section-wrap" style="text-align:center">
    <div class="section-label" style="margin:0 auto 1rem"><i class="bi bi-robot"></i> الذكاء الاصطناعي</div>
    <h2 class="section-title">5 نماذج ذكاء اصطناعي</h2>
    <p class="section-subtitle" style="margin:0 auto 3rem">
      اختر النموذج المناسب لك — ثلاثة منها مجانية تماماً
    </p>

    <div class="providers-grid">
      <div class="provider-pill">
        <span class="icon" style="color:#4285F4"><i class="bi bi-google"></i></span>
        Google Gemini
        <span class="free-badge">مجاني</span>
      </div>
      <div class="provider-pill">
        <span class="icon" style="color:var(--primary)"><i class="bi bi-box-fill"></i></span>
        OpenRouter
        <span class="free-badge">مجاني</span>
      </div>
      <div class="provider-pill">
        <span class="icon" style="color:var(--warning)"><i class="bi bi-lightning-fill"></i></span>
        Groq Llama 4
        <span class="free-badge">مجاني</span>
      </div>
      <div class="provider-pill">
        <span class="icon" style="color:var(--success)"><i class="bi bi-cpu-fill"></i></span>
        OpenAI GPT-4o
      </div>
      <div class="provider-pill">
        <span class="icon" style="color:var(--secondary)"><i class="bi bi-braces-asterisk"></i></span>
        Anthropic Claude
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-card" id="cta-card">
    <div style="font-size:3rem;margin-bottom:1rem"><i class="bi bi-fire"></i></div>
    <h2 class="cta-title">ابدأ رحلتك الصحية اليوم</h2>
    <p class="cta-subtitle">
      انضم إلى آلاف المستخدمين الذين يتتبعون سعراتهم بذكاء.
      مجاني، سريع، بدون تعقيد.
    </p>
    <a href="login.php" class="btn-hero-primary" style="display:inline-flex">
      <i class="bi bi-person-plus-fill"></i> إنشاء حساب مجاني
    </a>
  </div>
</section>

<footer>
  <div style="margin-bottom:8px">
    <i class="bi bi-fire" style="color:var(--primary)"></i>
    <strong style="color:var(--primary)">CalTrack</strong> — تتبع سعراتك بذكاء
  </div>
  <div>مدعوم بالذكاء الاصطناعي &nbsp;·&nbsp; مجاني تماماً &nbsp;·&nbsp; <a href="login.php" style="color:var(--primary)">ابدأ الآن</a></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/framer-motion@11/dist/framer-motion.js"></script>
<script>
window.addEventListener('load', () => {
  if (!window.Motion) return;
  const { animate, stagger, scroll, inView } = window.Motion;

  // Hero entrance
  animate('#hero-content', { opacity:[0,1], y:[40,0] }, { duration:0.9, easing:[0.4,0,0.2,1] });
  animate('.hero-badge',   { opacity:[0,1], scale:[0.9,1] }, { duration:0.6, delay:0.1 });
  animate('.hero-title',   { opacity:[0,1], y:[30,0] },       { duration:0.7, delay:0.2 });
  animate('.hero-subtitle',{ opacity:[0,1], y:[20,0] },       { duration:0.6, delay:0.35 });
  animate('.hero-actions', { opacity:[0,1], y:[20,0] },       { duration:0.6, delay:0.5 });

  // Phone 3D float
  animate('#hero-phone',
    { y:[0,-14,0], rotateZ:[-1,1,-1] },
    { duration:4, repeat:Infinity, easing:'ease-in-out' }
  );
  animate('#phone-wrap', { opacity:[0,1], y:[30,0], scale:[0.95,1] }, { duration:0.8, delay:0.6 });

  // Stats row
  inView('#stats-row', () => {
    animate('#stats-row .stat-big',
      { opacity:[0,1], y:[20,0] },
      { duration:0.5, delay:stagger(0.1) }
    );
  });

  // Features grid
  inView('#features-grid', () => {
    animate('#features-grid .feature-card',
      { opacity:[0,1], y:[30,0], scale:[0.96,1] },
      { duration:0.5, delay:stagger(0.08), easing:[0.4,0,0.2,1] }
    );
  });

  // Steps
  inView('#steps-wrap', () => {
    animate('#steps-wrap .step-card',
      { opacity:[0,1], x:[0,0], y:[30,0] },
      { duration:0.5, delay:stagger(0.12) }
    );
    animate('#steps-wrap .step-num',
      { scale:[0,1], rotate:[-15,0] },
      { duration:0.5, delay:stagger(0.12, { start:0.1 }), easing:[0.34,1.56,0.64,1] }
    );
  });

  // CTA card
  inView('#cta-card', () => {
    animate('#cta-card', { opacity:[0,1], y:[40,0], scale:[0.96,1] }, { duration:0.7, easing:[0.4,0,0.2,1] });
  });

  // Navbar scroll effect
  document.addEventListener('scroll', () => {
    const nav = document.getElementById('lp-nav');
    nav.style.background = window.scrollY > 60
      ? 'rgba(15,15,26,0.95)'
      : 'rgba(15,15,26,0.8)';
  });
});
</script>
</body>
</html>
