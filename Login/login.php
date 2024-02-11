<?php
session_start();
require_once "db.php";

$response = array("success" => false, "message" => "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

    // Basic form validation
    if (empty($username) || empty($password)) {
        $response["message"] = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id, $username, $hashed_password);

        if ($stmt->fetch() && password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $username;
            $response["success"] = true;
            $response["message"] = "Login successful";
        } else {
            $response["message"] = "Invalid username or password.";
        }

        $stmt->close();
    }
}

$conn->close();

header("Content-type: application/json");
echo json_encode($response);
?>
