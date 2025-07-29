<?php
function rateLimitCheck($ip, $limit = 1000, $seconds = 60) {
    $file = sys_get_temp_dir() . "/rate_limit_{$ip}.txt";
    $timestamps = [];
    if (file_exists($file)) {
        $timestamps = json_decode(file_get_contents($file), true);
        $timestamps = array_filter($timestamps, fn($t) => $t > time() - $seconds);
    }
    if (count($timestamps) >= $limit) {
        return false;
    }
    $timestamps[] = time();
    file_put_contents($file, json_encode($timestamps));
    return true;
}
?>