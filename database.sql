CREATE DATABASE IF NOT EXISTS calories_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE calories_tracker;

-- ── Users ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('user','admin') DEFAULT 'user',
    daily_goal      INT DEFAULT 2000,
    age             INT DEFAULT NULL,
    weight          DECIMAL(5,2) DEFAULT NULL,
    height          DECIMAL(5,2) DEFAULT NULL,
    gender          ENUM('male','female') DEFAULT NULL,
    fitness_goal    ENUM('lose_weight','gain_weight','maintain') DEFAULT NULL,
    profile_complete TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Food logs ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS food_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    food_name  VARCHAR(255) NOT NULL,
    calories   INT NOT NULL,
    protein    DECIMAL(6,2) DEFAULT 0,
    carbs      DECIMAL(6,2) DEFAULT 0,
    fat        DECIMAL(6,2) DEFAULT 0,
    meal_type  ENUM('breakfast','lunch','dinner','snack') DEFAULT 'snack',
    image_path VARCHAR(500) DEFAULT NULL,
    log_date   DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Settings ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    `key`      VARCHAR(100) UNIQUE NOT NULL,
    value      TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Body photos ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS body_photos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    photo_type     ENUM('front','back') NOT NULL,
    photo_path     VARCHAR(500) NOT NULL,
    weight_at_time DECIMAL(5,2) DEFAULT NULL,
    notes          TEXT DEFAULT NULL,
    taken_at       DATE NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Weight history ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS weight_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    weight     DECIMAL(5,2) NOT NULL,
    notes      TEXT DEFAULT NULL,
    log_date   DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_date (user_id, log_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Exercise library ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS exercises (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT DEFAULT NULL,
    name         VARCHAR(200) NOT NULL,
    muscle_group VARCHAR(100) DEFAULT NULL,
    is_custom    TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Workout plans ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS workout_plans (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    exercise_id   INT NOT NULL,
    day_of_week   TINYINT NOT NULL COMMENT '0=Sun 1=Mon 2=Tue 3=Wed 4=Thu 5=Fri 6=Sat',
    sets          INT DEFAULT 3,
    reps          INT DEFAULT 10,
    target_weight DECIMAL(6,2) DEFAULT 0,
    sort_order    INT DEFAULT 0,
    notes         TEXT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- ── Workout logs ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS workout_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    exercise_id     INT NOT NULL,
    plan_id         INT DEFAULT NULL,
    sets_completed  INT DEFAULT 0,
    reps_completed  INT DEFAULT 0,
    weight_used     DECIMAL(6,2) DEFAULT 0,
    notes           TEXT DEFAULT NULL,
    log_date        DATE NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- ── Default settings ──────────────────────────────────────────────────────────
INSERT IGNORE INTO settings (`key`, value) VALUES
('ai_provider',       'gemini'),
('gemini_api_key',    ''),
('gemini_model',      'gemini-1.5-flash'),
('app_name',          'CalTrack'),
('default_daily_goal','2000');

-- ── Default admin ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO users (name, email, password, role, daily_goal, profile_complete) VALUES
('Admin', 'admin@caltrack.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 2000, 1);
-- Default password: password

-- ── Predefined exercises ──────────────────────────────────────────────────────
INSERT IGNORE INTO exercises (id, user_id, name, muscle_group, is_custom) VALUES
(1,  NULL, 'بنش بريس (بار)',       'الصدر',           0),
(2,  NULL, 'بنش بريس (دمبل)',      'الصدر',           0),
(3,  NULL, 'تشست فلاي دمبل',       'الصدر',           0),
(4,  NULL, 'ديدليفت',              'الظهر والأرجل',   0),
(5,  NULL, 'سحب عالي (بار)',       'الظهر',           0),
(6,  NULL, 'سحب بكرة',            'الظهر',           0),
(7,  NULL, 'كيرل بايسبس (بار)',    'البايسبس',        0),
(8,  NULL, 'كيرل دمبل',           'البايسبس',        0),
(9,  NULL, 'ضغط أكتاف (بار)',     'الأكتاف',         0),
(10, NULL, 'ضغط أكتاف (دمبل)',    'الأكتاف',         0),
(11, NULL, 'شراج (ترابيس)',        'الأكتاف',         0),
(12, NULL, 'سكوات',               'الأرجل',          0),
(13, NULL, 'ليج بريس',            'الأرجل',          0),
(14, NULL, 'ليج كيرل',            'الأرجل',          0),
(15, NULL, 'ليج إكستنشن',         'الأرجل',          0),
(16, NULL, 'تريسبس بولي',          'التريسبس',        0),
(17, NULL, 'ديبس',                'التريسبس',        0),
(18, NULL, 'رفع ربلة الساق',      'الساق',           0),
(19, NULL, 'بلانك',               'الكور',           0),
(20, NULL, 'كرانش',               'البطن',           0);
