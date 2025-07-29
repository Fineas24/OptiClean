<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = escapeshellarg($_POST['command']);
    //$command='uscare';
    $output = shell_exec("python /var/www/html/opticlean/server/gpio_control2.py $command 2>&1");
    echo "Command executed: $command<br>";
    echo "Output:<br><pre>$output</pre>";
} else {
    echo "No command received.";
}
?>
