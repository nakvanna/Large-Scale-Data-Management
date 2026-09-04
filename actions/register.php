<?php

session_start();

require_once "../config/shard_database.php";
require_once "../config/database.php";
require_once "../models/Registration.php";

// ============================================================
// Check login
// ============================================================

if (
    !isset($_SESSION["student"])
    && !isset($_POST["student_id"])
) {
    header("Location: ../pages/login.php");
    exit;
}

// ============================================================
// Get student ID
// ============================================================

if (isset($_POST["student_id"])) {

    $student_id = $_POST["student_id"];

} else {

    $student_id =
        $_SESSION["student"]["student_id"];
}

// ============================================================
// Get course ID
// ============================================================

if (!isset($_POST["course_id"])) {

    echo "FAILED";
    exit;
}

$course_id = $_POST["course_id"];

// ============================================================
// Determine student's database shard
//
// S001-S250 -> DB1 :3306
// S251-S500 -> DB2 :3307
// ============================================================

$shardConn =
    getShardConnection($student_id);

if ($shardConn === null) {

    echo "FAILED";
    exit;
}

// ============================================================
// GLOBAL DATABASE
//
// DB1 :3306 is the global capacity authority.
// ============================================================

$globalConn = new mysqli(
    "127.0.0.1",
    "root",
    "",
    "university_db",
    3306
);

if ($globalConn->connect_error) {

    echo "FAILED";

    $shardConn->close();

    exit;
}

// ============================================================
// Determine whether student's shard is DB1
// ============================================================

$studentNumber = (int) substr($student_id, 1);

$isDB1 = ($studentNumber <= 250);

// ============================================================
// If student belongs to DB1,
// use the same connection.
//
// This avoids two connections fighting over the
// same DB1 transaction/locks.
// ============================================================

if ($isDB1) {

    $registrationConn = $globalConn;

} else {

    $registrationConn = $shardConn;
}

// ============================================================
// Start GLOBAL transaction on DB1
// ============================================================

$globalConn->begin_transaction();

$success = false;
$message = "";

// ============================================================
// Lock the GLOBAL course lock row
//
// This is the critical FOR UPDATE.
//
// Every request for C001 must wait here if another
// request is already processing C001.
// ============================================================

$course =
    lockCourse(
        $globalConn,
        $course_id
    );

if (!$course) {

    $message =
        "Course does not exist.";

    $globalConn->rollback();

} else {

    // ========================================================
    // Check duplicate registration
    //
    // Do this after acquiring the global lock.
    // ========================================================

    if (
        isAlreadyRegistered(
            $registrationConn,
            $student_id,
            $course_id
        )
    ) {

        $message =
            "You already registered for this course.";

        $globalConn->rollback();

    } else {

        // ====================================================
        // Count registrations on DB1
        // ====================================================

        $registeredDB1 =
            countCourseRegistrations(
                $globalConn,
                $course_id
            );

        // ====================================================
        // Count registrations on DB2
        // ====================================================

        if ($isDB1) {

            // Student is on DB1.
            // Need another connection to read DB2.

            $db2Conn = new mysqli(
                "127.0.0.1",
                "root",
                "",
                "university_db",
                3307
            );

            if ($db2Conn->connect_error) {

                $message =
                    "Database Node 2 connection failed.";

                $globalConn->rollback();

            } else {

                $registeredDB2 =
                    countCourseRegistrations(
                        $db2Conn,
                        $course_id
                    );
            }

        } else {

            // Student is already on DB2.
            // Reuse the student's DB2 connection.

            $db2Conn = $shardConn;

            $registeredDB2 =
                countCourseRegistrations(
                    $db2Conn,
                    $course_id
                );
        }

        // ====================================================
        // Continue only if DB2 count was successfully obtained
        // ====================================================

        if (
            isset($registeredDB2)
            && $globalConn->errno === 0
        ) {

            // =================================================
            // Calculate GLOBAL registration count
            // =================================================

            $totalRegistered =
                $registeredDB1
                +
                $registeredDB2;

            // =================================================
            // Check GLOBAL capacity
            // =================================================

            if (
                $totalRegistered
                >=
                (int) $course["capacity"]
            ) {

                $message =
                    "Sorry, this course is full.";

                $globalConn->rollback();

            } else {

                // =============================================
                // Insert into student's own shard
                // =============================================

                if (
                    registerStudent(
                        $registrationConn,
                        $student_id,
                        $course_id
                    )
                ) {

                    // =========================================
                    // Registration successful
                    // =========================================

                    $globalConn->commit();

                    $success = true;

                    $message =
                        "Registration successful.";

                } else {

                    $message =
                        "Registration failed.";

                    $globalConn->rollback();
                }
            }
        }
    }

    // ========================================================
    // Close separate DB2 connection
    // ========================================================

    if (
        $isDB1
        && isset($db2Conn)
        && $db2Conn !== $shardConn
    ) {

        $db2Conn->close();
    }
}

// ============================================================
// Response
//
// baseline_test.php sends test_mode=1.
// Manual registration does not.
// ============================================================

$test_mode =
    isset($_POST["test_mode"])
    ? (int) $_POST["test_mode"]
    : 0;

if ($test_mode === 1) {

    if ($success) {

        echo "SUCCESS";

    } else {

        echo "FAILED";
    }

} else {

    echo $message;
}

// ============================================================
// Close connections
// ============================================================

if ($registrationConn !== $globalConn) {

    $shardConn->close();
}

$globalConn->close();

?>