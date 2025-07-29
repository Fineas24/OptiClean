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
    echo json_encode(["error"=>"Too many requests. Try later."]);
    exit;
}

$user = checkJWTAuthorization();
$username = $user->username ?? '';
if (!$username) {
    http_response_code(401);
    echo json_encode(["error"=>"Unauthorized"]);
    exit;
}

$stmt = DB_CONNECTION->prepare("SELECT username, active_at FROM users WHERE active = 1 LIMIT 1");
$stmt->execute();
$stmt->bind_result($activeUsername, $activeAt);
$found = $stmt->fetch();
$stmt->close();

$now = time();

if ($found) {
    if ($activeAt !== null && ($now - intval($activeAt)) > 120) {
        $clearStmt = DB_CONNECTION->prepare("UPDATE users SET active = 0, active_at = NULL WHERE username = ?");
        $clearStmt->bind_param("s", $activeUsername);
        $clearStmt->execute();
        $clearStmt->close();
        $found = false;
    }
}

if ($found) {
    if ($activeUsername === $username) {
        $upd = DB_CONNECTION->prepare("UPDATE users SET active_at = ? WHERE username = ?");
        $upd->bind_param("is", $now, $username);
        $upd->execute();
        $upd->close();
        http_response_code(200);
        echo json_encode(["status"=>"active"]);
    } else {
        http_response_code(423);
        echo json_encode(["error"=>"unavailable"]);
    }
    exit;
}

$set = DB_CONNECTION->prepare("UPDATE users SET active = 1, active_at = ? WHERE username = ?");
$set->bind_param("is", $now, $username);
if ($set->execute()) {
    http_response_code(200);
    echo json_encode(["status"=>"activated"]);
} else {
    http_response_code(500);
    echo json_encode(["error"=>"Failed to activate user"]);
}
$set->close();
exit;
