<?php
// config.example.php
// Rename this to config.php and set up environment variables if needed

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// These environment variables should be set via Docker or manually
$servername = getenv('DB_HOST');        // e.g., 'db' if using Docker
$dbUsername = getenv('DB_USER');        // e.g., 'root'
$dbPassword = getenv('DB_PASSWORD');    // e.g., 'your_password'
$dbName     = getenv('DB_NAME');        // e.g., 'game2048'

// If any variable is missing, stop execution with a clear message
if (!$servername || !$dbUsername || !$dbPassword || !$dbName) {
    die("Missing database environment variables. Please check your .env or server configuration.");
}

// Connect to the MySQL database
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Error handling if DB connection fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
