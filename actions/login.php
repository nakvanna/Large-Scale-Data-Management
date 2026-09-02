<?php

session_start();

require_once "../config/database.php";
require_once "../models/Student.php";

$conn = getConnection();

$student_id = $_POST["student_id"];

$student = findStudent($conn, $student_id);

if ($student) {

    $_SESSION["student"] = $student;

    header("Location: ../pages/registration.php");

    exit;

} else {

    echo "Student ID does not exist.";

}

$conn->close();