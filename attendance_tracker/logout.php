<?php
// logout.php
require_once 'db_connect.php';

// Log the logout action before destroying the session
if (isset($_SESSION['name'])) {
    log_action('LOGOUT', "User '{$_SESSION['name']}' logged out.");
}

// Unset all of the session variables
$_SESSION = array();
 
// Destroy the session.
session_destroy();
 
// Redirect to login page
header("location: login.php");
exit;
?>

