<?php
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the login session
session_destroy();

// Return to login page
header("Location: login.php");
exit();
?>