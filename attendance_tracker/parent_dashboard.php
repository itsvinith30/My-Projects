<?php
// parent_dashboard.php
require_once 'header.php';

// Ensure only parents can access this page
if ($_SESSION['role'] != 'parent') {
    header("location: index.php");
    exit;
}

$parent_id = $_SESSION['id'];
$children = [];

// Fetch the students linked to this parent
$sql_children = "SELECT s.student_id, s.name 
                 FROM students s
                 JOIN parent_student_link psl ON s.student_id = psl.student_id
                 WHERE psl.parent_id = ?";

if ($stmt_children = $conn->prepare($sql_children)) {
    $stmt_children->bind_param("i", $parent_id);
    $stmt_children->execute();
    $result_children = $stmt_children->get_result();
    while ($row = $result_children->fetch_assoc()) {
        $children[] = $row;
    }
    $stmt_children->close();
}

// Fetch attendance for each child
foreach ($children as $key => $child) {
    $attendance_records = [];
    $sql_attendance = "SELECT ar.date, ar.status, ar.remarks, c.class_name, c.section
                       FROM attendance_records ar
                       JOIN classes c ON ar.class_id = c.class_id
                       WHERE ar.student_id = ?
                       ORDER BY ar.date DESC";
    
    if ($stmt_attendance = $conn->prepare($sql_attendance)) {
        $stmt_attendance->bind_param("i", $child['student_id']);
        $stmt_attendance->execute();
        $result_attendance = $stmt_attendance->get_result();
        while ($row = $result_attendance->fetch_assoc()) {
            $attendance_records[] = $row;
        }
        $stmt_attendance->close();
    }
    $children[$key]['attendance'] = $attendance_records;
}
?>

<div class="main-content">
    <div class="container">
        <title>Parent Dashboard</title>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <p>Here is the attendance history for your child/children.</p>

        <?php if (empty($children)): ?>
            <p>No students are currently linked to your account.</p>
        <?php else: ?>
            <?php foreach ($children as $child): ?>
                <hr>
                <h3>Attendance for <?php echo htmlspecialchars($child['name']); ?></h3>
                <?php if (empty($child['attendance'])): ?>
                    <p>No attendance records found for this child.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($child['attendance'] as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['class_name'] . ' - ' . $record['section']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <span class="status-text-<?php echo htmlspecialchars($record['status']); ?>">
                                            <?php echo htmlspecialchars($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

