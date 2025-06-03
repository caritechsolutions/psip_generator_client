<?php
$transportsDirectory = 'transports';
$transports = array_filter(glob("$transportsDirectory/*"), 'is_dir');
$transports = array_map(function($dir) {
    return basename($dir);
}, $transports);

echo json_encode($transports);
?>