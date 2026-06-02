<?php
// ══════════════════════════════════════════════
//  PuneRooms — Database Configuration (db.php)
// ══════════════════════════════════════════════

define('DB_HOST', '10.1.32.10');
define('DB_PORT', '5432');
define('DB_NAME', 'tyb2');
define('DB_USER', 'tyb2');
define('DB_PASS', 'pravin807');

function db_connect() {
    static $conn = null;
    if ($conn !== null) return $conn; // reuse connection - faster!
    $conn_string = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s connect_timeout=5",
        DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
    );
    $conn = @pg_connect($conn_string);
    if (!$conn) {
        die('<div style="font-family:sans-serif;max-width:600px;margin:80px auto;padding:30px;
             background:#fff0f0;border:1px solid #e74c3c;border-radius:12px;text-align:center;">
             <h2 style="color:#c0392b">❌ Database Connection Failed</h2>
             <p>Cannot connect to PostgreSQL at <strong>' . DB_HOST . ':' . DB_PORT . '</strong></p>
             <p>Database: <strong>' . DB_NAME . '</strong> | User: <strong>' . DB_USER . '</strong></p>
             <p style="margin-top:12px;font-size:14px;color:#666">Check PostgreSQL is running and credentials are correct.</p>
        </div>');
    }
    return $conn;
}

function db_query($conn, $sql, $params = []) {
    if (empty($params)) {
        $result = pg_query($conn, $sql);
    } else {
        $result = pg_query_params($conn, $sql, $params);
    }
    if (!$result) {
        error_log('DB Error: ' . pg_last_error($conn));
        return false;
    }
    return $result;
}

function db_fetch_all($result) {
    if (!$result) return [];
    return pg_fetch_all($result) ?: []; // faster than manual loop
}

function db_fetch_one($result) {
    if (!$result) return null;
    return pg_fetch_assoc($result) ?: null;
}

function clean($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

// Uses PHP SHA256 — consistent with database inserts (we store PHP-generated hashes)
function hash_password($password) {
    return hash('sha256', $password);
}

// ── SESSION HELPERS ──
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', 86400);
        session_set_cookie_params(86400);
        session_start();
    }
}

function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

function is_admin() {
    start_session();
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function is_owner() {
    start_session();
    return isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'owner' || $_SESSION['user_type'] === 'admin');
}

function require_login($redirect = 'login.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: index.php?msg=' . urlencode('Access denied. Admin only.') . '&mtype=error');
        exit;
    }
}

function get_user_id()   { return $_SESSION['user_id']    ?? null; }
function get_user_name() { return $_SESSION['user_name']  ?? 'Guest'; }
function get_user_type() { return $_SESSION['user_type']  ?? 'student'; }

function set_user_session($user) {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
    $_SESSION['user_type']  = $user['user_type'];
    $_SESSION['user_email'] = $user['email'];
}

function logout_user() {
    session_unset();
    session_destroy();
}

// ── AMENITY HELPERS ──
function is_true_val($v) {
    return $v === 't' || $v === 'true' || $v === '1' || $v === true;
}

function amenity_badges($room) {
    $list = [];
    if (is_true_val($room['has_wifi']??false))         $list[] = '<span class="badge">📶 WiFi</span>';
    if (is_true_val($room['has_ac']??false))           $list[] = '<span class="badge">❄️ AC</span>';
    if (is_true_val($room['has_meals']??false))        $list[] = '<span class="badge">🍽️ Meals</span>';
    if (is_true_val($room['has_furnished']??false))    $list[] = '<span class="badge">🛋️ Furnished</span>';
    if (is_true_val($room['has_parking']??false))      $list[] = '<span class="badge">🚗 Parking</span>';
    if (is_true_val($room['has_laundry']??false))      $list[] = '<span class="badge">👕 Laundry</span>';
    if (is_true_val($room['has_security']??false))     $list[] = '<span class="badge">🔒 Security</span>';
    if (is_true_val($room['has_power_backup']??false)) $list[] = '<span class="badge">⚡ Power</span>';
    if (is_true_val($room['has_balcony']??false))      $list[] = '<span class="badge">🌇 Balcony</span>';
    if (is_true_val($room['has_cctv']??false))         $list[] = '<span class="badge">📹 CCTV</span>';
    if (is_true_val($room['has_water']??false))        $list[] = '<span class="badge">💧 Water</span>';
    if (is_true_val($room['has_pets']??false))         $list[] = '<span class="badge">🐾 Pets OK</span>';
    return implode('', array_slice($list, 0, 5));
}

