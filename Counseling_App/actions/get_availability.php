<?php
// This "API" endpoint calculates and returns a counselor's available time slots for a given date.
// It now also checks for existing appointments to prevent double-booking.

require_once '../config.php';

// --- Input Validation ---
// Ensure required parameters are present.
if (!isset($_GET['counselor_id']) || !isset($_GET['date'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters.']);
    exit();
}

$counselor_id = (int)$_GET['counselor_id'];
$selected_date_str = $_GET['date'];

// Validate the date format (YYYY-MM-DD)
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $selected_date_str)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid date format.']);
    exit();
}

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// --- Logic ---
try {
    $selected_date_obj = new DateTime($selected_date_str);
    $day_of_week = $selected_date_obj->format('l'); // e.g., 'Monday'

    // 1. Get the counselor's general availability for that day of the week.
    $sql_avail = "SELECT start_time, end_time FROM counselor_availability WHERE counselor_id = ? AND day_of_week = ?";
    $stmt_avail = $db->prepare($sql_avail);
    $stmt_avail->bind_param("is", $counselor_id, $day_of_week);
    $stmt_avail->execute();
    $result_avail = $stmt_avail->get_result();
    
    $available_slots = [];

    if ($result_avail->num_rows === 1) {
        $row = $result_avail->fetch_assoc();
        $start_time = new DateTime($row['start_time']);
        $end_time = new DateTime($row['end_time']);
        
        // Assuming 1-hour slots. You could change 'PT1H' to 'PT30M' for 30-minute slots.
        $interval = new DateInterval('PT1H');
        $period = new DatePeriod($start_time, $interval, $end_time);

        foreach ($period as $time) {
            $available_slots[] = $time->format('H:i');
        }
    }
    
    $stmt_avail->close();

    // 2. Get all *booked* appointments for that counselor on that specific date.
    // This includes pending and approved appointments, as they reserve a slot.
    $sql_booked = "SELECT scheduled_datetime FROM appointments 
                   WHERE counselor_id = ? 
                   AND DATE(scheduled_datetime) = ?
                   AND (status = 'pending' OR status = 'approved')";
    $stmt_booked = $db->prepare($sql_booked);
    $stmt_booked->bind_param("is", $counselor_id, $selected_date_str);
    $stmt_booked->execute();
    $result_booked = $stmt_booked->get_result();

    $booked_slots = [];
    if ($result_booked->num_rows > 0) {
        while($row = $result_booked->fetch_assoc()) {
            $booked_time = new DateTime($row['scheduled_datetime']);
            $booked_slots[] = $booked_time->format('H:i');
        }
    }
    $stmt_booked->close();

    // 3. Remove the booked slots from the available slots.
    // The array_diff function is perfect for this.
    $final_slots = array_diff($available_slots, $booked_slots);

    // --- Output the final list of available slots as JSON ---
    header('Content-Type: application/json');
    echo json_encode(array_values($final_slots)); // array_values re-indexes the array

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'An error occurred processing the date.']);
} finally {
    $db->close();
}
?>

