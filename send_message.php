<?php
include 'config.php';

// Read the POST body (JSON)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!$data || !isset($data['message']) || !isset($data['author'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Mesaj sau autor invalid/lipsă']);
    exit;
}

$message = $conn->real_escape_string($data['message']);
$author = $conn->real_escape_string($data['author']);

// Insert query including author
$sql = "INSERT INTO messages (text, author, time) VALUES ('$message', '$author', NOW())";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la inserare: ' . $conn->error]);
}

$conn->close();
?>
