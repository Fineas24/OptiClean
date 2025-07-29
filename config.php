<?php
// config.php

// Datele pentru conexiune
require __DIR__ . '/modules/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/server');
$dotenv->load();

// Database connection using env variables
$host = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];

// Crearea conexiunii
$conn = new mysqli($host, $username, $password, $database);

// Verificarea conexiunii
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Setează setările de caractere pentru a evita problemele de encoding (opțional)
$conn->set_charset("utf8");

// Poți adăuga o variabilă globală pentru a o folosi în alte fișiere
define('DB_CONNECTION', $conn);
?>
