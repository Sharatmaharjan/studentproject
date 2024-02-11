<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Basic input validation
    if (empty($username) || empty($password)) {
        echo "Please fill in all fields.";
    } else {
        // Check if the username already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            echo "Username already exists. Please choose a different username.";
        } else {
            // Username is unique, proceed with registration
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $username, $hashed_password);

            if ($insertStmt->execute()) {
                echo "Registration successful!";
                header("Location: login.html"); // Redirect to login page
                exit();
            } else {
                echo "Error during registration: " . $insertStmt->error;
            }

            $insertStmt->close();
        }

        $checkStmt->close();
    }
}

$conn->close();
?>
