<?php

function getShardConnection($student_id)
{
    // Remove "S" from student ID
    // Example: S123 → 123
    $student_number = (int) substr($student_id, 1);

    // Determine database node
    if ($student_number >= 1 && $student_number <= 250) {
        // DB Node 1
        $port = 3306;

    } elseif ($student_number >= 251 && $student_number <= 500) {
        // DB Node 2
        $port = 3307;

    } else {
        return null;
    }

    $host = "127.0.0.1";
    $user = "root";
    $password = "";
    $database = "university_db";

    $conn = new mysqli(
        $host,
        $user,
        $password,
        $database,
        $port
    );

    if ($conn->connect_error) {
        return null;
    }

    return $conn;
}