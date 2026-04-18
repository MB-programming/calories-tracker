<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'add') {
    $foodName  = trim($_POST['food_name'] ?? '');
    $calories  = (int)($_POST['calories'] ?? 0);
    $protein   = (float)($_POST['protein'] ?? 0);
    $carbs     = (float)($_POST['carbs'] ?? 0);
    $fat       = (float)($_POST['fat'] ?? 0);
    $mealType  = $_POST['meal_type'] ?? 'snack';
    $logDate   = $_POST['log_date'] ?? date('Y-m-d');
    $imagePath = null;

    if (!$foodName || $calories <= 0) {
        echo json_encode(['success' => false, 'message' => 'اسم الطعام والسعرات الحرارية مطلوبة']);
        exit;
    }

    if (!empty($_FILES['image']['tmp_name'])) {
        $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('food_', true) . '.' . $ext;
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
        $imagePath = 'uploads/' . $filename;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO food_logs (user_id, food_name, calories, protein, carbs, fat, meal_type, image_path, log_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $foodName, $calories, $protein, $carbs, $fat, $mealType, $imagePath, $logDate]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM food_logs WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'today') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT * FROM food_logs WHERE user_id = ? AND log_date = ? ORDER BY created_at DESC"
    );
    $stmt->execute([$userId, $date]);
    $logs = $stmt->fetchAll();

    $totals = array_reduce($logs, function($carry, $item) {
        $carry['calories'] += $item['calories'];
        $carry['protein']  += $item['protein'];
        $carry['carbs']    += $item['carbs'];
        $carry['fat']      += $item['fat'];
        return $carry;
    }, ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0]);

    echo json_encode(['success' => true, 'logs' => $logs, 'totals' => $totals]);
    exit;
}

if ($action === 'history') {
    $days  = (int)($_GET['days'] ?? 30);
    $days  = min($days, 365);
    $stmt  = $pdo->prepare(
        "SELECT log_date, SUM(calories) as total_calories, SUM(protein) as total_protein,
                SUM(carbs) as total_carbs, SUM(fat) as total_fat
         FROM food_logs
         WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY log_date ORDER BY log_date ASC"
    );
    $stmt->execute([$userId, $days]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'weekly_report') {
    $stmt = $pdo->prepare(
        "SELECT YEARWEEK(log_date, 1) as week_num,
                MIN(log_date) as week_start,
                MAX(log_date) as week_end,
                SUM(calories) as total_calories,
                AVG(calories) as avg_calories,
                COUNT(DISTINCT log_date) as days_logged
         FROM food_logs
         WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
         GROUP BY YEARWEEK(log_date, 1) ORDER BY week_num ASC"
    );
    $stmt->execute([$userId]);
    $weeks = $stmt->fetchAll();

    for ($i = 1; $i < count($weeks); $i++) {
        $diff = $weeks[$i]['avg_calories'] - $weeks[$i-1]['avg_calories'];
        $weeks[$i]['change'] = round($diff, 1);
        $weeks[$i]['trend']  = $diff > 50 ? 'up' : ($diff < -50 ? 'down' : 'stable');
    }
    if (count($weeks) > 0) {
        $weeks[0]['change'] = 0;
        $weeks[0]['trend']  = 'stable';
    }

    echo json_encode(['success' => true, 'weeks' => $weeks]);
    exit;
}

if ($action === 'monthly_report') {
    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(log_date, '%Y-%m') as month,
                DATE_FORMAT(log_date, '%M %Y') as month_label,
                SUM(calories) as total_calories,
                AVG(calories) as avg_calories,
                COUNT(DISTINCT log_date) as days_logged,
                SUM(protein) as total_protein,
                SUM(carbs) as total_carbs,
                SUM(fat) as total_fat
         FROM food_logs
         WHERE user_id = ? AND log_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(log_date, '%Y-%m') ORDER BY month ASC"
    );
    $stmt->execute([$userId]);
    $months = $stmt->fetchAll();

    $goal = $_SESSION['daily_goal'] ?? 2000;
    for ($i = 0; $i < count($months); $i++) {
        $months[$i]['avg_calories'] = round($months[$i]['avg_calories'], 1);
        $months[$i]['goal_diff']    = round($months[$i]['avg_calories'] - $goal, 1);
        if ($i > 0) {
            $diff = $months[$i]['avg_calories'] - $months[$i-1]['avg_calories'];
            $months[$i]['change'] = round($diff, 1);
            $months[$i]['trend']  = $diff > 50 ? 'up' : ($diff < -50 ? 'down' : 'stable');
        } else {
            $months[$i]['change'] = 0;
            $months[$i]['trend']  = 'stable';
        }
    }

    echo json_encode(['success' => true, 'months' => $months, 'goal' => $goal]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
