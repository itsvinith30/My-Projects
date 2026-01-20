<?php
// This file handles the user login process.

require_once '../config.php';

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Form Data Validation ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize and retrieve form inputs
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        redirect('../index.php?page=login&error=emptyfields');
    }

    // --- Find User and Verify Password ---
    // 1. Prepare SQL to find user by email
    $sql = "SELECT user_id, full_name, email, password_hash, user_role FROM users WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // User found, now verify the password
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            // Password is correct! Start a session.
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect to the dashboard
            $stmt->close();
            $db->close();
            redirect('../index.php?page=dashboard');
            
        } else {
            // Incorrect password
            $stmt->close();
            $db->close();
            redirect('../index.php?page=login&error=wrongpwd');
        }

    } else {
        // No user found with that email
        $stmt->close();
        $db->close();
        redirect('../index.php?page=login&error=nouser');
    }

} else {
    redirect('../index.php');
}
?>
