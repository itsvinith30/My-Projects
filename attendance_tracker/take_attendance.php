<?php
// take_attendance.php
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

$class_id = $_POST['class_id'] ?? $_GET['class_id'] ?? null;
$date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
$message = '';
$error = '';

// Handle form submission to save attendance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['attendance'])) {
    $attendance_data = $_POST['attendance'];
    $class_id = $_POST['class_id'];
    $date = $_POST['date'];

    $conn->begin_transaction();
    try {
        foreach ($attendance_data as $student_id => $details) {
            $status = $details['status'];
            $remarks = $details['remarks'];

            // Check if a record already exists
            $sql_check = "SELECT record_id FROM attendance_records WHERE student_id = ? AND date = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("is", $student_id, $date);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Update existing record
                $record = $result_check->fetch_assoc();
                $record_id = $record['record_id'];
                $sql_update = "UPDATE attendance_records SET status = ?, remarks = ? WHERE record_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssi", $status, $remarks, $record_id);
                $stmt_update->execute();
            } else {
                // Insert new record
                $sql_insert = "INSERT INTO attendance_records (student_id, class_id, date, status, remarks) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iisss", $student_id, $class_id, $date, $status, $remarks);
                $stmt_insert->execute();
            }

            // If student is marked absent, send an email
            if ($status == 'Absent') {
                $sql_student_email = "SELECT email, name FROM students WHERE student_id = ? AND email IS NOT NULL AND email != ''";
                $stmt_email = $conn->prepare($sql_student_email);
                $stmt_email->bind_param("i", $student_id);
                $stmt_email->execute();
                $result_email = $stmt_email->get_result();
                if ($student_info = $result_email->fetch_assoc()) {
                    $student_email = $student_info['email'];
                    $student_name = $student_info['name'];

                    $subject_template = $app_settings['absence_email_subject'] ?? 'Absence Notification';
                    $body_template = $app_settings['absence_email_body'] ?? 'Dear Parent/Guardian of {student_name}, This is to inform you that your child was marked absent on {date}.';
                    
                    $subject = str_replace('{student_name}', $student_name, $subject_template);
                    $body = str_replace('{student_name}', $student_name, $body_template);
                    $body = str_replace('{date}', date("F j, Y", strtotime($date)), $body);
                    
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

                        $mail->send();
                    } catch (Exception $e) {
                         // Silently log error, but don't show the user
                         error_log("Mailer Error: " . $mail->ErrorInfo);
                    }
                }
            }
        }
        $conn->commit();
        $message = "Attendance saved successfully!";
        log_action('ATTENDANCE_SAVED', "User saved attendance for class ID {$class_id} on date {$date}.");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to save attendance. Error: " . $e->getMessage();
    }
}

// Fetch classes based on user role
$classes = [];
$sql_classes = "SELECT c.class_id, c.class_name, c.section FROM classes c";
if ($_SESSION['role'] == 'teacher') {
    $teacher_id = $_SESSION['id'];
    $sql_classes .= " WHERE c.teacher_id = ?";
    $stmt_classes = $conn->prepare($sql_classes);
    $stmt_classes->bind_param("i", $teacher_id);
} else {
    $sql_classes .= " ORDER BY CAST(SUBSTRING_INDEX(c.class_name, ' ', -1) AS UNSIGNED) ASC, c.section ASC";
    $stmt_classes = $conn->prepare($sql_classes);
}
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();
if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch students for the selected class
$students = [];
if ($class_id) {
    // Security check: if user is a teacher, ensure they are assigned to this class
    if ($_SESSION['role'] == 'teacher') {
        $is_assigned = false;
        foreach ($classes as $class) {
            if ($class['class_id'] == $class_id) {
                $is_assigned = true;
                break;
            }
        }
        if (!$is_assigned) {
            die("Access Denied: You are not assigned to this class.");
        }
    }

    $sql_students = "SELECT s.student_id, s.name, s.roll_number, ar.status, ar.remarks 
                     FROM students s
                     LEFT JOIN attendance_records ar ON s.student_id = ar.student_id AND ar.date = ?
                     WHERE s.class_id = ? 
                     ORDER BY s.roll_number, s.name";
    if ($stmt_students = $conn->prepare($sql_students)) {
        $stmt_students->bind_param("si", $date, $class_id);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();
        while ($row = $result_students->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>

<div class="main-content">
    <div class="container">
        <title>Take Attendance</title>
        <h2>Take Attendance</h2>
        <p>Select a class and date to mark attendance.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Selection Form -->
        <form action="take_attendance.php" method="get" class="form-inline">
            <div class="form-group">
                <label for="class_id">Class:</label>
                <select name="class_id" id="class_id" required onchange="this.form.submit()">
                    <option value="">Select a class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>" <?php echo ($class_id == $class['class_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>" required onchange="this.form.submit()">
            </div>
        </form>

        <?php if ($class_id && !empty($students)): ?>
            <hr>
            <h3>Attendance for <?php echo date('F j, Y', strtotime($date)); ?></h3>
            <form action="take_attendance.php" method="post">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="date" value="<?php echo $date; ?>">
                
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td>
                                    <select name="attendance[<?php echo $student['student_id']; ?>][status]" required>
                                        <option value="Present" <?php echo (isset($student['status']) && $student['status'] == 'Present') ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo (isset($student['status']) && $student['status'] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                        <option value="Late" <?php echo (isset($student['status']) && $student['status'] == 'Late') ? 'selected' : ''; ?>>Late</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="attendance[<?php echo $student['student_id']; ?>][remarks]" value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" placeholder="Optional remarks...">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <input type="submit" class="btn" value="Save Attendance">
            </form>
        <?php elseif ($class_id): ?>
            <p style="margin-top: 20px;">No students found for the selected class.</p>
        <?php endif; ?>
    </div>
</div>

