<?php
// student_dashboard.php
require_once 'header.php';

// Authorization: only students
if ($_SESSION['role'] != 'student') {
    header("location: login.php");
    exit;
}

$student_id = $_SESSION['id'];
$message = '';

// Handle leave request submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_leave'])) {
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $reason = trim($_POST['reason']);

    if (!empty($date_from) && !empty($date_to) && !empty($reason)) {
        $sql_insert = "INSERT INTO leave_requests (student_id, date_from, date_to, reason) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql_insert)) {
            $stmt->bind_param("isss", $student_id, $date_from, $date_to, $reason);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Leave request submitted successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error submitting request.</div>';
            }
            $stmt->close();
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    }
}

// Fetch student's attendance records
$sql_attendance = "SELECT date, status FROM attendance_records WHERE student_id = ? ORDER BY date DESC";
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bind_param("i", $student_id);
$stmt_attendance->execute();
$attendance_result = $stmt_attendance->get_result();
$stmt_attendance->close();

// Fetch student's leave requests
$sql_leave = "SELECT date_from, date_to, reason, status FROM leave_requests WHERE student_id = ? ORDER BY created_at DESC";
$stmt_leave = $conn->prepare($sql_leave);
$stmt_leave->bind_param("i", $student_id);
$stmt_leave->execute();
$leave_result = $stmt_leave->get_result();
$stmt_leave->close();
?>
<head>
    <title>Student Dashboard - Attendance Tracker</title>
</head>

<div class="main-content">
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <p>This is your personal dashboard where you can view your attendance and manage leave requests.</p>
        
        <?php echo $message; ?>

        <div class="student-layout">
            <!-- Submit Leave Request -->
            <div class="leave-request-form">
                <h3>Submit a Leave Request</h3>
                <form action="student_dashboard.php" method="post">
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" required>
                    </div>
                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" required>
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason for Leave</label>
                        <textarea id="reason" name="reason" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                    </div>
                    <input type="submit" name="submit_leave" class="btn" value="Submit Request">
                </form>
            </div>

            <!-- Leave Request History -->
            <div class="leave-history">
                <h3>Your Leave Requests</h3>
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leave_result->num_rows > 0): ?>
                            <?php while($row = $leave_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['date_from']); ?></td>
                                    <td><?php echo htmlspecialchars($row['date_to']); ?></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">You have not submitted any leave requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Attendance History -->
        <h3>Your Attendance History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_result->num_rows > 0): ?>
                    <?php while($row = $attendance_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>