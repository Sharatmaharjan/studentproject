<?php
// db.php
// Set up database connection and create database/tables if not exists

$host = 'localhost';       // Database host
$dbname = 'simple_login';  // Database name
$username = 'root';        // DB username
$password = '';            // DB password

try {
    // 1. Connect to MySQL server (without specifying DB yet)
    $pdo = new PDO("mysql:host=$host", $username, $password);

    // 2. Set PDO error mode to Exception (helps in debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");

    // 4. Select the database
    $pdo->exec("USE $dbname");

    // 5. Create users table if it doesn't exist
    //    Columns:
    //    id: primary key
    //    username: unique
    //    password: hashed password
    //    role: 'admin' or 'user'
    //    created_at: timestamp
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    // If connection fails, stop the script and show error
    die("Database error: " . $e->getMessage());
}
