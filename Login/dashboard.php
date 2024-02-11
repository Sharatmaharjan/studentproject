<?php
// Assume session_start() is called at the beginning of your login.php file
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html"); // Redirect to login page if not logged in
    exit();
}

// Fetch user details from the session or the database
$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Your custom stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Welcome, <?php echo $username; ?>!</h1>
                <p class="card-text">Your User ID: <?php echo $user_id; ?></p>

                <!-- Menu with CRUD operations on students -->
                <div class="list-group mt-4">
                    <a href="../ManagementSystem/create_student.php" class="list-group-item list-group-item-action">Create Student</a>
                    <a href="../ManagementSystem/read_students.php" class="list-group-item list-group-item-action">Read Students</a>
                    <a href="../ManagementSystem/update_student.php" class="list-group-item list-group-item-action">Update Student</a>
                    <a href="../ManagementSystem/delete_student.php" class="list-group-item list-group-item-action">Delete Student</a>
                </div>

                <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
