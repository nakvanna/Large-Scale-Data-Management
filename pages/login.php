<!DOCTYPE html>
<html>

<head>
    <title>Student Login</title>
</head>

<body>

<h2>Student Login</h2>

<form method="POST" action="../actions/login.php">

    <label>Student ID:</label>

    <input
        type="text"
        name="student_id"
        placeholder="S001"
        required
    >

    <button type="submit">
        Login
    </button>

</form>

</body>

</html>