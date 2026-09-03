<?php

session_start();

require_once "../config/database.php";
require_once "../models/Course.php";

if (!isset($_SESSION["student"])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION["student"]["student_id"];
$student_name = $_SESSION["student"]["name"];
$student_gender = $_SESSION["student"]["gender"];

$conn = getConnection();

$courses = getAvailableCourses($conn);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Course Registration</title>
</head>

<body>

<h2>Course Registration</h2>

<p>
    <b>Your ID:</b>
    <?php echo htmlspecialchars($student_id); ?>
    <b>Name:</b>
    <?php echo htmlspecialchars($student_name); ?>
    <b>Gender:</b>
    <?php echo htmlspecialchars($student_gender); ?>
</p>

<br>

<a href="my_registrations.php">
    View My Registrations
</a>
<form method="POST" action="../actions/register.php">

    <input
        type="hidden"
        name="student_id"
        value="<?php echo htmlspecialchars($student_id); ?>"
    >

    <h3>Available Courses</h3>

    <?php while ($course = $courses->fetch_assoc()): ?>

        <div>

            <input
                type="radio"
                name="course_id"
                value="<?php echo htmlspecialchars($course["course_id"]); ?>"
                required
            >

            <?php echo htmlspecialchars($course["course_id"]); ?>

            -

            <?php echo htmlspecialchars($course["name"]); ?>

            -

            <?php echo $course["capacity"] - $course["registered"]; ?>
            seats available

        </div>

    <?php endwhile; ?>

    <br>

    <button type="submit">
        Register
    </button>

</form>

</body>

</html>

<?php

$conn->close();

?>