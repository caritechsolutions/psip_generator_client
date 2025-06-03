<?php
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['transportName'])) {
    $transportName = $data['transportName'];
    $directory = "transports/$transportName";

    if (is_dir($directory)) {
        array_map('unlink', glob("$directory/*.*"));
        rmdir($directory);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Transport not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}
?>