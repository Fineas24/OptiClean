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

$stmt = DB_CONNECTION->prepare("SELECT selected_preset FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($selected_preset);

if ($stmt->fetch()) {
    $stmt->close();
    $preset_name = null;
    $presetStmt = DB_CONNECTION->prepare("SELECT name FROM presets WHERE preset_id = ? LIMIT 1");
    $presetStmt->bind_param("i", $selected_preset);
    $presetStmt->execute();
    $presetStmt->bind_result($preset_name);
    $presetStmt->fetch();
    $presetStmt->close();
    echo json_encode([
        "preset_id" => $selected_preset,
        "name" => $preset_name ?? ""
    ]);
} else {
    $stmt->close();
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
}

exit;
