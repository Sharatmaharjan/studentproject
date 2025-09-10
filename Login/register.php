<?php
require_once "db.php"; // Include database connection-> only once so that it wont produce any error in future

// 1. Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]); // Remove extra spaces
    $password = trim($_POST["password"]);

    // 2. Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // 3. Hash password securely before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); //PASSWORD_DEFAULT currently uses bcrypt hashing


        // $sql = "INSERT INTO users (username, password) VALUES ($username, $password)"; -> simple but it's vulnerable, prone to sql attacks (not recommended)
        // try {
        //     $pdo->exec($sql);
        //     $success = "User registered successfully!";
        // } catch (PDOException $e) {
        //     // If username already exists or any other error
        //     $error = "Error: " . $e->getMessage();
        // }

        // 4. Insert user into database using prepared statement to secure from sql injection attacks
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if ($stmt->execute([$username, $hashed_password])) {
            $success = "User registered successfully!";
        } else {
            $error = "Username already exists.";
        }
    }
}
?>

<!-- 5. HTML Form -->
<h2>Register</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username"><br>
    <input type="password" name="password" placeholder="Password"><br>
    <button type="submit">Register</button>
</form>

<!-- 6. Show messages -->
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>