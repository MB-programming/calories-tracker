<?php
/**
 * CalTrack Setup Script - Run once to initialize the database
 * Delete this file after setup is complete!
 */

// Simple protection
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>CalTrack Setup</title>
    <style>body{font-family:Cairo,sans-serif;background:#0F0F1A;color:#E0E0FF;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#1A1A2E;border:1px solid #2a2a4a;border-radius:16px;padding:2rem;max-width:480px;text-align:center}
    h1{color:#6C63FF;margin-bottom:1rem}p{color:#8888aa;line-height:1.7}
    a{display:inline-block;margin-top:1.5rem;padding:12px 28px;background:linear-gradient(135deg,#6C63FF,#574fd6);color:#fff;text-decoration:none;border-radius:10px;font-weight:700}
    </style></head><body>
    <div class="box">
      <h1>🔥 CalTrack Setup</h1>
      <p>هذا الملف سيقوم بإنشاء قاعدة البيانات وجميع الجداول المطلوبة.<br>تأكد من إعداد بيانات الاتصال في <code>includes/db.php</code> أولاً.</p>
      <a href="?confirm=yes">✅ تأكيد وبدء الإعداد</a>
    </div></body></html>';
    exit;
}

$errors = [];
$steps  = [];

// Check db.php
if (!file_exists(__DIR__ . '/includes/db.php')) {
    die('<div style="color:red">خطأ: ملف includes/db.php غير موجود</div>');
}

// Connect without DB first to create it
$dbConf = ['host'=>'localhost','user'=>'root','pass'=>'','name'=>'calories_tracker'];

try {
    $pdo = new PDO("mysql:host={$dbConf['host']};charset=utf8mb4", $dbConf['user'], $dbConf['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConf['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbConf['name']}`");
    $steps[] = '✅ تم إنشاء قاعدة البيانات';

    $sql = file_get_contents(__DIR__ . '/database.sql');
    // Remove CREATE DATABASE and USE statements since we handled them
    $sql = preg_replace('/^(CREATE DATABASE|USE).*$/mi', '', $sql);
    // Split and run each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt) { $pdo->exec($stmt); }
    }
    $steps[] = '✅ تم إنشاء الجداول';
    $steps[] = '✅ تم إدراج البيانات الأولية';

    // Create uploads dir
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        file_put_contents($uploadsDir . '/.htaccess', "Options -Indexes\nOptions -ExecCGI\n");
    }
    $steps[] = '✅ تم إنشاء مجلد uploads';

    $steps[] = '🎉 <strong>تم الإعداد بنجاح!</strong>';

} catch (PDOException $e) {
    $errors[] = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
}

echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>CalTrack Setup</title>
<style>body{font-family:Cairo,sans-serif;background:#0F0F1A;color:#E0E0FF;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem}
.box{background:#1A1A2E;border:1px solid #2a2a4a;border-radius:16px;padding:2rem;max-width:560px;width:100%}
h1{color:#6C63FF;margin-bottom:1.5rem;text-align:center}
.step{padding:10px 14px;margin:6px 0;background:#16213E;border-radius:8px;font-size:0.95rem}
.err{background:rgba(255,87,87,0.1);color:#FF5757;border:1px solid rgba(255,87,87,0.3)}
.links{display:flex;gap:10px;margin-top:1.5rem;flex-wrap:wrap}
a{flex:1;padding:10px;background:linear-gradient(135deg,#6C63FF,#574fd6);color:#fff;text-decoration:none;border-radius:8px;text-align:center;font-weight:700;min-width:120px}
.warn{background:#1a1a2e;border:1px solid #FFB347;color:#FFB347;border-radius:8px;padding:12px;margin-top:1rem;font-size:0.85rem}
</style></head><body><div class="box">
<h1>🔥 CalTrack Setup</h1>';

foreach ($steps as $s) echo "<div class='step'>$s</div>";
foreach ($errors as $e) echo "<div class='step err'>❌ $e</div>";

if (empty($errors)) {
    echo '<div class="warn">⚠️ <strong>مهم:</strong> احذف ملف setup.php بعد الانتهاء من الإعداد لأسباب أمنية!</div>';
    echo '<div class="links">
      <a href="login.php">🚀 الدخول للموقع</a>
      <a href="admin/index.php">⚙️ لوحة الإدارة</a>
    </div>
    <div style="margin-top:1rem;font-size:0.85rem;color:#8888aa;background:#16213E;border-radius:8px;padding:12px">
      <strong>بيانات الدخول الافتراضية:</strong><br>
      البريد: admin@caltrack.com<br>
      كلمة المرور: password
    </div>';
}

echo '</div></body></html>';
