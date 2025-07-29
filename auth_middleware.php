<?php
ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../modules/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

function checkJWTAuthorization() {
    $jwt_secret = $_ENV['JWT_SECRET'];
    $headers = apache_request_headers(); 

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Authorization header missing"]);
        exit;
    }

    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid Authorization header format"]);
        exit;
    }

    $jwt = substr($authHeader, 7);

    try {
        $decoded = JWT::decode($jwt, new Key($jwt_secret, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid or expired token: " . $e->getMessage()]);
        exit;
    }
}
/*
require_once 'authMiddleware.php';

// Check JWT and block unauthorized requests
$user = checkJWTAuthorization();

*/
?>