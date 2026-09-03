<?php

// ============================================================
// App Server Nodes
// ============================================================

$nodes = [
    "http://localhost:8080/Large-Scale-Data-Management/actions/register.php",
    "http://localhost:8081/Large-Scale-Data-Management/actions/register.php"
];


// ============================================================
// Get request number
//
// Baseline test sends:
// request = 1, 2, 3, ...
//
// Manual registration doesn't send request,
// so we use 1.
// ============================================================

$request = isset($_POST["request"])
    ? (int) $_POST["request"]
    : 1;


// ============================================================
// Round Robin Load Balancing
// ============================================================

$nodeIndex =
    ($request - 1)
    % count($nodes);


// Selected App Server

$url =
    $nodes[$nodeIndex];


// ============================================================
// Get registration data
// ============================================================

$student_id =
    $_POST["student_id"];

$course_id =
    $_POST["course_id"];


// ============================================================
// Get test mode
//
// Load test:
//     test_mode = 1
//
// Manual registration:
//     test_mode doesn't exist
// ============================================================

$test_mode =
    isset($_POST["test_mode"])
    ? $_POST["test_mode"]
    : 0;


// ============================================================
// Send request to selected App Server
// ============================================================

$ch =
    curl_init($url);


curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);


// IMPORTANT:
// Forward test_mode to register.php
curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    [
        "student_id" => $student_id,
        "course_id"  => $course_id,
        "test_mode"  => $test_mode
    ]
);


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


// ============================================================
// Execute request
// ============================================================

$response =
    curl_exec($ch);


// ============================================================
// Check cURL error
// ============================================================

if (curl_errno($ch)) {

    echo "FAILED";

} else {

    // Return exactly what register.php returns
    echo trim($response);
}


// ============================================================
// Close cURL
// ============================================================

curl_close($ch);

?>