function stars($rating) {
    $r = floatval($rating);
    $full = floor($r); $half = ($r - $full) >= 0.5 ? 1 : 0; $empty = 5 - $full - $half;
    return str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', $empty) .
           ' <span style="font-size:.75rem;opacity:.7;">(' . number_format($r,1) . ')</span>';
}

function get_stats($conn) {
    $stats = [];
    // Single query for performance
    $r = db_query($conn, "
        SELECT
            COUNT(*) AS total_rooms,
            SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) AS available_rooms,
            SUM(CASE WHEN status='Rented' THEN 1 ELSE 0 END) AS rented_rooms,
            ROUND(AVG(CASE WHEN status='Available' THEN monthly_rent END)) AS avg_rent,
            COUNT(DISTINCT area) AS total_areas
        FROM pr_rooms
    ");
    $row = db_fetch_one($r);
    $stats['total_rooms']     = (int)($row['total_rooms'] ?? 0);
    $stats['available_rooms'] = (int)($row['available_rooms'] ?? 0);
    $stats['rented_rooms']    = (int)($row['rented_rooms'] ?? 0);
    $stats['avg_rent']        = (int)($row['avg_rent'] ?? 0);
    $stats['total_areas']     = (int)($row['total_areas'] ?? 0);

    $r = db_query($conn, "SELECT COUNT(*) AS c FROM pr_users");
    $stats['total_users'] = (int)(db_fetch_one($r)['c'] ?? 0);
    return $stats;
}

function get_saved_room_ids($conn, $user_id) {
    $result = db_query($conn, "SELECT room_id FROM pr_saved WHERE user_id = $1", [$user_id]);
    if (!$result) return [];
    $rows = pg_fetch_all($result);
    return $rows ? array_column($rows, 'room_id') : [];
}

function get_filtered_rooms($conn, $search='', $min_rent=0, $max_rent=0, $room_type='', $gender='', $area='', $sort='newest', $status='') {
    $sql = "SELECT * FROM pr_rooms WHERE 1=1";
    $params = []; $i = 1;
    if ($search) {
        $sql .= " AND (title ILIKE \$$i OR area ILIKE \$$i OR nearby_college ILIKE \$$i OR description ILIKE \$$i)";
        $params[] = '%'.$search.'%'; $i++;
    }
    if ($min_rent > 0)  { $sql .= " AND monthly_rent >= \$$i"; $params[] = $min_rent; $i++; }
    if ($max_rent > 0)  { $sql .= " AND monthly_rent <= \$$i"; $params[] = $max_rent; $i++; }
    if ($room_type)     { $sql .= " AND room_type = \$$i"; $params[] = $room_type; $i++; }
    if ($gender)        { $sql .= " AND gender_preference = \$$i"; $params[] = $gender; $i++; }
    if ($area)          { $sql .= " AND area = \$$i"; $params[] = $area; $i++; }
    if ($status)        { $sql .= " AND status = \$$i"; $params[] = $status; $i++; }
    if ($sort === 'rent-low')   $sql .= " ORDER BY monthly_rent ASC";
    elseif ($sort === 'rent-high') $sql .= " ORDER BY monthly_rent DESC";
    else $sql .= " ORDER BY upload_date DESC";
    return db_fetch_all(db_query($conn, $sql, $params));
}
?>
