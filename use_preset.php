<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../modules/vendor/autoload.php';
require '../config.php';
require_once 'rate_limit.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Try later."]);
    exit;
}

$user = checkJWTAuthorization();
$username = $user->username ?? '';
if (!$username) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// === Check active user and session timeout ===
$stmt = DB_CONNECTION->prepare("SELECT username, active_at FROM users WHERE active = 1 LIMIT 1");
$stmt->execute();
$stmt->bind_result($activeUsername, $activeAt);
$found = $stmt->fetch();
$stmt->close();

$now = time();
if ($found && $activeAt !== null && ($now - intval($activeAt)) > 120) {
    $clear = DB_CONNECTION->prepare("UPDATE users SET active = 0, active_at = NULL WHERE username = ?");
    $clear->bind_param("s", $activeUsername);
    $clear->execute();
    $clear->close();
    $found = false;
}

if ($found) {
    if ($activeUsername !== $username) {
        http_response_code(423);
        echo json_encode(["error" => "OptiClean is in use"]);
        exit;
    }
    // Refresh active_at timestamp
    $upd = DB_CONNECTION->prepare("UPDATE users SET active_at = ? WHERE username = ?");
    $upd->bind_param("is", $now, $username);
    $upd->execute();
    $upd->close();
}

// === Check if active_preset is already in use ===
$res = DB_CONNECTION->query("SELECT COUNT(*) FROM active_preset");
$count = $res ? $res->fetch_row()[0] : 0;
if ($count > 0) {
    http_response_code(409);
    echo json_encode(["error" => "OptiClean is in use"]);
    exit;
}

// === Get selected preset for current user ===
$u = DB_CONNECTION->prepare("SELECT selected_preset FROM users WHERE username = ?");
$u->bind_param("s", $username);
$u->execute();
$u->bind_result($preset_id);
if (!$u->fetch() || intval($preset_id) <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "No preset selected"]);
    $u->close();
    exit;
}
$u->close();

// === Fetch all steps for that preset into array ===
$q = DB_CONNECTION->prepare("SELECT use_order, owner_id, type, time_times FROM presets WHERE preset_id = ? ORDER BY use_order ASC");
$q->bind_param("i", $preset_id);
$q->execute();
$q->bind_result($use_order, $owner_id, $type, $time_times);

$steps = [];
while ($q->fetch()) {
    $steps[] = [$use_order, $owner_id, $type, $time_times];
}
$q->close(); // ✅ Close before running further queries

// === Insert each step into active_preset ===
$insert = DB_CONNECTION->prepare("INSERT INTO active_preset (use_order, owner_id, type, time_times) VALUES (?,?,?,?)");
foreach ($steps as $step) {
    $insert->bind_param("iisi", $step[0], $step[1], $step[2], $step[3]);
    $insert->execute();
}
$insert->close();

// === Done ===
http_response_code(200);
echo json_encode(["success" => true, "preset_id" => $preset_id]);
exit;
