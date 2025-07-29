<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    echo "Received: $username / $password";
} else {
    echo "Only POST allowed.";
}