<?php

session_start();

require_once "../config/shard_database.php";
require_once "../models/Registration.php";


// ============================================================
// Check login
// ============================================================

if (!isset($_SESSION["student"])) {

    header("Location: login.php");
    exit;
}


// ============================================================
// Get student information
// ============================================================

$student_id =
    $_SESSION["student"]["student_id"];

$student_name =
    $_SESSION["student"]["name"];


// ============================================================
// Connect to student's shard
// ============================================================

$conn =
    getShardConnection($student_id);


if ($conn === null) {

    echo "Database connection failed.";
    exit;
}


// ============================================================
// Get student's registrations
// ============================================================

$registrations =
    getStudentRegistrations(
        $conn,
        $student_id
    );

?>

<!DOCTYPE html>
<html>

<head>

    <title>My Registrations</title>

</head>

<body>

<h2>My Registrations</h2>


<p>

    <b>Student ID:</b>

    <?php
    echo htmlspecialchars($student_id);
    ?>

    <br>

    <b>Name:</b>

    <?php
    echo htmlspecialchars($student_name);
    ?>

</p>


<?php if ($registrations->num_rows == 0): ?>

    <p>
        You have not registered for any courses.
    </p>

<?php else: ?>

    <table
        border="1"
        cellpadding="5"
        style="border-collapse: collapse;"
    >

        <tr>

            <th>Course ID</th>

            <th>Course Name</th>

            <th>Registered At</th>

        </tr>


        <?php while (
            $row =
            $registrations->fetch_assoc()
        ): ?>

            <tr>

                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["course_id"]
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["name"]
                    );
                    ?>

                </td>


                <td>

                    <?php
                    echo htmlspecialchars(
                        $row["registered_at"]
                    );
                    ?>

                </td>

            </tr>

        <?php endwhile; ?>

    </table>

<?php endif; ?>


<br>

<a href="registration.php">
    Back to Course Registration
</a>


</body>

</html>

<?php

$conn->close();

?>