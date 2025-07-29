<?php
// test.php

// Includerea fișierului de configurare pentru conexiunea la baza de date
include 'config.php';

// Testarea conexiunii
if ($conn) {
    echo "Conexiunea la baza de date a fost realizată cu succes!<br>";

    // Interogare SELECT
    $sql = "SELECT id, nume, prenume, ani FROM test"; // Aici presupunem că există tabelul 'users'

    // Executarea interogării
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Afișarea rezultatelor
        echo "<h2>Rezultate din tabelul 'test':</h2>";
        echo "<table border='1'><tr><th>ID</th><th>nume</th><th>prenume</th><th>ani</th></tr>";
        
        // Iterează prin fiecare rând din rezultatele interogării
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['nume'] . "</td><td>" . $row['prenume'] . "</td><td>" . $row['ani'] . "</td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "Nu au fost găsite date în tabelul 'users'.";
    }
} else {
    echo "Eroare la conectarea la baza de date: " . $conn->connect_error;
}

// Închide conexiunea la baza de date
$conn->close();
?>
