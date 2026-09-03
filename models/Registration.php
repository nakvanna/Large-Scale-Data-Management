<?php


// ============================================================
// Check whether a student is already registered
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

    $exists = ($result->num_rows > 0);

    $stmt->close();

    return $exists;
}


// ============================================================
// Get course availability
// Normal version - no lock
// ============================================================

function getCourseAvailability(
    $conn,
    $course_id
) {

    $sql = "
        SELECT
            c.course_id,
            c.name,
            c.capacity,
            COUNT(r.id) AS registered
        FROM tbl_courses c
        LEFT JOIN tbl_registrations r
            ON c.course_id = r.course_id
        WHERE c.course_id = ?
        GROUP BY
            c.course_id,
            c.name,
            c.capacity
    ";

    $stmt = $conn->prepare($sql);

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
// Get course availability WITH row lock
// ============================================================

function getCourseAvailabilityForUpdate(
    $conn,
    $course_id
) {

    // --------------------------------------------------------
    // Lock the course row
    // --------------------------------------------------------

    $sql = "
        SELECT
            course_id,
            name,
            capacity
        FROM tbl_courses
        WHERE course_id = ?
        FOR UPDATE
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "s",
        $course_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $course = $result->fetch_assoc();

    $stmt->close();


    // Course doesn't exist
    if (!$course) {
        return null;
    }


    // --------------------------------------------------------
    // Count current registrations
    // The course row is still locked here.
    // --------------------------------------------------------

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


    $course["registered"] =
        (int) $row["registered"];


    return $course;
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

?>