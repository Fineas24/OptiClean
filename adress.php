<?php
//echo 'a';
require_once 'auth_middleware.php';
$user = checkJWTAuthorization();
header('Content-Type: application/json');

$ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
echo json_encode(['ip' => $ip]);
?>
