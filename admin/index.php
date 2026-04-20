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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
.provider-fields { display:none; }
.provider-fields.active { display:block; }
</style>
</head>
<body>
<nav class="navbar">
  <div class="navbar-brand"><i class="bi bi-fire"></i><span>Admin Panel</span></div>
  <ul class="navbar-nav">
    <li><a href="../index.php"><i class="bi bi-house-fill"></i> العودة للموقع</a></li>
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
      <li><div class="admin-nav-link active" onclick="showSection('dashboard')"><i class="bi bi-bar-chart-fill"></i> لوحة التحكم</div></li>
      <li><div class="admin-nav-link" onclick="showSection('settings')"><i class="bi bi-gear-fill"></i> إعدادات الذكاء الاصطناعي</div></li>
      <li><div class="admin-nav-link" onclick="showSection('multi-api')"><i class="bi bi-diagram-3-fill"></i> تعدد النماذج والاحتياطي</div></li>
      <li><div class="admin-nav-link" onclick="showSection('users')"><i class="bi bi-people-fill"></i> إدارة المستخدمين</div></li>
      <li><div class="admin-nav-link" onclick="showSection('app-settings')"><i class="bi bi-tools"></i> إعدادات التطبيق</div></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="admin-content" id="main-content" style="opacity:0">

    <!-- Dashboard Section -->
    <section class="section active" id="section-dashboard">
      <div class="page-header">
        <div class="page-title"><i class="bi bi-bar-chart-fill"></i> لوحة التحكم</div>
        <div class="page-subtitle">نظرة عامة على الموقع</div>
      </div>

      <div class="stats-grid" id="admin-stats">
        <div class="stat-card" style="--accent:var(--primary)">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <div class="stat-value" id="st-users">-</div>
          <div class="stat-label">إجمالي المستخدمين</div>
        </div>
        <div class="stat-card" style="--accent:var(--success)">
          <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-value" id="st-active">-</div>
          <div class="stat-label">نشطون اليوم</div>
        </div>
        <div class="stat-card" style="--accent:var(--warning)">
          <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
          <div class="stat-value" id="st-logs">-</div>
          <div class="stat-label">تسجيلات اليوم</div>
        </div>
        <div class="stat-card" style="--accent:var(--secondary)">
          <div class="stat-icon"><i class="bi bi-fire"></i></div>
          <div class="stat-value" id="st-cal">-</div>
          <div class="stat-label">كالوري مسجلة اليوم</div>
        </div>
      </div>

      <div class="card" style="margin-top:1.5rem">
        <div class="card-title"><i class="bi bi-graph-up-arrow"></i> السعرات - آخر 7 أيام (كل المستخدمين)</div>
        <div class="chart-bar-wrap" id="admin-chart" style="height:140px;gap:8px;align-items:flex-end"></div>
      </div>
    </section>

    <!-- AI Settings Section -->
    <section class="section" id="section-settings">
      <div class="page-header">
        <div class="page-title"><i class="bi bi-gear-fill"></i> إعدادات الذكاء الاصطناعي</div>
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
              <select name="ai_provider" id="ai-provider" class="form-control" style="width:260px" onchange="toggleProviderFields(this.value)">
                <optgroup label="مجاني تماماً">
                  <option value="gemini">Google Gemini (مجاني)</option>
                  <option value="openrouter">OpenRouter (نماذج مجانية)</option>
                  <option value="groq">Groq — Llama Vision (مجاني)</option>
                </optgroup>
                <optgroup label="مدفوع">
                  <option value="openai">OpenAI GPT-4 Vision</option>
                  <option value="anthropic">Anthropic Claude</option>
                </optgroup>
              </select>
            </div>
          </div>

          <!-- Gemini Fields -->
          <div id="gemini-fields" class="provider-fields">
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
                <select name="gemini_model" id="gemini-model" class="form-control" style="width:260px">
                  <option value="gemini-1.5-flash">gemini-1.5-flash — سريع ومجاني</option>
                  <option value="gemini-1.5-pro">gemini-1.5-pro — أدق ومجاني</option>
                  <option value="gemini-2.0-flash">gemini-2.0-flash — أحدث وأسرع</option>
                  <option value="gemini-2.0-flash-lite">gemini-2.0-flash-lite — اقتصادي</option>
                  <option value="gemini-2.5-flash-preview-04-17">gemini-2.5-flash-preview — أحدث تجريبي</option>
                </select>
              </div>
            </div>
          </div>

          <!-- OpenAI Fields -->
          <div id="openai-fields" class="provider-fields">
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">مفتاح OpenAI API</div>
                <div class="setting-desc">احصل على مفتاح من <a href="https://platform.openai.com/api-keys" target="_blank" style="color:var(--primary)">OpenAI Platform</a></div>
              </div>
              <div class="setting-control">
                <input type="text" name="openai_api_key" id="openai-api-key" class="form-control"
                  style="width:280px" placeholder="sk-...">
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">نموذج GPT</div>
                <div class="setting-desc">GPT-4o الأفضل للصور، GPT-4o-mini أسرع وأرخص</div>
              </div>
              <div class="setting-control">
                <select name="openai_model" id="openai-model" class="form-control" style="width:260px">
                  <option value="gpt-4o">gpt-4o — الأفضل دقةً</option>
                  <option value="gpt-4o-mini">gpt-4o-mini — أسرع وأرخص</option>
                  <option value="gpt-4-turbo">gpt-4-turbo — قوي وبطيء</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Anthropic Fields -->
          <div id="anthropic-fields" class="provider-fields">
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">مفتاح Anthropic API</div>
                <div class="setting-desc">احصل على مفتاح من <a href="https://console.anthropic.com/settings/keys" target="_blank" style="color:var(--primary)">Anthropic Console</a></div>
              </div>
              <div class="setting-control">
                <input type="text" name="anthropic_api_key" id="anthropic-api-key" class="form-control"
                  style="width:280px" placeholder="sk-ant-...">
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">نموذج Claude</div>
                <div class="setting-desc">Sonnet توازن ممتاز بين الدقة والسرعة</div>
              </div>
              <div class="setting-control">
                <select name="anthropic_model" id="anthropic-model" class="form-control" style="width:260px">
                  <option value="claude-sonnet-4-6">claude-sonnet-4-6 — الأحدث والأفضل</option>
                  <option value="claude-opus-4-7">claude-opus-4-7 — الأقوى</option>
                  <option value="claude-haiku-4-5-20251001">claude-haiku-4-5 — الأسرع</option>
                </select>
              </div>
            </div>
          </div>

          <!-- OpenRouter Fields -->
          <div id="openrouter-fields" class="provider-fields">
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">مفتاح OpenRouter API</div>
                <div class="setting-desc">احصل على مفتاح مجاني من <a href="https://openrouter.ai/keys" target="_blank" style="color:var(--primary)">OpenRouter</a> — يدعم نماذج مجانية بالكامل</div>
              </div>
              <div class="setting-control">
                <input type="text" name="openrouter_api_key" id="openrouter-api-key" class="form-control"
                  style="width:280px" placeholder="sk-or-...">
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">نموذج OpenRouter (مجاني)</div>
                <div class="setting-desc">جميع النماذج أدناه مجانية تماماً وتدعم تحليل الصور</div>
              </div>
              <div class="setting-control">
                <select name="openrouter_model" id="openrouter-model" class="form-control" style="width:320px">
                  <option value="google/gemini-2.0-flash-exp:free">google/gemini-2.0-flash-exp:free — Gemini مجاني</option>
                  <option value="meta-llama/llama-4-scout:free">meta-llama/llama-4-scout:free — Llama 4 Scout مجاني</option>
                  <option value="qwen/qwen2.5-vl-72b-instruct:free">qwen/qwen2.5-vl-72b-instruct:free — Qwen Vision مجاني</option>
                  <option value="meta-llama/llama-4-maverick:free">meta-llama/llama-4-maverick:free — Llama 4 Maverick مجاني</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Groq Fields -->
          <div id="groq-fields" class="provider-fields">
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">مفتاح Groq API</div>
                <div class="setting-desc">احصل على مفتاح مجاني من <a href="https://console.groq.com/keys" target="_blank" style="color:var(--primary)">Groq Console</a> — سريع جداً ومجاني</div>
              </div>
              <div class="setting-control">
                <input type="text" name="groq_api_key" id="groq-api-key" class="form-control"
                  style="width:280px" placeholder="gsk_...">
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-info">
                <div class="setting-label">نموذج Groq</div>
                <div class="setting-desc">Llama 4 Scout هو النموذج الأسرع مع دعم رؤية الصور</div>
              </div>
              <div class="setting-control">
                <select name="groq_model" id="groq-model" class="form-control" style="width:320px">
                  <option value="meta-llama/llama-4-scout-17b-16e-instruct">llama-4-scout-17b — الأسرع مع الصور</option>
                  <option value="meta-llama/llama-4-maverick-17b-128e-instruct">llama-4-maverick-17b — أقوى وأبطأ قليلاً</option>
                </select>
              </div>
            </div>
          </div>

          <div style="margin-top:1.5rem;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> حفظ الإعدادات</button>
            <button type="button" class="btn btn-secondary" onclick="testConnection()" id="test-btn"><i class="bi bi-plug-fill"></i> اختبار الاتصال</button>
          </div>
        </form>

        <div id="test-result" style="margin-top:1rem"></div>
      </div>

      <div class="card" style="margin-top:1rem">
        <div class="card-title"><i class="bi bi-book-fill"></i> دليل الإعداد</div>
        <div id="setup-guide-gemini">
          <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
            <li>اذهب إلى <strong style="color:var(--primary)">Google AI Studio</strong> → <code>aistudio.google.com</code></li>
            <li>سجل الدخول بحسابك Google</li>
            <li>اضغط على "Get API Key" وأنشئ مفتاح جديد</li>
            <li>انسخ المفتاح والصقه في الحقل أعلاه</li>
            <li>اضغط "حفظ الإعدادات" ثم "اختبار الاتصال"</li>
            <li>استخدم <strong>gemini-1.5-flash</strong> للاستخدام المجاني (حتى 15 طلب/دقيقة)</li>
          </ol>
        </div>
        <div id="setup-guide-openai" style="display:none">
          <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
            <li>اذهب إلى <strong style="color:var(--primary)">OpenAI Platform</strong> → <code>platform.openai.com</code></li>
            <li>سجل الدخول وافتح قسم "API Keys"</li>
            <li>أنشئ مفتاح جديد وانسخه في الحقل أعلاه</li>
            <li>تأكد من وجود رصيد في حسابك لاستخدام GPT-4o</li>
            <li>استخدم <strong>gpt-4o-mini</strong> لتكلفة أقل مع جودة جيدة</li>
          </ol>
        </div>
        <div id="setup-guide-anthropic" style="display:none">
          <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
            <li>اذهب إلى <strong style="color:var(--primary)">Anthropic Console</strong> → <code>console.anthropic.com</code></li>
            <li>سجل الدخول وافتح قسم "API Keys"</li>
            <li>أنشئ مفتاح جديد يبدأ بـ <code>sk-ant-</code></li>
            <li>انسخه في الحقل أعلاه</li>
            <li>استخدم <strong>claude-sonnet-4-6</strong> للتوازن المثالي بين الدقة والتكلفة</li>
          </ol>
        </div>
        <div id="setup-guide-openrouter" style="display:none">
          <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
            <li>اذهب إلى <strong style="color:var(--primary)">openrouter.ai</strong> وسجل حساباً مجانياً</li>
            <li>افتح قسم "Keys" وأنشئ مفتاح API جديد يبدأ بـ <code>sk-or-</code></li>
            <li>انسخه في الحقل أعلاه</li>
            <li>اختر أي نموذج ينتهي بـ <strong>:free</strong> — جميعها مجانية تماماً</li>
            <li>النموذج المُوصى: <strong>google/gemini-2.0-flash-exp:free</strong> للدقة العالية</li>
            <li>لا يوجد حد يومي صارم — مناسب للاستخدام المكثف</li>
          </ol>
        </div>
        <div id="setup-guide-groq" style="display:none">
          <ol style="padding-right:1.2rem;display:flex;flex-direction:column;gap:8px;color:var(--text-muted);font-size:0.9rem;line-height:1.7">
            <li>اذهب إلى <strong style="color:var(--primary)">console.groq.com</strong> وسجل حساباً مجانياً</li>
            <li>افتح قسم "API Keys" وأنشئ مفتاح جديد يبدأ بـ <code>gsk_</code></li>
            <li>انسخه في الحقل أعلاه</li>
            <li>استخدم <strong>llama-4-scout-17b</strong> — أسرع نموذج مع دعم تحليل الصور</li>
            <li>الحد المجاني: 14,400 طلب/يوم — أكثر من كافٍ للاستخدام العادي</li>
          </ol>
        </div>
      </div>
    </section>

    <!-- Multi-API Section -->
    <section class="section" id="section-multi-api">
      <div class="page-header">
        <div class="page-title"><i class="bi bi-diagram-3-fill"></i> تعدد النماذج والاحتياطي</div>
        <div class="page-subtitle">اضبط وضع التحقق المتعدد وترتيب المزودين الاحتياطيين</div>
      </div>

      <div class="card" style="margin-bottom:1rem">
        <div id="multi-api-alert"></div>
        <form id="multi-api-form">

          <!-- Consensus Mode -->
          <div style="margin-bottom:1.5rem">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
              <div>
                <div style="font-weight:700;font-size:1rem"><i class="bi bi-diagram-3-fill" style="color:var(--primary)"></i> وضع التحقق المتعدد (Consensus)</div>
                <div style="font-size:0.85rem;color:var(--text-muted);margin-top:4px">
                  يرسل الصورة لعدة نماذج في نفس الوقت ويحسب متوسط النتائج — دقة أعلى وثقة أكبر
                </div>
              </div>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" id="consensus-toggle" name="consensus_mode" value="1"
                  style="width:18px;height:18px;accent-color:var(--primary)" onchange="toggleConsensusUI(this.checked)">
                <span id="consensus-label" style="font-weight:700;color:var(--text-muted)">معطّل</span>
              </label>
            </div>

            <div id="consensus-providers-wrap" style="display:none;background:var(--bg-card2);border-radius:12px;padding:1rem;border:1px solid var(--border)">
              <div style="font-size:0.85rem;font-weight:700;margin-bottom:0.75rem;color:var(--text-muted)">اختر النماذج للتحقق المتعدد (2 على الأقل):</div>
              <div style="display:flex;flex-wrap:wrap;gap:0.75rem" id="consensus-checkboxes">
                <?php foreach([
                  ['gemini',     'Google Gemini',    'google',          '#4285F4'],
                  ['openrouter', 'OpenRouter',        'box-fill',        'var(--primary)'],
                  ['groq',       'Groq Llama',        'lightning-fill',  'var(--warning)'],
                  ['openai',     'OpenAI GPT-4o',     'cpu-fill',        'var(--success)'],
                  ['anthropic',  'Anthropic Claude',  'braces-asterisk', 'var(--secondary)'],
                ] as [$val,$label,$icon,$color]): ?>
                <label style="display:flex;align-items:center;gap:8px;background:var(--bg-card);border:1.5px solid var(--border);border-radius:10px;padding:8px 14px;cursor:pointer;transition:0.2s" class="cp-label" data-val="<?=$val?>">
                  <input type="checkbox" name="cp_<?=$val?>" value="<?=$val?>"
                    style="width:16px;height:16px;accent-color:var(--primary)">
                  <i class="bi bi-<?=$icon?>" style="color:<?=$color?>"></i>
                  <span style="font-size:0.88rem;font-weight:600"><?=$label?></span>
                </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" id="consensus-providers-input" name="consensus_providers" value="[]">
              <div style="margin-top:0.75rem;font-size:0.82rem;color:var(--text-muted)">
                <i class="bi bi-info-circle-fill"></i>
                يعمل الوضع فقط مع المزودين الذين تم إعداد مفاتيح API الخاصة بهم.
              </div>
            </div>
          </div>

          <hr style="border-color:var(--border);margin:1.5rem 0">

          <!-- Fallback Chain -->
          <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
              <div>
                <div style="font-weight:700;font-size:1rem"><i class="bi bi-arrow-repeat" style="color:var(--success)"></i> الاحتياطي التلقائي (Fallback)</div>
                <div style="font-size:0.85rem;color:var(--text-muted);margin-top:4px">
                  إذا فشل المزود الرئيسي أو تجاوز الحد، يتم التحويل تلقائياً للمزود التالي
                </div>
              </div>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" id="fallback-toggle" name="fallback_enabled" value="1"
                  style="width:18px;height:18px;accent-color:var(--success)" onchange="toggleFallbackUI(this.checked)">
                <span id="fallback-label" style="font-weight:700;color:var(--text-muted)">معطّل</span>
              </label>
            </div>

            <div id="fallback-order-wrap" style="display:none;background:var(--bg-card2);border-radius:12px;padding:1rem;border:1px solid var(--border)">
              <div style="font-size:0.85rem;font-weight:700;margin-bottom:0.75rem;color:var(--text-muted)">ترتيب المزودين الاحتياطيين (يُجرَّب من الأول):</div>
              <div id="fallback-list" style="display:flex;flex-direction:column;gap:6px">
                <?php foreach([
                  ['gemini',     'Google Gemini',    'google',          '#4285F4'],
                  ['openrouter', 'OpenRouter',        'box-fill',        'var(--primary)'],
                  ['groq',       'Groq Llama',        'lightning-fill',  'var(--warning)'],
                  ['openai',     'OpenAI GPT-4o',     'cpu-fill',        'var(--success)'],
                  ['anthropic',  'Anthropic Claude',  'braces-asterisk', 'var(--secondary)'],
                ] as $idx => [$val,$label,$icon,$color]): ?>
                <label class="fb-item" data-val="<?=$val?>" style="display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1.5px solid var(--border);border-radius:10px;padding:9px 14px;cursor:pointer;transition:0.2s">
                  <span style="font-size:0.78rem;color:var(--text-muted);width:20px;text-align:center;font-weight:700"><?=$idx+1?></span>
                  <input type="checkbox" name="fb_<?=$val?>" value="<?=$val?>"
                    style="width:16px;height:16px;accent-color:var(--success)">
                  <i class="bi bi-<?=$icon?>" style="color:<?=$color?>"></i>
                  <span style="font-size:0.88rem;font-weight:600;flex:1"><?=$label?></span>
                  <i class="bi bi-grip-vertical" style="color:var(--border)"></i>
                </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" id="fallback-providers-input" name="fallback_providers" value="[]">
              <div style="margin-top:0.75rem;font-size:0.82rem;color:var(--text-muted)">
                <i class="bi bi-info-circle-fill"></i>
                المزود الرئيسي (المحدد في إعدادات الذكاء الاصطناعي) يُجرَّب دائماً أولاً.
              </div>
            </div>
          </div>

          <div style="margin-top:1.5rem">
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> حفظ الإعدادات</button>
          </div>
        </form>
      </div>

      <!-- Info card -->
      <div class="card">
        <div class="card-title"><i class="bi bi-lightbulb-fill"></i> كيف يعمل؟</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-size:0.88rem;color:var(--text-muted);line-height:1.7">
          <div>
            <div style="font-weight:700;color:var(--primary);margin-bottom:6px"><i class="bi bi-diagram-3-fill"></i> وضع Consensus</div>
            <ul style="padding-right:1.2rem;display:flex;flex-direction:column;gap:4px">
              <li>يرسل الصورة لكل النماذج المختارة في نفس الوقت (parallel)</li>
              <li>يحسب متوسط السعرات والبروتين والكارب والدهون</li>
              <li>إذا اتفقت النماذج (&lt;15% فرق) → ثقة عالية</li>
              <li>إذا اختلفت → يُظهر تحذير وثقة متوسطة أو منخفضة</li>
              <li>الأبطأ قليلاً لكن الأدق بكثير</li>
            </ul>
          </div>
          <div>
            <div style="font-weight:700;color:var(--success);margin-bottom:6px"><i class="bi bi-arrow-repeat"></i> وضع Fallback</div>
            <ul style="padding-right:1.2rem;display:flex;flex-direction:column;gap:4px">
              <li>يجرّب المزود الرئيسي أولاً</li>
              <li>إذا فشل (خطأ 429، rate limit، مشكلة شبكة)...</li>
              <li>ينتقل تلقائياً للمزود الاحتياطي التالي</li>
              <li>يكمل حتى ينجح أحد المزودين</li>
              <li>موصى به دائماً حتى مع وضع Consensus</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Users Section -->
    <section class="section" id="section-users">
      <div class="page-header">
        <div class="page-title"><i class="bi bi-people-fill"></i> إدارة المستخدمين</div>
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
        <div class="page-title"><i class="bi bi-tools"></i> إعدادات التطبيق</div>
        <div class="page-subtitle">إعدادات عامة للتطبيق</div>
      </div>

      <div class="card">
        <div id="app-settings-alert"></div>
        <form id="app-settings-form">

          <!-- Branding -->
          <div style="font-weight:700;font-size:0.95rem;color:var(--primary);margin-bottom:1rem;padding-bottom:6px;border-bottom:1px solid var(--border)">
            <i class="bi bi-palette-fill"></i> هوية التطبيق (Branding)
          </div>

          <div class="form-group">
            <label class="form-label">اسم التطبيق</label>
            <input type="text" name="app_name" id="app-name" class="form-control" placeholder="CalTrack">
          </div>

          <div class="form-row" style="align-items:flex-end">
            <div class="form-group">
              <label class="form-label">أيقونة الشعار (Bootstrap Icons)</label>
              <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="app_logo_icon" id="app-logo-icon" class="form-control"
                  placeholder="fire" oninput="previewLogo()" style="flex:1">
                <div id="logo-preview" style="width:44px;height:44px;border-radius:10px;background:rgba(108,99,255,0.15);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">
                  <i class="bi bi-fire" id="logo-preview-icon"></i>
                </div>
              </div>
              <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px">
                أمثلة: fire · heart-pulse-fill · activity · lightning-fill · apple · egg-fried · star-fill
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">لون الأيقونة (اختياري)</label>
              <div style="display:flex;gap:8px;align-items:center">
                <input type="color" name="app_logo_color" id="app-logo-color" value="#FF6584"
                  style="width:44px;height:44px;border:none;background:none;cursor:pointer;padding:0;border-radius:8px"
                  oninput="previewLogo()">
                <input type="text" id="app-logo-color-hex" class="form-control" placeholder="تلقائي"
                  oninput="document.getElementById('app-logo-color').value=this.value" style="flex:1">
              </div>
            </div>
          </div>

          <!-- Icon quick-pick -->
          <div style="margin-bottom:1.5rem">
            <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:6px">اختيار سريع:</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php foreach(['fire','heart-pulse-fill','activity','lightning-fill','apple','egg-fried','trophy-fill','star-fill','moon-fill','sun-fill','bicycle','person-running','droplet-fill','leaf-fill','basket-fill'] as $ic): ?>
              <button type="button" onclick="pickIcon('<?=$ic?>')"
                style="width:36px;height:36px;border-radius:8px;border:1.5px solid var(--border);background:var(--bg-card2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:0.2s"
                title="<?=$ic?>" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <i class="bi bi-<?=$ic?>"></i>
              </button>
              <?php endforeach; ?>
            </div>
          </div>

          <hr style="border-color:var(--border);margin:1.5rem 0">

          <!-- Other settings -->
          <div style="font-weight:700;font-size:0.95rem;color:var(--primary);margin-bottom:1rem;padding-bottom:6px;border-bottom:1px solid var(--border)">
            <i class="bi bi-sliders"></i> إعدادات عامة
          </div>

          <div class="form-group">
            <label class="form-label">الهدف اليومي الافتراضي (كالوري)</label>
            <input type="number" name="default_daily_goal" id="default-goal" class="form-control" placeholder="2000" min="500" max="10000">
          </div>

          <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill"></i> حفظ</button>
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

  if (name === 'users')       loadUsers();
  if (name === 'settings')    loadSettings();
  if (name === 'multi-api')   loadMultiApiSettings();
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

  if (s.ai_provider)        document.getElementById('ai-provider').value          = s.ai_provider;
  if (s.gemini_api_key)     document.getElementById('gemini-api-key').value        = s.gemini_api_key;
  if (s.gemini_model)       document.getElementById('gemini-model').value          = s.gemini_model;
  if (s.openai_api_key)     document.getElementById('openai-api-key').value        = s.openai_api_key;
  if (s.openai_model)       document.getElementById('openai-model').value          = s.openai_model;
  if (s.anthropic_api_key)  document.getElementById('anthropic-api-key').value     = s.anthropic_api_key;
  if (s.anthropic_model)    document.getElementById('anthropic-model').value       = s.anthropic_model;
  if (s.openrouter_api_key) document.getElementById('openrouter-api-key').value    = s.openrouter_api_key;
  if (s.openrouter_model)   document.getElementById('openrouter-model').value      = s.openrouter_model;
  if (s.groq_api_key)       document.getElementById('groq-api-key').value          = s.groq_api_key;
  if (s.groq_model)         document.getElementById('groq-model').value            = s.groq_model;
  if (s.app_name)           document.getElementById('app-name').value              = s.app_name;
  if (s.default_daily_goal) document.getElementById('default-goal').value          = s.default_daily_goal;
  if (s.app_logo_icon) {
    document.getElementById('app-logo-icon').value = s.app_logo_icon;
    previewLogo();
  }
  if (s.app_logo_color) {
    document.getElementById('app-logo-color').value     = s.app_logo_color;
    document.getElementById('app-logo-color-hex').value = s.app_logo_color;
  }

  toggleProviderFields(document.getElementById('ai-provider').value);
}

