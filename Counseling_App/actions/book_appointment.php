<?php
// This file handles the logic when a patient submits the booking form with a selected time slot.

require_once '../config.php';

// --- PHPMailer Inclusion (Manual) ---
// Make sure you have the PHPMailer folder in your project's root directory
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    redirect('../index.php?page=login&error=unauthorized');
}

// Check if the form was submitted correctly.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('../index.php?page=dashboard');
}

// --- Get Form Data ---
$patient_id = $_SESSION['user_id'];
$counselor_id = $_POST['counselor_id'];
$scheduled_datetime = $_POST['scheduled_datetime']; // This now includes the selected date and time
$patient_name = trim($_POST['patient_name']);
$patient_dob = trim($_POST['patient_dob']);
$reason = trim($_POST['reason']);

// Basic validation
if (empty($counselor_id) || empty($scheduled_datetime) || empty($patient_name) || empty($patient_dob) || empty($reason)) {
    redirect('../index.php?page=dashboard&error=missingfields');
}

// --- Package Application Data into JSON ---
$application_data = [
    'patient_name' => $patient_name,
    'patient_dob' => $patient_dob,
    'reason' => $reason
];
$application_form_data_json = json_encode($application_data);

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Insert New Appointment into Database ---
// The `scheduled_datetime` is now saved directly with the pending request.
$sql = "INSERT INTO appointments (patient_id, counselor_id, status, scheduled_datetime, application_form_data) VALUES (?, ?, 'pending', ?, ?)";
$stmt = $db->prepare($sql);
$stmt->bind_param("iiss", $patient_id, $counselor_id, $scheduled_datetime, $application_form_data_json);

if ($stmt->execute()) {
    // --- Fetch Counselor's Email for Notification ---
    $sql_counselor = "SELECT email, full_name FROM users WHERE user_id = ?";
    $stmt_counselor = $db->prepare($sql_counselor);
    $stmt_counselor->bind_param("i", $counselor_id);
    $stmt_counselor->execute();
    $result = $stmt_counselor->get_result();
    
    if ($result->num_rows === 1) {
        $counselor = $result->fetch_assoc();
        $counselor_email = $counselor['email'];
        $counselor_name = $counselor['full_name'];

        // Format the date for the email
        $requested_time_formatted = (new DateTime($scheduled_datetime))->format('l, F j, Y \a\t g:i A');

        // --- Send Notification Email to Counselor ---
        $mail = new PHPMailer(true);

        try {
            //Server settings from config.php
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            //Recipients
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($counselor_email, $counselor_name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'New Appointment Request Received';
            $mail->Body    = "
                <p>Dear {$counselor_name},</p>
                <p>You have received a new appointment request from <strong>{$patient_name}</strong> for the following time slot:</p>
                <p><strong>{$requested_time_formatted}</strong></p>
                <p>Please log in to your dashboard to review the application details and to approve or reject this request.</p>
                <p><a href='" . SITE_URL . "/index.php?page=login'>Click here to login</a></p>
                <p>Sincerely,<br>The Serenity Harbor Team</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
    $stmt_counselor->close();
}

// --- Close connections and Redirect ---
$stmt->close();
$db->close();
redirect('../index.php?page=dashboard&status=booked');
?>

