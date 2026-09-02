<?php

function getAvailableCourses($conn)
{
    $sql = "
        SELECT
            c.course_id,
            c.name,
            c.capacity,
            COUNT(r.id) AS registered
        FROM tbl_courses c
        LEFT JOIN tbl_registrations r
            ON c.course_id = r.course_id
        GROUP BY
            c.course_id,
            c.name,
            c.capacity
        HAVING COUNT(r.id) < c.capacity
        ORDER BY c.course_id
    ";

    return $conn->query($sql);
}

function listCourses($conn) {
    $sql = "
        SELECT course_id, name, capacity 
        FROM tbl_courses 
        ORDER BY course_id
    ";

    return $conn->query($sql);
}