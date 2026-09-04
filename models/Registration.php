<?php

// ============================================================
// Check duplicate registration on the student's shard
// ============================================================

function isAlreadyRegistered(
    $conn,
    $student_id,
    $course_id
) {

    $sql = "
        SELECT id
        FROM tbl_registrations
        WHERE student_id = ?
        AND course_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ss",
        $student_id,
        $course_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $exists = $result->num_rows > 0;

    $stmt->close();

    return $exists;
}

// ============================================================
// Lock GLOBAL course
//
// DB Node 1 :3306 is the global capacity authority.
//
// IMPORTANT:
// This locks tbl_course_locks, NOT tbl_courses.
// Every concurrent request for the same course must
// acquire this same lock before checking capacity.
// ============================================================

function lockCourse(
    $globalConn,
    $course_id
) {

    $sql = "
        SELECT
            l.course_id,
            c.capacity
        FROM tbl_course_locks l
        JOIN tbl_courses c
            ON l.course_id = c.course_id
        WHERE l.course_id = ?
        FOR UPDATE
    ";

    $stmt = $globalConn->prepare($sql);

    $stmt->bind_param(
        "s",
        $course_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $course = $result->fetch_assoc();

    $stmt->close();

    return $course;
}

// ============================================================
// Count registrations on one shard
// ============================================================

function countCourseRegistrations(
    $conn,
    $course_id
) {

    $sql = "
        SELECT COUNT(*) AS registered
        FROM tbl_registrations
        WHERE course_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "s",
        $course_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) $row["registered"];
}


// ============================================================
// Register student
// ============================================================

function registerStudent(
    $conn,
    $student_id,
    $course_id
) {

    $sql = "
        INSERT INTO tbl_registrations
        (
            student_id,
            course_id,
            registered_at
        )
        VALUES (?, ?, NOW())
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ss",
        $student_id,
        $course_id
    );

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}


// ============================================================
// Get student's registrations
// ============================================================

function getStudentRegistrations(
    $conn,
    $student_id
) {

    $sql = "
        SELECT
            r.course_id,
            c.name,
            r.registered_at
        FROM tbl_registrations r
        JOIN tbl_courses c
            ON r.course_id = c.course_id
        WHERE r.student_id = ?
        ORDER BY r.registered_at DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "s",
        $student_id
    );

    $stmt->execute();

    return $stmt->get_result();
}

?>