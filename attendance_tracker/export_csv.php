<?php
// export_csv.php
require_once 'db_connect.php';

// Security check: ensure user is logged in
if (!isset($_SESSION["loggedin"]) || !in_array($_SESSION["role"], ['admin', 'teacher'])) {
    // Or redirect to login page
    die("Access Denied");
}

// Get parameters from URL
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if (empty($class_id) || empty($start_date) || empty($end_date)) {
    die("Invalid parameters for export.");
}

// Fetch the data from the database
$sql_export = "
    SELECT s.name as student_name, ar.date, ar.status, ar.remarks
    FROM attendance_records ar
    JOIN students s ON ar.student_id = s.student_id
    WHERE ar.class_id = ? AND ar.date BETWEEN ? AND ?
    ORDER BY ar.date DESC, s.name ASC
";

if ($stmt = $conn->prepare($sql_export)) {
    $stmt->bind_param("iss", $class_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Set headers to force download
    $filename = "attendance_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add CSV header row
    fputcsv($output, ['Student Name', 'Date', 'Status', 'Remarks']);

    // Loop through the data and write to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    $stmt->close();
}
$conn->close();
exit();
?>
