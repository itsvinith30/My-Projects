<?php
// This file handles the logic for changing a counselor's password.

require_once '../config.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'counselor') {
    redirect('../index.php?page=login&error=unauthorized');
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('../index.php?page=dashboard');
}

$counselor_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Basic validation
if (empty($current_password) || empty($new_password)) {
    redirect('../index.php?page=dashboard&error=emptyfields');
}

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Verify Current Password ---
$sql = "SELECT password_hash FROM users WHERE user_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && password_verify($current_password, $user['password_hash'])) {
    // Current password is correct, proceed to update
    
    // Hash the new password for security
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the password in the database
    $sql_update = "UPDATE users SET password_hash = ? WHERE user_id = ?";
    $stmt_update = $db->prepare($sql_update);
    $stmt_update->bind_param("si", $new_password_hash, $counselor_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    $db->close();
    
    // --- Redirect with Success Message ---
    redirect('../index.php?page=dashboard&status=passwordsuccess');

} else {
    // Incorrect current password
    $db->close();
    redirect('../index.php?page=dashboard&error=wrongpwd');
}

?>

