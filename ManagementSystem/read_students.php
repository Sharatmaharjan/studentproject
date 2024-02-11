<?php
// Assume session_start() is called at the beginning of your read_students.php file
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html"); // Redirect to login page if not logged in
    exit();
}

// Assuming you have a database connection
// Include your database connection code here (similar to db.php)
require_once "../Login/db.php";

// Retrieve all students from the database
$getAllStmt = $conn->prepare("SELECT id, name, age, gender FROM students");
$getAllStmt->execute();
$getAllResult = $getAllStmt->get_result();
$getAllStmt->close();

// Retrieve a student by ID if provided
$studentDetails = null;
$message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentId = $_POST["id"];
    $getByIdStmt = $conn->prepare("SELECT id, name, age, gender FROM students WHERE id = ?");
    $getByIdStmt->bind_param("i", $studentId);
    $getByIdStmt->execute();
    $getByIdResult = $getByIdStmt->get_result();
    $studentDetails = $getByIdResult->fetch_assoc();
    $getByIdStmt->close();

    // Check if a student was found
    if (!$studentDetails) {
        $message = "No student found with ID: $studentId";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Students</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Your custom stylesheet -->
    <link rel="stylesheet" href="Login/css/styles.css">
</head>
<body>
    <div class="container mt-5">
        <!-- Add a "Back to Dashboard" button -->
        <a href="../Login/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
        <h2 class="mb-3">All Students</h2>

        <!-- Search form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-3">
            <div class="form-group">
                <label for="student_id">Search by Student ID:</label>
                <input type="number" class="form-control" id="student_id" name="id" pattern="\d+" title="Please enter a valid number" required>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Display message if no student found -->
        <?php if ($message) : ?>
            <div class="alert alert-warning" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Display all students -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $getAllResult->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row["id"]; ?></td>
                        <td><?php echo $row["name"]; ?></td>
                        <td><?php echo $row["age"]; ?></td>
                        <td><?php echo $row["gender"]; ?></td>
                        <td>
                            <a href="read_students.php?id=<?php echo $row["id"]; ?>" class="btn btn-info btn-sm">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Display student details by ID if provided -->
        <?php if ($studentDetails) : ?>
            <div class="mt-3">
                <h2>Student Details</h2>
                <p>ID: <?php echo $studentDetails["id"]; ?></p>
                <p>Name: <?php echo $studentDetails["name"]; ?></p>
                <p>Age: <?php echo $studentDetails["age"]; ?></p>
                <p>Gender: <?php echo $studentDetails["gender"]; ?></p>
                <a href="read_students.php" class="btn btn-primary">Back to All Students</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
