<?php

function isAlreadyRegistered($conn, $student_id, $course_id)
{
    $sql = "
        SELECT id
        FROM tbl_registrations
        WHERE student_id = ?
        AND course_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ss", $student_id, $course_id);

    $stmt->execute();

    $result = $stmt->get_result();

    return $result->num_rows > 0;
}


function getCourseAvailability($conn, $course_id)
{
    $sql = "
        SELECT
            c.capacity,
            COUNT(r.id) AS registered
        FROM tbl_courses c
        LEFT JOIN tbl_registrations r
            ON c.course_id = r.course_id
        WHERE c.course_id = ?
        GROUP BY c.course_id, c.capacity
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $course_id);

    $stmt->execute();

    $result = $stmt->get_result();

    return $result->fetch_assoc();
}


function registerStudent($conn, $student_id, $course_id)
{
    $sql = "
        INSERT INTO tbl_registrations
        (student_id, course_id, registered_at)
        VALUES (?, ?, NOW())
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ss", $student_id, $course_id);

    return $stmt->execute();
}