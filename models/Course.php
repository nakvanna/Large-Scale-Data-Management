<?php

function getAvailableCourses($conn)
{
    // Get courses from DB Node 1
    $sql = "
        SELECT
            course_id,
            name,
            capacity
        FROM tbl_courses
        ORDER BY course_id
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return false;
    }

    // Connect to DB Node 2
    $db2 = new mysqli(
        "127.0.0.1",
        "root",
        "",
        "university_db",
        3307
    );

    if ($db2->connect_error) {
        return false;
    }


    // Store DB2 registration counts
    $db2Counts = [];

    $sql2 = "
        SELECT
            course_id,
            COUNT(*) AS registered
        FROM tbl_registrations
        GROUP BY course_id
    ";

    $result2 = $db2->query($sql2);

    while ($row = $result2->fetch_assoc()) {

        $db2Counts[$row["course_id"]] =
            (int) $row["registered"];
    }


    // Add DB2 registrations to each course
    $courses = [];

    while ($course = $result->fetch_assoc()) {

        $course_id = $course["course_id"];


        // Count registrations on DB1
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS registered
            FROM tbl_registrations
            WHERE course_id = ?
        ");

        $stmt->bind_param(
            "s",
            $course_id
        );

        $stmt->execute();

        $countResult = $stmt->get_result();

        $row = $countResult->fetch_assoc();

        $db1Registered =
            (int) $row["registered"];

        $stmt->close();


        // Count registrations on DB2
        $db2Registered =
            $db2Counts[$course_id] ?? 0;


        // Global registration count
        $course["registered"] =
            $db1Registered
            +
            $db2Registered;


        $courses[] = $course;
    }

    $db2->close();

    // Return array instead of mysqli_result
    return $courses;
}


function listCourses($conn)
{
    $sql = "
        SELECT
            course_id,
            name,
            capacity
        FROM tbl_courses
        ORDER BY course_id
    ";

    return $conn->query($sql);
}