<?php
session_start();

// Store the user role before clearing session
$userRole = $_SESSION['user_role'] ?? '';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Role-based redirect
if ($userRole === 'admin') {
    header("Location: http://localhost/IMS/admin_login.php");
} else if ($userRole === 'employee') {
    header("Location: http://localhost/IMS/user_login.php");
} else {
    header("Location: http://localhost/IMS/user_login.php");
}

exit();
?>