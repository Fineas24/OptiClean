<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../modules/vendor/autoload.php';
require '../config.php';
require_once 'rate_limit.php';
require_once 'auth_middleware.php'; // Your JWT middleware here

header('Content-Type: application/json');

// Rate limit check
$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Try later."]);
    exit;
}

// JWT Auth check via middleware
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
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Request must be array"]);
    exit;
}

if (empty($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Include steps"]);
    exit;
}

$valid_types = ['wash', 'dry', 'spray', 'shake'];
$order_numbers = [];
$used_names = [];

// Validate each step
foreach ($data as $index => $obj) {
    if (!isset($obj['use_order'], $obj['type'], $obj['time_times'], $obj['name'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fields at item $index"]);
        exit;
    }

    $use_order = intval($obj['use_order']);
    $type = strtolower($obj['type']);
    $time_times = intval($obj['time_times']);
    $name = substr($obj['name'], 0, 100);

    // Check for invalid orders, types, etc.
    if ($use_order < 1) {
        http_response_code(400);
        echo json_encode(["error" => "Order invalid at $index"]);
        exit;
    }

    if (!is_numeric($obj['time_times'])) {
        http_response_code(400);
        echo json_encode(["error" => "Time invalid at $index"]);
        exit;
    }

    if ($time_times > 300) {
        http_response_code(400);
        echo json_encode(["error" => "Time max 300 at $index"]);
        exit;
    }

    if ($type === 'spray' && $time_times > 20) {
        http_response_code(400);
        echo json_encode(["error" => "Spray max 20 at $index"]);
        exit;
    }

    if (!in_array($type, $valid_types, true)) {
        http_response_code(400);
        echo json_encode(["error" => "Type invalid at $index"]);
        exit;
    }

    if (!is_string($name) || trim($name) === '') {
        http_response_code(400);
        echo json_encode(["error" => "Name empty at $index"]);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
        http_response_code(400);
        echo json_encode(["error" => "Bad name at $index"]);
        exit;
    }

    // Check if the name already exists in the database for the current user
    $nameCheckStmt = DB_CONNECTION->prepare("SELECT COUNT(*) FROM presets WHERE owner_id = ? AND name = ?");
    $nameCheckStmt->bind_param("is", $owner_id, $name);
    $nameCheckStmt->execute();
    $nameCheckStmt->bind_result($name_exists);
    $nameCheckStmt->fetch();
    $nameCheckStmt->close();

    if ($name_exists > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Name exists ($name)"]);
        exit;
    }

    // Check for duplicate orders
    if (in_array($use_order, $order_numbers, true)) {
        http_response_code(400);
        echo json_encode(["error" => "Order repeated"]);
        exit;
    }

    // Add to the arrays for subsequent checks
    $order_numbers[] = $use_order;
    $used_names[] = $name;
}

// Validate order sequence
sort($order_numbers);
for ($i = 1; $i <= count($order_numbers); $i++) {
    if ($order_numbers[$i - 1] !== $i) {
        http_response_code(400);
        echo json_encode(["error" => "Order must be 1,2,3..."]);
        exit;
    }
}

// Generate new preset ID
$result = DB_CONNECTION->query("SELECT MAX(preset_id) FROM presets");
$row = $result->fetch_row();
$new_preset_id = ($row[0] ?? 0) + 1;

// Insert steps into the database
$insertStmt = DB_CONNECTION->prepare("
    INSERT INTO presets (preset_id, use_order, owner_id, type, time_times, name)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($data as $obj) {
    $use_order = intval($obj['use_order']);
    $type = strtolower($obj['type']);
    $time_times = intval($obj['time_times']);
    $name = substr($obj['name'], 0, 100);

    $insertStmt->bind_param("iiisis", $new_preset_id, $use_order, $owner_id, $type, $time_times, $name);
    $insertStmt->execute();
}

echo json_encode(["success" => true, "preset_id" => $new_preset_id]);
exit;
