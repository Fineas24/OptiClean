<?php
// create_token.php

if (!defined('APP_INTERNAL')) {
    http_response_code(403);
    exit('Forbidden');
}

use Firebase\JWT\JWT;

/**
 * Generates a JWT token for a given username
 *
 * @param string $username
 * @return string JWT
 * @throws Exception if secret key is missing
 */
function createJwtToken(string $username): string {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $jwt_secret = $_ENV['JWT_SECRET'] ?? null;
    
    if (!$jwt_secret) {
        throw new Exception('JWT secret key not set in .env');
    }

    $payload = [
        'iat' => time(),
        'exp' => time() + 3600, // 1 hour
        'username' => $username
    ];

    return JWT::encode($payload, $jwt_secret, 'HS256');
}
