<?php
// Assume session_start() is called at the beginning of your create_student.php file
session_start();


// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html"); // Redirect to login page if not logged in
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];

    // Basic input validation
    if (empty($name) || empty($age) || empty($gender)) {
        $errorMessage = "Please fill in all fields.";
    } else {
        // Perform additional validation if needed

        // Assuming you have a database connection
        // Include your database connection code here (similar to db.php)
        require_once "../Login/db.php";

        // Example: Insert student into the database
        $stmt = $conn->prepare("INSERT INTO students (name, age, gender) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $name, $age, $gender);

        if ($stmt->execute()) {
            $successMessage = "Student added successfully!";
        } else {
            $errorMessage = "Error adding student: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Your custom stylesheet -->
    <link rel="stylesheet" href="Login/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Create Student</h2>

                <!-- Display error message if there's an error -->
                <?php if (isset($errorMessage)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- Display success message if student added successfully -->
                <?php if (isset($successMessage)) : ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- Add a "Back to Dashboard" button -->
                <a href="../Login/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" pattern="[A-Za-z\s]+" title="Enter only letters and spaces" required>
                        <small class="text-muted">Only letters and spaces are allowed.</small>
                    </div>
                    <div class="form-group">
                    <label for="age">Age:</label>
                        <input type="number" class="form-control" id="age" name="age" min="2" max="100" required>
                        <small class="text-muted">Age must be between 2 and 100.</small>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
