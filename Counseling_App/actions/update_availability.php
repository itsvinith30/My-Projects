<?php
// This file handles updating the counselor's weekly availability.

require_once '../config.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'counselor') {
    redirect('../index.php?page=login&error=unauthorized');
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('../index.php?page=dashboard');
}

// --- Get Form Data ---
$counselor_id = $_SESSION['user_id'];
$selected_days = isset($_POST['days']) ? $_POST['days'] : [];
$start_times = $_POST['start_time'];
$end_times = $_POST['end_time'];

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Database Operations ---
// For simplicity, we'll delete the old schedule and insert the new one.
// This is done inside a transaction to ensure data integrity.

$db->begin_transaction();

try {
    // 1. Delete the counselor's old availability
    $sql_delete = "DELETE FROM counselor_availability WHERE counselor_id = ?";
    $stmt_delete = $db->prepare($sql_delete);
    $stmt_delete->bind_param("i", $counselor_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. Insert the new availability for each selected day
    if (!empty($selected_days)) {
        $sql_insert = "INSERT INTO counselor_availability (counselor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
        $stmt_insert = $db->prepare($sql_insert);

        foreach ($selected_days as $day) {
            // Ensure the start and end times for this day exist in the POST data
            if (isset($start_times[$day]) && isset($end_times[$day])) {
                $start = $start_times[$day];
                $end = $end_times[$day];
                $stmt_insert->bind_param("isss", $counselor_id, $day, $start, $end);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();
    }
    
    // If everything was successful, commit the changes
    $db->commit();

} catch (mysqli_sql_exception $exception) {
    // If anything went wrong, roll back the changes
    $db->rollback();
    // You could redirect with an error message here
    // For now, we'll just stop
    die("Error updating availability: " . $exception->getMessage());
}

// --- Close connection and Redirect ---
$db->close();
redirect('../index.php?page=dashboard&status=availabilityupdated');

?>
