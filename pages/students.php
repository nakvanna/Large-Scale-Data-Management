<?php

require_once "../config/database.php";
require_once "../models/Student.php";

$conn = getConnection();

$students = listStudents($conn);

$count = $students->num_rows;
echo "<h1>Students $count</h1>";

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='backgroundcolor:#f2f2f2;'><th>ID</th><th>Name</th><th>Gender</th><th>Program</th></tr>";
while ($row = $students->fetch_assoc()) {
    echo "<tr><td>{$row["student_id"]}</td><td>{$row["name"]}</td><td>{$row["gender"]}</td><td>{$row["program"]}</td></tr>";
}
echo "</table>"; 
?>