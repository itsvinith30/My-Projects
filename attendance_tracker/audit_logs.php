<?php
// audit_logs.php
// Admin page to view the system audit trail.
require_once 'header.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

// Fetch all audit logs, most recent first
$logs = [];
$sql = "SELECT log_id, user_id, user_role, action_type, log_message, ip_address, created_at FROM audit_logs ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>

<div class="main-content">
    <div class="container">
        <title>Audit Logs</title>
        <h2>System Audit Logs</h2>
        <p>This page shows a record of all significant actions taken within the application.</p>

        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User Role</th>
                    <th>Action Type</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No audit logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($log['user_role'])); ?></td>
                            <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                            <td><?php echo htmlspecialchars($log['log_message']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