async function loadMultiApiSettings() {
  const res  = await fetch('../api/admin.php?action=get_settings');
  const data = await res.json();
  if (!data.success) return;
  const s = data.settings;

  const consensusOn  = s.consensus_mode === '1';
  const fallbackOn   = s.fallback_enabled === '1';
  const cpProviders  = JSON.parse(s.consensus_providers  || '[]');
  const fbProviders  = JSON.parse(s.fallback_providers   || '[]');

  document.getElementById('consensus-toggle').checked = consensusOn;
  document.getElementById('fallback-toggle').checked  = fallbackOn;
  toggleConsensusUI(consensusOn);
  toggleFallbackUI(fallbackOn);

  // restore consensus checkboxes
  document.querySelectorAll('#consensus-checkboxes input[type=checkbox]').forEach(cb => {
    cb.checked = cpProviders.includes(cb.value);
    cb.closest('label').style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
  });

  // restore fallback checkboxes
  document.querySelectorAll('#fallback-list input[type=checkbox]').forEach(cb => {
    cb.checked = fbProviders.includes(cb.value);
    cb.closest('label').style.borderColor = cb.checked ? 'var(--success)' : 'var(--border)';
  });
}

function toggleConsensusUI(on) {
  document.getElementById('consensus-label').textContent  = on ? 'مفعّل' : 'معطّل';
  document.getElementById('consensus-label').style.color  = on ? 'var(--primary)' : 'var(--text-muted)';
  document.getElementById('consensus-providers-wrap').style.display = on ? '' : 'none';
}

