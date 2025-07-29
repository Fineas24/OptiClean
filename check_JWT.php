<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once './auth_middleware.php';

header('Content-Type: application/json');

$user = checkJWTAuthorization();

if ($user) {
    echo json_encode([
        "valid" => true,
        "username" => $user->username ?? 'Unknown'
    ]);
    exit;
} else {
    echo json_encode([
        "valid" => false,
        "message" => "Token validation failed"
    ]);
    exit;
}