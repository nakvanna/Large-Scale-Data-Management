<?php

function getConnection()
{
    $host = "127.0.0.1";
    $user = "root";
    $password = "";
    $database = "university_db";
    $port = 3306;

    $conn = new mysqli(
        $host,
        $user,
        $password,
        $database,
        $port
    );

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    return $conn;
}