function toggleFallbackUI(on) {
  document.getElementById('fallback-label').textContent = on ? 'مفعّل' : 'معطّل';
  document.getElementById('fallback-label').style.color = on ? 'var(--success)' : 'var(--text-muted)';
  document.getElementById('fallback-order-wrap').style.display = on ? '' : 'none';
}

// Sync checkbox arrays to hidden inputs before submit
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#consensus-checkboxes input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', () => {
      cb.closest('label').style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
      syncConsensusProviders();
    });
  });
  document.querySelectorAll('#fallback-list input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', () => {
      cb.closest('label').style.borderColor = cb.checked ? 'var(--success)' : 'var(--border)';
      syncFallbackProviders();
    });
  });
});

function syncConsensusProviders() {
  const checked = [...document.querySelectorAll('#consensus-checkboxes input:checked')].map(c => c.value);
  document.getElementById('consensus-providers-input').value = JSON.stringify(checked);
}
function syncFallbackProviders() {
  const checked = [...document.querySelectorAll('#fallback-list input:checked')].map(c => c.value);
  document.getElementById('fallback-providers-input').value = JSON.stringify(checked);
}

document.getElementById('multi-api-form')?.addEventListener('submit', async e => {
  e.preventDefault();
  syncConsensusProviders();
  syncFallbackProviders();
  const fd = new FormData(e.target);
  if (!document.getElementById('consensus-toggle').checked) fd.set('consensus_mode', '0');
  if (!document.getElementById('fallback-toggle').checked)  fd.set('fallback_enabled', '0');
  fd.append('action', 'save_settings');
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('multi-api-alert').innerHTML =
    `<div class="alert alert-${data.success?'success':'error'}"><i class="bi bi-${data.success?'check-circle-fill':'x-circle-fill'}"></i> ${data.message}</div>`;
});

