<?php
// If sessions are needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read DB connection info from environment:
$servername = getenv('DB_HOST') ?: 'db';        // 'db' is the mysql service name
$dbUsername = getenv('DB_USER') ?: 'root';      
$dbPassword = getenv('DB_PASSWORD') ?: 'bishant';
$dbName     = getenv('DB_NAME') ?: 'game2048';

// Create connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
