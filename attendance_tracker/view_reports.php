<?php
// view_reports.php
// Page for teachers/admins to generate and view attendance reports.
require_once 'header.php';

// Allow admin and teacher to access
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'teacher') {
    header("location: index.php");
    exit;
}

// Initialize variables
$class_id = '';
$start_date = '';
$end_date = '';
$attendance_records = [];
$error = '';

// --- Fetch classes for the filter dropdown, filtered by teacher if applicable ---
$classes = [];
$sql_classes = "SELECT class_id, class_name, section FROM classes";
if ($_SESSION['role'] == 'teacher') {
    $sql_classes .= " WHERE teacher_id = ?";
}
$sql_classes .= " ORDER BY class_name, section";

$stmt_classes = $conn->prepare($sql_classes);
if ($_SESSION['role'] == 'teacher') {
    $stmt_classes->bind_param("i", $_SESSION['id']);
}
$stmt_classes->execute();
$result_classes = $stmt_classes->get_result();
if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Handle form submission for filtering
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['filter'])) {
    $class_id = $_GET['class_id'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // Security Check: Verify teacher is assigned to this class
    if ($_SESSION['role'] == 'teacher') {
        $is_assigned = false;
        foreach ($classes as $class) {
            if ($class['class_id'] == $class_id) {
                $is_assigned = true;
                break;
            }
        }
        if (!$is_assigned) {
            $error = "You are not authorized to view reports for this class.";
        }
    }

    if (empty($error) && !empty($class_id) && !empty($start_date) && !empty($end_date)) {
        $sql = "SELECT ar.date, s.name as student_name, s.roll_number, ar.status, ar.remarks 
                FROM attendance_records ar
                JOIN students s ON ar.student_id = s.student_id
                WHERE ar.class_id = ? AND ar.date BETWEEN ? AND ?
                ORDER BY ar.date, s.name";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iss", $class_id, $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $attendance_records[] = $row;
            }
            $stmt->close();
        }
    }
}
?>
<title>View Attendance Reports</title>

<div class="main-content">
    <div class="container">
        <h2>View Attendance Reports</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filter Form -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="form-container">
            <div class="form-group">
                <label for="class_id">Select Class:</label>
                <select name="class_id" id="class_id" required>
                    <option value="">-- Select a Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>" <?php if ($class_id == $class['class_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
            </div>
            <button type="submit" name="filter" value="1" class="btn">Generate Report</button>
        </form>

        <hr>

        <!-- Report Results -->
        <?php if (!empty($attendance_records) && empty($error)): ?>
            <h3>Report for <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></h3>
            
            <!-- Export to CSV Button -->
            <a href="export_csv.php?class_id=<?php echo $class_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn" style="width: auto; margin-bottom: 20px; display: inline-block;">Export to CSV</a>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($record['status'])); ?></td>
                            <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($_GET['filter']) && empty($error)): ?>
            <p>No attendance records found for the selected criteria.</p>
        <?php endif; ?>
    </div>
</div>

