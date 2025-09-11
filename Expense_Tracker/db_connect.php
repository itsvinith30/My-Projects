<?php
// --- Database Configuration ---
$servername = "localhost"; // Your server name, usually "localhost"
$username = "root";       // Your database username, often "root"
$password = "YOUR_DATABASE_PASSWORD_HERE";   // Your database password (update if you have one)
$dbname = "expense_tracker"; // The database name you created with setup.sql

// --- Create Connection ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    die();
}
?>