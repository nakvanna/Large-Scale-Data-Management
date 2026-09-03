<?php

session_start();

require_once "../config/database.php";
require_once "../models/Registration.php";


// ============================================================
// Check authentication
// ============================================================

if (
    !isset($_SESSION["student"])
    &&
    !isset($_POST["student_id"])
) {

    header("Location: ../pages/login.php");
    exit;
}


// ============================================================
// Start response timer
// ============================================================

$startTime = microtime(true);


// ============================================================
// Get course ID
// ============================================================

$course_id = $_POST["course_id"];


// ============================================================
// Get student ID
// ============================================================

if (isset($_POST["student_id"])) {

    // Load testing
    $student_id = $_POST["student_id"];

} else {

    // Normal browser registration
    $student = $_SESSION["student"];

    $student_id = $student["student_id"];
}


// ============================================================
// Connect database
// ============================================================

$conn = getConnection();


// ============================================================
// Default result
// ============================================================

$success = false;
$message = "";


// ============================================================
// START TRANSACTION
// ============================================================

$conn->begin_transaction();


try {

    // ========================================================
    // 1. LOCK THE COURSE FIRST
    // ========================================================
    //
    // This is the most important part.
    //
    // Requests for the same course must wait for each other.
    //
    // ========================================================

    $course =
        getCourseAvailabilityForUpdate(
            $conn,
            $course_id
        );


    // ========================================================
    // 2. Check whether course exists
    // ========================================================

    if (!$course) {

        $message =
            "Course does not exist.";

        $conn->rollback();


    } else {


        // ====================================================
        // 3. Check duplicate registration
        // ====================================================

        if (
            isAlreadyRegistered(
                $conn,
                $student_id,
                $course_id
            )
        ) {

            $message =
                "You already registered for this course.";

            $conn->rollback();


        } else {


            // =================================================
            // 4. Check course capacity
            // =================================================

            if (
                $course["registered"]
                >=
                $course["capacity"]
            ) {

                $message =
                    "Sorry, this course is full.";

                $conn->rollback();


            } else {


                // =============================================
                // 5. Register student
                // =============================================

                if (
                    registerStudent(
                        $conn,
                        $student_id,
                        $course_id
                    )
                ) {

                    // =========================================
                    // 6. COMMIT
                    // =========================================

                    $conn->commit();

                    $success = true;

                    $message =
                        "Registration successful.";

                } else {

                    $message =
                        "Registration failed.";

                    $conn->rollback();
                }
            }
        }
    }


} catch (Throwable $e) {

    // ========================================================
    // Something went wrong
    // ========================================================

    $conn->rollback();

    $success = false;

    $message =
        "Registration failed.";
}


// ============================================================
// Calculate response time
// ============================================================

$endTime = microtime(true);

$responseTime =
    ($endTime - $startTime) * 1000;


// ============================================================
// AUTOMATED LOAD TEST RESPONSE
// ============================================================

if (
    isset($_POST["test_mode"])
    &&
    $_POST["test_mode"] == "1"
) {

    if ($success) {

        echo "SUCCESS";

    } else {

        echo "FAILED";
    }


// ============================================================
// NORMAL BROWSER RESPONSE
// ============================================================

} else {

    echo $message;
}


// ============================================================
// Close connection
// ============================================================

$conn->close();

?>