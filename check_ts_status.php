<?php
if (!isset($_POST['name'])) {
    echo "not running";
    exit;
}

$name = $_POST['name'];
$statusFile = "/var/www/html/epg/transports/{$name}/status.ini";

if (!file_exists($statusFile)) {
    echo "not running";
    exit;
}

// Read the status file
$statusContent = parse_ini_file($statusFile, true);

if (!isset($statusContent['Process']['pid'])) {
    echo "not running";
    exit;
}

$pid = $statusContent['Process']['pid'];

// Check if the process is running
exec("ps -p $pid", $output, $result);

if ($result == 0) {
    echo "running";
} else {
    echo "not running";
}
?>
