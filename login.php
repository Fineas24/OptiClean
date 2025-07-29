<?php
define('APP_INTERNAL', true); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../modules/vendor/autoload.php';
require '../config.php';
require_once 'rate_limit.php';
require_once 'create_token.php'; 

$ip = $_SERVER['REMOTE_ADDR'];

if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Please wait."]);
    exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username or password']);
    exit;
}

$stmt = DB_CONNECTION->prepare("SELECT password_hash FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($password_hash);
    $stmt->fetch();

    if (password_verify($password, $password_hash)) {
        try {
            $jwt = createJwtToken($username);
            echo json_encode(['token' => $jwt]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Token generation failed']);
            exit;
        }
    }
}

http_response_code(401);
echo json_encode(['error' => 'Invalid credentials']);
exit;
