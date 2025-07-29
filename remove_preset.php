<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../modules/vendor/autoload.php';
require '../config.php';
require_once './auth_middleware.php'; // Your JWT middleware here
require_once 'rate_limit.php';

header('Content-Type: application/json');

// Rate limit check (optional, if you need to limit requests)
$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Try later."]);
    exit;
}

// JWT Authentication (will get user details from token)
$user = checkJWTAuthorization();
$username = $user->username ?? '';

if (!$username) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get user ID
$stmt = DB_CONNECTION->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($owner_id);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(["error" => "User not found"]);
    exit;
}
$stmt->close();

// Parse and validate input
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !isset($data['preset_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Request must contain preset_id"]);
    exit;
}

$preset_id = intval($data['preset_id']); // Ensure it's an integer

// Check if the preset exists and belongs to the user (ownership validation)
$stmt = DB_CONNECTION->prepare("SELECT COUNT(*) FROM presets WHERE preset_id = ? AND owner_id = ?");
$stmt->bind_param("ii", $preset_id, $owner_id);
$stmt->execute();
$stmt->bind_result($preset_exists);
$stmt->fetch();
$stmt->close();

if ($preset_exists == 0) {
    http_response_code(404);
    echo json_encode(["error" => "Preset not found or does not belong to the user"]);
    exit;
}

// Remove all instances of the preset_id for the current user
$deleteStmt = DB_CONNECTION->prepare("DELETE FROM presets WHERE preset_id = ? AND owner_id = ?");
$deleteStmt->bind_param("ii", $preset_id, $owner_id);
$deleteStmt->execute();

// Check if deletion was successful
if ($deleteStmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Preset removed successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to remove preset"]);
}

$deleteStmt->close();
exit;
