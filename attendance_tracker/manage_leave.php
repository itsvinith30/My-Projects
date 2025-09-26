<?php
// manage_leave.php
// Page for admins and teachers to view and manage student leave requests.
require_once 'header.php';
require_once 'config.php'; // For email credentials

// PHPMailer includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


// Ensure only admin and teacher can access
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';

// Handle updating leave request status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // 'Approved' or 'Denied'

    $sql_update = "UPDATE leave_requests SET status = ? WHERE request_id = ?";
    if ($stmt = $conn->prepare($sql_update)) {
        $stmt->bind_param("si", $status, $request_id);
        if ($stmt->execute()) {
            $message = "Leave request has been " . strtolower($status) . ".";
            log_action('LEAVE_STATUS_CHANGED', "User changed status of leave request ID {$request_id} to {$status}.");

            // --- Send notification email to the student ---
            $sql_student_info = "SELECT s.name as student_name, s.email as student_email, lr.date_from, lr.date_to 
                                 FROM leave_requests lr
                                 JOIN students s ON lr.student_id = s.student_id
                                 WHERE lr.request_id = ?";
            $stmt_info = $conn->prepare($sql_student_info);
            $stmt_info->bind_param("i", $request_id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();

            if ($info = $result_info->fetch_assoc()) {
                if (!empty($info['student_email'])) {
                    $student_name = $info['student_name'];
                    $student_email = $info['student_email'];
                    $date_from = date("F j, Y", strtotime($info['date_from']));
                    $date_to = date("F j, Y", strtotime($info['date_to']));
                    $request_status = strtolower($status); // 'approved' or 'denied'

                    // Use email templates from settings
                    $subject_template = $app_settings['leave_status_email_subject'] ?? 'Update on Your Leave Request';
                    $body_template = $app_settings['leave_status_email_body'] ?? 'Dear {student_name}, Your leave request from {date_from} to {date_to} has been {status}.';

                    // Replace placeholders
                    $subject = str_replace('{student_name}', $student_name, $subject_template);
                    $body = str_replace('{student_name}', $student_name, $body_template);
                    $body = str_replace('{date_from}', $date_from, $body);
                    $body = str_replace('{date_to}', $date_to, $body);
                    $body = str_replace('{status}', $request_status, $body);

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USERNAME;
                        $mail->Password   = SMTP_PASSWORD;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port       = SMTP_PORT;

                        $mail->setFrom(EMAIL_FROM, $app_settings['app_name'] ?? 'Attendance Tracker');
                        $mail->addAddress($student_email, $student_name);
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body    = nl2br($body);
                        $mail->AltBody = strip_tags($body);

                        $mail->send();
                    } catch (Exception $e) {
                        // Email sending failed, but the status was updated.
                        $error = "Request status updated, but failed to send notification email. Mailer Error: {$mail->ErrorInfo}";
                    }
                }
            }
        } else {
            $error = "Failed to update leave request status.";
        }
        $stmt->close();
    }
}


// Fetch all leave requests
$leave_requests = [];
$sql = "SELECT lr.request_id, s.name as student_name, c.class_name, c.section, lr.date_from, lr.date_to, lr.reason, lr.status
        FROM leave_requests lr
        JOIN students s ON lr.student_id = s.student_id
        LEFT JOIN classes c ON s.class_id = c.class_id
        ORDER BY lr.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $leave_requests[] = $row;
    }
}
?>

<div class="main-content">
    <div class="container">
        <title>Manage Leave Requests</title>
        <h2>Manage Leave Requests</h2>
        <p>Review and approve or deny student leave requests.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leave_requests)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No leave requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leave_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['class_name'] . ' - ' . $request['section']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($request['date_from'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($request['date_to'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] == 'Pending'): ?>
                                    <form action="manage_leave.php" method="post" style="display:inline-block; margin-right: 5px;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="status" value="Approved">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form action="manage_leave.php" method="post" style="display:inline-block;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="status" value="Denied">
                                        <button type="submit" class="btn btn-sm btn-danger">Deny</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

