<?php
require '../config.php';
require_once 'rate_limit.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests"]);
    exit;
}

// JWT auth
$user = checkJWTAuthorization();
$username = $user->username ?? '';

if (!$username) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get user ID from username
$stmt = DB_CONNECTION->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($owner_id);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}
$stmt->close();

// Fetch all presets for the user
$stmt = DB_CONNECTION->prepare("SELECT preset_id, use_order, type, time_times, name FROM presets WHERE owner_id = ? ORDER BY preset_id, use_order");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$presets = [];
while ($row = $result->fetch_assoc()) {
    $presets[] = $row;
}
$stmt->close();

echo json_encode(["success" => true, "presets" => $presets]);
exit;