// Logo branding helpers
function previewLogo() {
  const icon  = document.getElementById('app-logo-icon').value.trim() || 'fire';
  const color = document.getElementById('app-logo-color').value || '';
  const el    = document.getElementById('logo-preview-icon');
  el.className = 'bi bi-' + icon;
  el.style.color = color;
}
function pickIcon(name) {
  document.getElementById('app-logo-icon').value = name;
  previewLogo();
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
          ${u.role === 'admin' ? '<i class="bi bi-gear-fill"></i> مشرف' : '<i class="bi bi-person-fill"></i> مستخدم'}
        </span>
      </td>
      <td>${u.daily_goal}</td>
      <td>${u.total_logs}</td>
      <td>${parseInt(u.today_calories).toLocaleString()}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-sm btn-secondary" onclick="toggleRole(${u.id}, '${u.role}')">
            ${u.role === 'admin'
              ? '<i class="bi bi-arrow-down-circle-fill"></i> رجوع مستخدم'
              : '<i class="bi bi-arrow-up-circle-fill"></i> ترقية مشرف'}
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.id}, '${escHtml(u.name)}')"><i class="bi bi-trash3-fill"></i></button>
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
    document.getElementById('users-alert').innerHTML = `<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> ${data.message}</div>`;
  }
}

document.getElementById('ai-settings-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','save_settings');
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('settings-alert').innerHTML =
    `<div class="alert alert-${data.success ? 'success' : 'error'}"><i class="bi bi-${data.success ? 'check-circle-fill' : 'x-circle-fill'}"></i> ${data.message}</div>`;
});

