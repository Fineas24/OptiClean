<?php
include 'config.php';

header('Content-Type: application/json');

$sql = "SELECT id, text, time, author FROM messages";
$result = $conn->query($sql);

$messages = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

echo json_encode($messages);

$conn->close();
?>
