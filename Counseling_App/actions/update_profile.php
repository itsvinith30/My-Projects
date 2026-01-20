<?php
// This file handles updating the counselor's profile information.

require_once '../config.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'counselor') {
    redirect('../index.php?page=login&error=unauthorized');
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('../index.php?page=dashboard');
}

$counselor_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name']);
$specialization = trim($_POST['specialization']);

// Basic validation
if (empty($full_name) || empty($specialization)) {
    redirect('../index.php?page=dashboard&error=emptyfields');
}

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Handle Profile Photo Upload ---
$new_photo_filename = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $upload_dir = '../uploads/';
    // Create a unique filename to prevent overwriting files
    $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $new_photo_filename = 'profile_' . $counselor_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $new_photo_filename;

    // TODO: Add more robust validation (file size, type, etc.)
    move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file);
}

// --- Update Database ---
// Update `users` table for the name
$sql_user = "UPDATE users SET full_name = ? WHERE user_id = ?";
$stmt_user = $db->prepare($sql_user);
$stmt_user->bind_param("si", $full_name, $counselor_id);
$stmt_user->execute();
$stmt_user->close();

// Update `counselor_profiles` table for specialization and photo
if ($new_photo_filename) {
    // If a new photo was uploaded, update the photo URL
    $sql_profile = "UPDATE counselor_profiles SET specialization = ?, profile_image_url = ? WHERE user_id = ?";
    $stmt_profile = $db->prepare($sql_profile);
    $stmt_profile->bind_param("ssi", $specialization, $new_photo_filename, $counselor_id);
} else {
    // If no new photo, only update the specialization
    $sql_profile = "UPDATE counselor_profiles SET specialization = ? WHERE user_id = ?";
    $stmt_profile = $db->prepare($sql_profile);
    $stmt_profile->bind_param("si", $specialization, $counselor_id);
}
$stmt_profile->execute();
$stmt_profile->close();

$db->close();

// Redirect back to the dashboard with a success message
redirect('../index.php?page=dashboard&status=profilesuccess');

?>

