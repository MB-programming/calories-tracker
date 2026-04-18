<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $goal  = (int)($_POST['daily_goal'] ?? 2000);

    if (!$name || !$email || !$pass) {
        echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل']);
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, daily_goal) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $goal]);
    $newId = $pdo->lastInsertId();

    // Auto-login after registration
    $_SESSION['user_id']        = $newId;
    $_SESSION['user_name']      = $name;
    $_SESSION['role']           = 'user';
    $_SESSION['daily_goal']     = $goal;

    echo json_encode(['success' => true, 'message' => 'تم إنشاء الحساب بنجاح', 'redirect' => 'setup_profile.php']);
    exit;
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'بيانات الدخول غير صحيحة']);
        exit;
    }

    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_name']      = $user['name'];
    $_SESSION['role']           = $user['role'];
    $_SESSION['daily_goal']     = $user['daily_goal'];

    $profileComplete = isset($user['profile_complete']) ? (int)$user['profile_complete'] : 1;
    $redirect = ($user['role'] === 'admin')
        ? 'admin/index.php'
        : ($profileComplete ? 'index.php' : 'setup_profile.php');

    echo json_encode(['success' => true, 'role' => $user['role'], 'redirect' => $redirect]);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
