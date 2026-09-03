<?php

// App Server nodes
$nodes = [
    "http://localhost:8080/Large-Scale-Data-Management/actions/register.php",
    "http://localhost:8081/Large-Scale-Data-Management/actions/register.php"
];

// Get request number from baseline_test case request = 1 when we manual register via webpage 
$request = isset($_POST["request"])
    ? (int) $_POST["request"]
    : 1;

// Round Robin
$nodeIndex = ($request - 1) % count($nodes);

// Which nodes are used?
$url = $nodes[$nodeIndex];
// $url = $nodes[0];

// Get registration data
$student_id = $_POST["student_id"];
$course_id = $_POST["course_id"];

// Send request to selected App Server
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, [
    "student_id" => $student_id,
    "course_id" => $course_id
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
$response = curl_exec($ch);

// Check error
if (curl_errno($ch)) {
    echo "FAILED";
} else {
    echo $response;
}

// Close cURL
curl_close($ch);

?>