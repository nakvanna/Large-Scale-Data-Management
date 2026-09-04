<?php

require_once "../config/shard_database.php";

$students = [
    "S001",
    "S100",
    "S250",
    "S251",
    "S400",
    "S500"
];

foreach ($students as $student_id) {

    $conn = getShardConnection($student_id);

    if ($conn === null) {

        echo $student_id . " → ERROR<br>";

        continue;
    }

    $result = $conn->query(
        "SELECT @@port AS server_port"
    );

    $row = $result->fetch_assoc();

    echo $student_id
        . " → DB Node Port: "
        . $row["server_port"]
        . "<br>";

    $conn->close();
}