document.getElementById('app-settings-form').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','save_settings');
  // sync color hex field
  const colorHex = document.getElementById('app-logo-color-hex').value.trim();
  if (colorHex) fd.set('app_logo_color', colorHex);
  const res  = await fetch('../api/admin.php', {method:'POST', body:fd});
  const data = await res.json();
  document.getElementById('app-settings-alert').innerHTML =
    `<div class="alert alert-${data.success ? 'success' : 'error'}"><i class="bi bi-${data.success ? 'check-circle-fill' : 'x-circle-fill'}"></i> ${data.message}</div>`;
});

async function testConnection() {
  const btn = document.getElementById('test-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-width:2px;margin:0"></div> جاري الاختبار...';
  document.getElementById('test-result').innerHTML = '';

  const fd = new FormData();
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = 'white'; ctx.fillRect(0,0,1,1);
  fd.append('image_base64', canvas.toDataURL('image/jpeg'));

  try {
    const res  = await fetch('../api/analyze.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success || data.message?.includes('التعرف')) {
      document.getElementById('test-result').innerHTML =
        '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> الاتصال يعمل بشكل صحيح!</div>';
    } else if (data.message?.includes('مفتاح API')) {
      document.getElementById('test-result').innerHTML =
        '<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> مفتاح API غير صحيح أو غير محفوظ</div>';
    } else {
      document.getElementById('test-result').innerHTML =
        `<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> الاتصال يعمل (${data.message})</div>`;
    }
  } catch(err) {
    document.getElementById('test-result').innerHTML =
      '<div class="alert alert-error"><i class="bi bi-x-circle-fill"></i> خطأ في الاتصال - تأكد من إعدادات الخادم</div>';
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-plug-fill"></i> اختبار الاتصال';
}

function toggleProviderFields(provider) {
  document.querySelectorAll('.provider-fields').forEach(el => el.classList.remove('active'));
  const el = document.getElementById(provider + '-fields');
  if (el) el.classList.add('active');

  const guides = ['gemini', 'openai', 'anthropic', 'openrouter', 'groq'];
  guides.forEach(p => {
    const g = document.getElementById('setup-guide-' + p);
    if (g) g.style.display = provider === p ? '' : 'none';
  });
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
<script src="../assets/js/theme.js"></script>
</body>
</html>
