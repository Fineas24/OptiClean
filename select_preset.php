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

$data = json_decode(file_get_contents('php://input'), true);
$preset_id = isset($data['preset_id']) ? intval($data['preset_id']) : 0;
if ($preset_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid 'preset_id'"]);
    exit;
}

// Update user's selected_preset
$update = DB_CONNECTION->prepare("UPDATE users SET selected_preset = ? WHERE username = ?");
$update->bind_param("is", $preset_id, $username);
if (!$update->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update selected preset"]);
    $update->close();
    exit;
}
$update->close();

// Get first preset with matching preset_id
$name = null;
$stmt = DB_CONNECTION->prepare("SELECT name FROM presets WHERE preset_id = ? ORDER BY id ASC LIMIT 1");
$stmt->bind_param("i", $preset_id);
$stmt->execute();
$stmt->bind_result($name);
if ($stmt->fetch()) {
    echo json_encode(["success" => true, "preset_id" => $preset_id, "name" => $name]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Preset not found"]);
}
$stmt->close();
exit;
