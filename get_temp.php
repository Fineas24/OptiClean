<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../config.php';
require_once 'rate_limit.php';

header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];
if (!rateLimitCheck($ip)) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests"]);
    exit;
}

function read_temp() {
    $base_dir = '/sys/bus/w1/devices/';
    $device_folders = glob($base_dir . '28*');

    if (!$device_folders || count($device_folders) === 0) {
        return null;
    }

    $device_file = $device_folders[0] . '/w1_slave';

    if (!file_exists($device_file)) {
        return null;
    }

    for ($i = 0; $i < 5; $i++) {
        $lines = file($device_file, FILE_IGNORE_NEW_LINES);
        if (!$lines || count($lines) < 2) {
            usleep(200000);
            continue;
        }

        if (substr($lines[0], -3) === 'YES' && preg_match('/t=(\d+)/', $lines[1], $matches)) {
            return floatval($matches[1]) / 1000.0;
        }

        usleep(200000);
    }

    return null;
}

$temp = read_temp();

if ($temp === null) {
    http_response_code(500);
    echo json_encode(["error" => "Sensor read failed"]);
    exit;
}

echo json_encode(["success" => true, "temperature" => $temp]);
