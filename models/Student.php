<?php

function findStudent($conn, $student_id)
{
    $sql = "
        SELECT student_id, name, gender, program
        FROM tbl_students
        WHERE student_id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $student_id);

    $stmt->execute();

    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function listStudents($conn) {
    $sql = "
        SELECT student_id, name, gender, program 
        FROM tbl_students 
        ORDER BY student_id
    ";

    return $conn->query($sql);
}