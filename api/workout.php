<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');
requireLogin();

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── List exercises (global + user custom) ────────────────────────────────────
if ($action === 'get_exercises') {
    $stmt = $pdo->prepare(
        'SELECT * FROM exercises WHERE user_id IS NULL OR user_id=? ORDER BY muscle_group, name'
    );
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Add custom exercise ──────────────────────────────────────────────────────
if ($action === 'add_exercise') {
    $name   = trim($_POST['name'] ?? '');
    $muscle = trim($_POST['muscle_group'] ?? '');

    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'اسم التمرين مطلوب']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO exercises (user_id, name, muscle_group, is_custom) VALUES (?,?,?,1)'
    );
    $stmt->execute([$userId, $name, $muscle]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => $name, 'muscle_group' => $muscle]);
    exit;
}

// ── Get workout plan (all days or one specific day) ──────────────────────────
if ($action === 'get_plan') {
    $day  = isset($_GET['day']) ? (int)$_GET['day'] : null;
    $base = '
        SELECT wp.*, e.name AS exercise_name, e.muscle_group,
               (SELECT wl.weight_used FROM workout_logs wl
                WHERE wl.user_id=wp.user_id AND wl.exercise_id=wp.exercise_id
                ORDER BY wl.log_date DESC, wl.created_at DESC LIMIT 1) AS last_weight,
               (SELECT wl2.log_date FROM workout_logs wl2
                WHERE wl2.user_id=wp.user_id AND wl2.exercise_id=wp.exercise_id
                ORDER BY wl2.log_date DESC, wl2.created_at DESC LIMIT 1) AS last_date
        FROM workout_plans wp
        JOIN exercises e ON wp.exercise_id = e.id
        WHERE wp.user_id=?';

    if ($day !== null) {
        $stmt = $pdo->prepare($base . ' AND wp.day_of_week=? ORDER BY wp.sort_order, wp.id');
        $stmt->execute([$userId, $day]);
    } else {
        $stmt = $pdo->prepare($base . ' ORDER BY wp.day_of_week, wp.sort_order, wp.id');
        $stmt->execute([$userId]);
    }

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Add exercise to plan ─────────────────────────────────────────────────────
if ($action === 'add_to_plan') {
    $exerciseId = (int)($_POST['exercise_id'] ?? 0);
    $day        = (int)($_POST['day_of_week'] ?? 0);
    $sets       = max(1, (int)($_POST['sets'] ?? 3));
    $reps       = max(1, (int)($_POST['reps'] ?? 10));
    $targetWt   = (float)($_POST['target_weight'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');

    if (!$exerciseId || $day < 0 || $day > 6) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
        exit;
    }

    // Verify exercise is accessible
    $check = $pdo->prepare('SELECT id FROM exercises WHERE id=? AND (user_id IS NULL OR user_id=?)');
    $check->execute([$exerciseId, $userId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'التمرين غير موجود']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO workout_plans (user_id, exercise_id, day_of_week, sets, reps, target_weight, notes) VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([$userId, $exerciseId, $day, $sets, $reps, $targetWt, $notes]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// ── Update plan item ─────────────────────────────────────────────────────────
if ($action === 'update_plan') {
    $planId   = (int)($_POST['plan_id'] ?? 0);
    $sets     = max(1, (int)($_POST['sets'] ?? 3));
    $reps     = max(1, (int)($_POST['reps'] ?? 10));
    $targetWt = (float)($_POST['target_weight'] ?? 0);

    $pdo->prepare(
        'UPDATE workout_plans SET sets=?, reps=?, target_weight=? WHERE id=? AND user_id=?'
    )->execute([$sets, $reps, $targetWt, $planId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

// ── Remove from plan ─────────────────────────────────────────────────────────
if ($action === 'remove_from_plan') {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $pdo->prepare('DELETE FROM workout_plans WHERE id=? AND user_id=?')->execute([$planId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

// ── Log completed workout ────────────────────────────────────────────────────
if ($action === 'log_workout') {
    $exerciseId = (int)($_POST['exercise_id'] ?? 0);
    $planId     = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $sets       = (int)($_POST['sets_completed'] ?? 0);
    $reps       = (int)($_POST['reps_completed'] ?? 0);
    $weight     = (float)($_POST['weight_used'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');
    $date       = $_POST['log_date'] ?? date('Y-m-d');

    if (!$exerciseId) {
        echo json_encode(['success' => false, 'message' => 'التمرين مطلوب']);
        exit;
    }

    $pdo->prepare(
        'INSERT INTO workout_logs (user_id, exercise_id, plan_id, sets_completed, reps_completed, weight_used, notes, log_date)
         VALUES (?,?,?,?,?,?,?,?)'
    )->execute([$userId, $exerciseId, $planId, $sets, $reps, $weight, $notes, $date]);

    // Bump target_weight in plan when a new PR is set
    if ($planId && $weight > 0) {
        $pdo->prepare(
            'UPDATE workout_plans SET target_weight=GREATEST(target_weight, ?) WHERE id=? AND user_id=?'
        )->execute([$weight, $planId, $userId]);
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── Today's logged exercises ─────────────────────────────────────────────────
if ($action === 'get_today_logs') {
    $today = date('Y-m-d');
    $stmt  = $pdo->prepare(
        'SELECT wl.*, e.name AS exercise_name FROM workout_logs wl
         JOIN exercises e ON wl.exercise_id = e.id
         WHERE wl.user_id=? AND wl.log_date=?'
    );
    $stmt->execute([$userId, $today]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Exercise history (last 20 sessions) ──────────────────────────────────────
if ($action === 'get_exercise_history') {
    $exerciseId = (int)($_GET['exercise_id'] ?? 0);
    if (!$exerciseId) {
        echo json_encode(['success' => false, 'message' => 'التمرين مطلوب']);
        exit;
    }
    $stmt = $pdo->prepare(
        'SELECT * FROM workout_logs WHERE user_id=? AND exercise_id=? ORDER BY log_date DESC, created_at DESC LIMIT 20'
    );
    $stmt->execute([$userId, $exerciseId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
