<?php

session_start();

require_once "../config/database.php";
require_once "../models/Registration.php";

if (!isset($_SESSION["student"])) {
    header("Location: ../pages/login.php");
    exit;
}

$student = $_SESSION["student"];

$student_id = $student["student_id"];
$course_id = $_POST["course_id"];

$conn = getConnection();


// 1. Check duplicate registration
if (isAlreadyRegistered($conn, $student_id, $course_id)) {

    die("You already registered for this course.");

}


// 2. Check course availability
$course = getCourseAvailability($conn, $course_id);

if (!$course) {

    die("Course does not exist.");

}


// 3. Check capacity
if ($course["registered"] >= $course["capacity"]) {

    die("Sorry, this course is full.");

}


// 4. Register student
if (registerStudent($conn, $student_id, $course_id)) {

    echo "Registration successful.";

} else {

    echo "Registration failed.";

}


$conn->close();