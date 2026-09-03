<?php

session_start();

require_once "../config/database.php";
require_once "../models/Registration.php";


// ==================================================
// 1. Check request
// ==================================================

if (
    !isset($_SESSION["student"]) &&
    !isset($_POST["student_id"])
) {
    header("Location: ../pages/login.php");
    exit;
}


// ==================================================
// 2. Start measuring response time
// ==================================================

$startTime = microtime(true);


// ==================================================
// 3. Get student ID
// ==================================================

if (isset($_POST["student_id"])) {

    // Load testing
    $student_id = $_POST["student_id"];

} else {

    // Normal browser registration
    $student_id = $_SESSION["student"]["student_id"];
}


// ==================================================
// 4. Get course ID
// ==================================================

if (!isset($_POST["course_id"])) {

    echo "FAILED";
    exit;
}

$course_id = $_POST["course_id"];


// ==================================================
// 5. Connect to database
// ==================================================

$conn = getConnection();


// ==================================================
// 6. Registration process
// ==================================================

$success = false;
$message = "";

// Check duplicate registration
if (isAlreadyRegistered(
    $conn,
    $student_id,
    $course_id
)) {

    $message = "<h3 style='color: red'>You already registered for this course.</h3>";

} else {

    // Get course information
    $course = getCourseAvailability(
        $conn,
        $course_id
    );

    // Course doesn't exist
    if (!$course) {

        $message = "Course does not exist.";

    } else {

        // Check capacity
        if (
            $course["registered"]
            >=
            $course["capacity"]
        ) {

            $message = "Sorry, this course is full.";

        } else {

            // Register student
            if (
                registerStudent(
                    $conn,
                    $student_id,
                    $course_id
                )
            ) {

                $success = true;
                $message = "Registration successful.";

            } else {

                $message = "Registration failed.";
            }
        }
    }
}


// ==================================================
// 7. Calculate response time
// ==================================================

$endTime = microtime(true);

$responseTime =
    ($endTime - $startTime) * 1000;


// ==================================================
// 8. Get final registration count
//    ONLY for the tested course
// ==================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM tbl_registrations
    WHERE course_id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $course_id);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$finalRegistrationCount = (int) $row["total"];

$stmt->close();

// ==================================================
// 9. Return machine-readable result
// ==================================================

if (isset($_POST["test_mode"]) && $_POST["test_mode"] == 1) {

    // Response for baseline_test.php
    if ($success) {
        echo "SUCCESS";
    } else {
        echo "FAILED";
    }

} else {
    // Response for normal browser
    echo $message;
}

// ==================================================
// 10. Close connection
// ==================================================

$conn->close();

?>