<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();
requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_settings') {
    $stmt = $pdo->query("SELECT `key`, value FROM settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        if ($row['key'] === 'gemini_api_key' && strlen($row['value']) > 4) {
            $settings[$row['key']] = substr($row['value'], 0, 4) . str_repeat('*', strlen($row['value']) - 4);
        } else {
            $settings[$row['key']] = $row['value'];
        }
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

if ($action === 'save_settings') {
    $allowed = ['ai_provider', 'gemini_api_key', 'gemini_model', 'app_name', 'default_daily_goal'];
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");

    foreach ($allowed as $key) {
        if (!isset($_POST[$key])) continue;
        $val = trim($_POST[$key]);
        if ($key === 'gemini_api_key' && str_contains($val, '***')) continue;
        $stmt->execute([$key, $val, $val]);
    }

    echo json_encode(['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح']);
    exit;
}

if ($action === 'get_users') {
    $stmt = $pdo->query(
        "SELECT u.id, u.name, u.email, u.role, u.daily_goal, u.created_at,
                COUNT(f.id) as total_logs,
                COALESCE(SUM(CASE WHEN f.log_date = CURDATE() THEN f.calories ELSE 0 END), 0) as today_calories
         FROM users u LEFT JOIN food_logs f ON u.id = f.user_id
         GROUP BY u.id ORDER BY u.created_at DESC"
    );
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'delete_user') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'لا يمكنك حذف حسابك الخاص']);
        exit;
    }
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_user_role') {
    $id   = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'stats') {
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM food_logs WHERE log_date = CURDATE()");
    $stats['today_logs'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(calories) FROM food_logs WHERE log_date = CURDATE()");
    $stats['today_calories'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM food_logs WHERE log_date = CURDATE()");
    $stats['active_today'] = $stmt->fetchColumn();

    $stmt = $pdo->query(
        "SELECT log_date, SUM(calories) as total
         FROM food_logs WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY log_date ORDER BY log_date"
    );
    $stats['week_chart'] = $stmt->fetchAll();

    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
