<?php
// This file handles the new user registration process.

require_once '../config.php';

// --- Database Connection ---
// Create a new database connection object.
// The @ suppresses warnings, but for debugging you might want to remove it.
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check for connection errors
if ($db->connect_error) {
    // In a real app, you would log this error and show a user-friendly message.
    die("Database connection failed: " . $db->connect_error);
}

// --- Form Data Validation ---
// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize and retrieve form inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // We won't trim the password
    $user_role = $_POST['user_role'];
    
    // 2. Basic Validation (you can add more complex rules)
    if (empty($full_name) || empty($email) || empty($password) || empty($user_role)) {
        // Redirect back with an error message
        redirect('../index.php?page=register&error=emptyfields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Redirect back with an error message
        redirect('../index.php?page=register&error=invalidemail');
    }

    // --- Check if Email Already Exists ---
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Email already exists
        $stmt->close();
        $db->close();
        redirect('../index.php?page=register&error=emailtaken');
    }
    
    $stmt->close();

    // --- Insert New User into Database ---
    // 1. Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // 2. Prepare the SQL INSERT statement
    $sql = "INSERT INTO users (full_name, email, password_hash, user_role) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssss", $full_name, $email, $password_hash, $user_role);

    // 3. Execute the statement
    if ($stmt->execute()) {
        // Registration successful
        $user_id = $stmt->insert_id; // Get the ID of the new user

        // If the new user is a counselor, create a default profile for them
        if ($user_role === 'counselor') {
            $profile_sql = "INSERT INTO counselor_profiles (user_id, bio) VALUES (?, 'Welcome to my profile!')";
            $profile_stmt = $db->prepare($profile_sql);
            $profile_stmt->bind_param("i", $user_id);
            $profile_stmt->execute();
            $profile_stmt->close();
        }

        // --- Automatically Log In the New User ---
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $user_role;
        $_SESSION['full_name'] = $full_name;

        // Redirect to the dashboard
        $stmt->close();
        $db->close();
        redirect('../index.php?page=dashboard');

    } else {
        // Registration failed
        $stmt->close();
        $db->close();
        redirect('../index.php?page=register&error=sqlerror');
    }
    
} else {
    // If someone tries to access this file directly, redirect them home.
    redirect('../index.php');
}
?>
