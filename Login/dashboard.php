<?php
session_start(); // Start session

// 1. Protect page: only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}
?>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>Your role: <?php echo $_SESSION['role']; ?></p>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <p>You have admin privileges.</p>
<?php else: ?>
    <p>You are a normal user.</p>
<?php endif; ?>

<a href="logout.php">Logout</a>