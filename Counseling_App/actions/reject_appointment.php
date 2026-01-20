<?php
// This file handles the logic when a counselor rejects an appointment request.

require_once '../config.php';

// Add these lines to use the PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security Check ---
// Ensure a user is logged in and is a counselor.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'counselor') {
    // If not, redirect them to the login page.
    redirect('../index.php?page=login&error=unauthorized');
}

// Check if the form was submitted correctly.
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['appointment_id'])) {
    // If accessed incorrectly, redirect to dashboard.
    redirect('../index.php?page=dashboard');
}

// --- Get Form Data ---
$appointment_id = $_POST['appointment_id'];
$rejection_reason = trim($_POST['rejection_reason']);
$counselor_id = $_SESSION['user_id']; // Get the logged-in counselor's ID

// If no reason is provided, use a default polite message.
if (empty($rejection_reason)) {
    $rejection_reason = "The counselor is unavailable at this time and could not approve your request.";
}

// --- Database Connection ---
@$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    // In a real app, log this error. For now, we'll just stop.
    die("Database connection failed: " . $db->connect_error);
}

// --- Update the Appointment in the Database ---
// We add "AND counselor_id = ?" to ensure a counselor can only reject their OWN appointments.
$sql = "UPDATE appointments SET status = 'rejected', rejection_reason = ? WHERE appointment_id = ? AND counselor_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("sii", $rejection_reason, $appointment_id, $counselor_id);

if ($stmt->execute()) {
    // Update was successful. Now, let's notify the patient.
    
    // --- Fetch Patient's Email for Notification ---
    $sql_patient = "SELECT u.email, u.full_name 
                    FROM users u
                    JOIN appointments a ON u.user_id = a.patient_id
                    WHERE a.appointment_id = ?";
    $stmt_patient = $db->prepare($sql_patient);
    $stmt_patient->bind_param("i", $appointment_id);
    $stmt_patient->execute();
    $result = $stmt_patient->get_result();
    
    if ($result->num_rows === 1) {
        $patient = $result->fetch_assoc();
        $patient_email = $patient['email'];
        $patient_name = $patient['full_name'];

        // --- Send Rejection Email ---
        // Manually include the PHPMailer files.
        // This assumes you have a 'PHPMailer' folder in your project's root directory.
        require_once '../PHPMailer/src/Exception.php';
        require_once '../PHPMailer/src/PHPMailer.php';
        require_once '../PHPMailer/src/SMTP.php';

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
            $mail->Subject = 'Update on your Counseling Appointment Request';
            $mail->Body    = "
                <p>Dear {$patient_name},</p>
                <p>This email is to inform you that your recent request for an appointment has been declined.</p>
                <p><strong>Reason provided by the counselor:</strong></p>
                <p><em>\"{$rejection_reason}\"</em></p>
                <p>We encourage you to browse other available counselors or try booking again at a later date. We wish you the best on your journey.</p>
                <p>Sincerely,<br>The Serenity Harbor Team</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // For debugging purposes, you can log the error.
            // In a live environment, you would log this to a file.
            // error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
    
    $stmt_patient->close();
}

// --- Close connections and Redirect ---
$stmt->close();
$db->close();
// Redirect back to the dashboard. You can add a success message in the URL.
redirect('../index.php?page=dashboard&status=rejected');

?>

