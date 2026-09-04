<?php

session_start();

require_once "../config/shard_database.php";
require_once "../models/Student.php";

// ============================================================
// Get student ID
// ============================================================

$student_id = $_POST["student_id"];

// ============================================================
// Find the correct database shard
// ============================================================

$conn = getShardConnection($student_id);

// Student ID is outside S001-S500
if ($conn === null) {

    echo "Student ID does not exist.";
    exit;
}

// ============================================================
// Find student on the correct shard
// ============================================================

$student = findStudent(
    $conn,
    $student_id
);

// ============================================================
// Login result
// ============================================================

if ($student) {

    $_SESSION["student"] = $student;

    header(
        "Location: ../pages/registration.php"
    );

    exit;

} else {

    echo "Student ID does not exist.";
}

// ============================================================
// Close connection
// ============================================================

$conn->close();

?>