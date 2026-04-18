<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Save initial profile ─────────────────────────────────────────────────────
if ($action === 'save_profile') {
    $age    = (int)($_POST['age'] ?? 0);
    $weight = (float)($_POST['weight'] ?? 0);
    $height = (float)($_POST['height'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $goal   = $_POST['fitness_goal'] ?? '';

    if ($age < 5 || $age > 120) {
        echo json_encode(['success' => false, 'message' => 'السن غير صحيح (5-120 سنة)']);
        exit;
    }
    if ($weight < 20 || $weight > 400) {
        echo json_encode(['success' => false, 'message' => 'الوزن غير صحيح (20-400 كجم)']);
        exit;
    }
    if ($height < 50 || $height > 300) {
        echo json_encode(['success' => false, 'message' => 'الطول غير صحيح (50-300 سم)']);
        exit;
    }
    if (!in_array($gender, ['male', 'female'])) {
        echo json_encode(['success' => false, 'message' => 'الجنس غير صحيح']);
        exit;
    }
    if (!in_array($goal, ['lose_weight', 'gain_weight', 'maintain'])) {
        echo json_encode(['success' => false, 'message' => 'الهدف غير صحيح']);
        exit;
    }

    // BMI
    $bmi = round($weight / (($height / 100) ** 2), 1);

    // Suggest daily calorie goal based on fitness goal
    $dailyGoal = match ($goal) {
        'lose_weight'  => 1500,
        'gain_weight'  => 2800,
        default        => 2000,
    };

    $stmt = $pdo->prepare(
        'UPDATE users SET age=?, weight=?, height=?, gender=?, fitness_goal=?, profile_complete=1, daily_goal=? WHERE id=?'
    );
    $stmt->execute([$age, $weight, $height, $gender, $goal, $dailyGoal, $userId]);

    // Log initial weight entry
    $pdo->prepare(
        'INSERT INTO weight_logs (user_id, weight, log_date) VALUES (?,?,CURDATE())
         ON DUPLICATE KEY UPDATE weight=?'
    )->execute([$userId, $weight, $weight]);

    $_SESSION['daily_goal'] = $dailyGoal;

    echo json_encode(['success' => true, 'bmi' => $bmi, 'daily_goal' => $dailyGoal]);
    exit;
}

// ── Get profile ──────────────────────────────────────────────────────────────
if ($action === 'get_profile') {
    $stmt = $pdo->prepare(
        'SELECT name, email, age, weight, height, gender, fitness_goal, daily_goal, profile_complete FROM users WHERE id=?'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    $user['bmi'] = ($user['height'] > 0)
        ? round($user['weight'] / (($user['height'] / 100) ** 2), 1)
        : null;

    // Latest weight from logs
    $stmt2 = $pdo->prepare('SELECT weight, log_date FROM weight_logs WHERE user_id=? ORDER BY log_date DESC LIMIT 1');
    $stmt2->execute([$userId]);
    $latest = $stmt2->fetch();
    $user['latest_weight']    = $latest ? (float)$latest['weight'] : (float)$user['weight'];
    $user['latest_weight_date'] = $latest ? $latest['log_date'] : null;

    echo json_encode(['success' => true, 'data' => $user]);
    exit;
}

// ── Log weight ───────────────────────────────────────────────────────────────
if ($action === 'log_weight') {
    $weight = (float)($_POST['weight'] ?? 0);
    $notes  = trim($_POST['notes'] ?? '');

    if ($weight < 20 || $weight > 400) {
        echo json_encode(['success' => false, 'message' => 'الوزن غير صحيح']);
        exit;
    }

    $pdo->prepare(
        'INSERT INTO weight_logs (user_id, weight, notes, log_date) VALUES (?,?,?,CURDATE())
         ON DUPLICATE KEY UPDATE weight=?, notes=?'
    )->execute([$userId, $weight, $notes, $weight, $notes]);

    $pdo->prepare('UPDATE users SET weight=? WHERE id=?')->execute([$weight, $userId]);

    // Recalculate BMI
    $stmt = $pdo->prepare('SELECT height FROM users WHERE id=?');
    $stmt->execute([$userId]);
    $height = (float)$stmt->fetchColumn();
    $bmi = ($height > 0) ? round($weight / (($height / 100) ** 2), 1) : null;

    echo json_encode(['success' => true, 'bmi' => $bmi]);
    exit;
}

// ── Weight history ───────────────────────────────────────────────────────────
if ($action === 'get_weight_history') {
    $stmt = $pdo->prepare(
        'SELECT weight, log_date FROM weight_logs WHERE user_id=? ORDER BY log_date ASC LIMIT 60'
    );
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Save body photo ──────────────────────────────────────────────────────────
if ($action === 'save_photo') {
    $type  = $_POST['photo_type'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!in_array($type, ['front', 'back'])) {
        echo json_encode(['success' => false, 'message' => 'نوع الصورة غير صحيح']);
        exit;
    }

    $photoPath = '';
    $uploadDir = '../uploads/body_photos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!empty($_FILES['photo']['tmp_name'])) {
        $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION) ?: 'jpg');
        $filename = 'body_' . $userId . '_' . $type . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
            $photoPath = 'uploads/body_photos/' . $filename;
        }
    } elseif (!empty($_POST['photo_base64'])) {
        $raw = $_POST['photo_base64'];
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $raw, $m)) {
            $filename = 'body_' . $userId . '_' . $type . '_' . time() . '.jpg';
            file_put_contents($uploadDir . $filename, base64_decode($m[2]));
            $photoPath = 'uploads/body_photos/' . $filename;
        }
    }

    if (!$photoPath) {
        echo json_encode(['success' => false, 'message' => 'فشل رفع الصورة']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT weight FROM users WHERE id=?');
    $stmt->execute([$userId]);
    $currentWeight = $stmt->fetchColumn();

    $pdo->prepare(
        'INSERT INTO body_photos (user_id, photo_type, photo_path, weight_at_time, notes, taken_at) VALUES (?,?,?,?,?,CURDATE())'
    )->execute([$userId, $type, $photoPath, $currentWeight, $notes]);

    echo json_encode(['success' => true, 'path' => $photoPath]);
    exit;
}

// ── Get body photos ──────────────────────────────────────────────────────────
if ($action === 'get_photos') {
    $stmt = $pdo->prepare(
        'SELECT * FROM body_photos WHERE user_id=? ORDER BY taken_at DESC, created_at DESC'
    );
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
