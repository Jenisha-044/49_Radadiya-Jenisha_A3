<?php
header("Content-Type: application/json; charset=UTF-8");

// PHP REST API Response
$data = [
    "status" => "success",
    "message" => "Hello from PHP REST API!",
    "server_time" => date("Y-m-d H:i:s"),
    "data" => [
        "course" => "MSc ICT",
        "assignment" => "PHP & Express REST API Integration"
    ]
];

echo json_encode($data);
exit;
?>