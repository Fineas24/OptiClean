<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../modules/vendor/autoload.php';
require '../config.php';
require_once 'rate_limit.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

// === Rate Limit Check ===
$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Try later."]);
    exit;
}

// === JWT Authentication ===
$user = checkJWTAuthorization();
$username = $user->username ?? '';
if (!$username) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// === Get User ID and Active Status ===
$stmt = DB_CONNECTION->prepare("SELECT id, active FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $active);
$found = $stmt->fetch();
$stmt->close();

if (!$found || intval($active) !== 1) {
    http_response_code(403);
    echo json_encode(["error" => "User is not active"]);
    exit;
}

// === Check if active_preset is already in use ===
$res = DB_CONNECTION->query("SELECT COUNT(*) FROM active_preset");
$count = $res ? $res->fetch_row()[0] : 0;
if ($count > 0) {
    http_response_code(409);
    echo json_encode(["error" => "OptiClean is already running"]);
    exit;
}

// === Fetch auto presets into array (safe step before insert) ===
$q = DB_CONNECTION->query("SELECT use_order, type, time_times FROM auto_preset");
if (!$q || $q->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "No auto presets found"]);
    exit;
}

$presets = [];
while ($row = $q->fetch_assoc()) {
    $presets[] = $row;
}
$q->free(); // ✅ Free result before using connection again

// === Insert presets into active_preset with current user's ID ===
$insert = DB_CONNECTION->prepare("INSERT INTO active_preset (use_order, owner_id, type, time_times) VALUES (?, ?, ?, ?)");
foreach ($presets as $p) {
    $insert->bind_param("iisi", $p['use_order'], $user_id, $p['type'], $p['time_times']);
    $insert->execute();
}
$insert->close();

// === Success Response ===
http_response_code(200);
echo json_encode(["success" => true]);
exit;
