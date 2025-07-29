<?php
define('APP_INTERNAL', true); // Allow secure internal includes

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once '../modules/vendor/autoload.php';
require_once 'rate_limit.php';
require_once 'create_token.php'; // ✅ Import JWT creation function

$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Please wait."]);
    exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');
$repeat_password = trim($data['repeat_password'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username or password']);
    exit;
}

if (!$repeat_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Please repeat password']);
    exit;
}

if ($password !== $repeat_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

// Check if username already exists
$stmt = DB_CONNECTION->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Username already taken']);
    exit;
}

// Insert new user
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = DB_CONNECTION->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password_hash);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create user']);
    exit;
}

$userId = $stmt->insert_id;

try {
    $token = createJwtToken($username);

    echo json_encode([
        'message' => 'User created successfully',
        'token' => $token
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Token generation failed']);
}
