<?php
session_start();           // Start PHP session to store user info
require_once "db.php";     // Include database connection

// 1. Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // 2. Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } else {
        // 3. Fetch user from database by username
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Verify password
        if ($user && password_verify($password, $user['password'])) {
            // 5. Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // 6. Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<h2>Login</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username"><br>
    <input type="password" name="password" placeholder="Password"><br>
    <button type="submit">Login</button>
</form>

<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>