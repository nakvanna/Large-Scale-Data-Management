<?php

require_once "../config/database.php";
require_once "../models/Course.php";

$conn = getConnection();

$courses = listCourses($conn);

$count = $courses->num_rows;
echo "<h1>$count Courses</h1>";

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Capacity</th><th>Seat Avaliable</th></tr>";
while ($row = $courses->fetch_assoc()) {
    echo "<tr><td>{$row["course_id"]}</td><td>{$row["name"]}</td><td>{$row["capacity"]}</td><td>0</td></tr>";
}
echo "</table>"; 
?>