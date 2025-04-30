<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = new Auth();

// Logout admin
$auth->logout();

// Redirect to login page
header("Location: " . BASE_URL . "/admin/login.php");
exit;
?>
