<?php
// This file handles the logic when a counselor approves a request.

require_once '../config.php';

// --- PHPMailer Inclusion (Manual) ---
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'counselor') {
    redirect('../index.php?page=login&error=unauthorized');
}

// Check if the form was submitted correctly.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect('../index.php?page=dashboard');
}

// --- Get Form Data ---
$appointment_id = $_POST['appointment_id'];
$meeting_details = trim($_POST['meeting_details']);
$counselor_id = $_SESSION['user_id'];

// Basic validation
if (empty($appointment_id) || empty($meeting_details)) {
    redirect('../index.php?page=dashboard&error=missingdetails');
}

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// --- Update the Appointment in the Database ---
// We only update status and meeting_details. The time was already set by the patient.
$sql = "UPDATE appointments SET status = 'approved', meeting_details = ? WHERE appointment_id = ? AND counselor_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("sii", $meeting_details, $appointment_id, $counselor_id);

if ($stmt->execute()) {
    // --- Fetch Patient & Appointment Details for Notification ---
    $sql_details = "SELECT u.email, u.full_name, a.scheduled_datetime
                    FROM users u
                    JOIN appointments a ON u.user_id = a.patient_id
                    WHERE a.appointment_id = ?";
    $stmt_details = $db->prepare($sql_details);
    $stmt_details->bind_param("i", $appointment_id);
    $stmt_details->execute();
    $result = $stmt_details->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $patient_email = $data['email'];
        $patient_name = $data['full_name'];
        $scheduled_datetime = $data['scheduled_datetime'];

        // Format the date for the email
        $confirmed_time_formatted = (new DateTime($scheduled_datetime))->format('l, F j, Y \a\t g:i A');

        // --- Send Confirmation Email to Patient ---
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
            $mail->addAddress($patient_email, $patient_name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Counseling Appointment is Confirmed!';
            $mail->Body    = "
                <p>Dear {$patient_name},</p>
                <p>Great news! Your request for an appointment has been approved by the counselor.</p>
                <p><strong>Appointment Details:</strong></p>
                <ul>
                    <li><strong>Time:</strong> {$confirmed_time_formatted}</li>
                    <li><strong>Meeting Instructions:</strong><br><pre>" . htmlspecialchars($meeting_details) . "</pre></li>
                </ul>
                <p>Please be ready a few minutes before the scheduled time. We look forward to seeing you.</p>
                <p>Sincerely,<br>The Serenity Harbor Team</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
    $stmt_details->close();
}

// --- Close connections and Redirect ---
$stmt->close();
$db->close();
redirect('../index.php?page=dashboard&status=approved');
?>

