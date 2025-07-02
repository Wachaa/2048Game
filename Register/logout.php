<?php
// Start the session
session_start();

// Destroy the session
session_unset();
session_destroy();

// Redirect to the login page after logout
header("Location: ../index.html");
exit();
?>
