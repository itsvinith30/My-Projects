<?php
// settings.php
// Admin page to manage system-wide settings.
require_once 'header.php';

// --- Security Check: Only Admins Allowed ---
if ($_SESSION['role'] !== 'admin') {
    // Redirect non-admins to the main dashboard
    header("location: index.php");
    exit;
}

$message = '';

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Loop through all submitted POST data
    foreach ($_POST as $key => $value) {
        // Prepare a statement to update each setting
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
            $stmt->close();
        }
    }
    $message = '<div class="alert alert-success">Settings have been updated successfully.</div>';
    
    // --- Reload settings into session to reflect changes immediately ---
    load_settings($conn); 
}

// Fetch all current settings from the database to display in the form
$current_settings = [];
$sql_fetch = "SELECT setting_key, setting_value FROM settings";
$result = $conn->query($sql_fetch);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<head>
    <title>System Settings - <?php echo htmlspecialchars($app_settings['app_name']); ?></title>
</head>

<div class="main-content">
    <div class="container">
        <h2>System Settings</h2>
        <p>Manage the application's global settings and email templates.</p>
        
        <?php echo $message; ?>

        <form action="settings.php" method="post">
            <!-- General Settings -->
            <fieldset>
                <legend>General Settings</legend>
                <div class="form-group">
                    <label for="app_name">Application Name</label>
                    <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($current_settings['app_name'] ?? ''); ?>" required>
                    <small>This name will appear in the header and in emails.</small>
                </div>
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <input type="text" id="timezone" name="timezone" value="<?php echo htmlspecialchars($current_settings['timezone'] ?? 'UTC'); ?>" required>
                    <small>e.g., UTC, America/New_York, Europe/London, Asia/Kolkata</small>
                </div>
            </fieldset>

            <!-- Email Templates -->
            <fieldset>
                <legend>Email Templates</legend>
                <p><small>You can use placeholders like {student_name}, {date}, {date_from}, {date_to}, and {app_name}.</small></p>

                <div class="form-group">
                    <label for="absence_email_subject">Absence Notification Subject</label>
                    <input type="text" id="absence_email_subject" name="absence_email_subject" value="<?php echo htmlspecialchars($current_settings['absence_email_subject'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="absence_email_body">Absence Notification Body</label>
                    <textarea id="absence_email_body" name="absence_email_body" rows="6" required><?php echo htmlspecialchars($current_settings['absence_email_body'] ?? ''); ?></textarea>
                </div>

                <hr>

                <div class="form-group">
                    <label for="leave_approved_subject">Leave Approved Subject</label>
                    <input type="text" id="leave_approved_subject" name="leave_approved_subject" value="<?php echo htmlspecialchars($current_settings['leave_approved_subject'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="leave_approved_body">Leave Approved Body</label>
                    <textarea id="leave_approved_body" name="leave_approved_body" rows="4" required><?php echo htmlspecialchars($current_settings['leave_approved_body'] ?? ''); ?></textarea>
                </div>

                <hr>
                
                <div class="form-group">
                    <label for="leave_denied_subject">Leave Denied Subject</label>
                    <input type="text" id="leave_denied_subject" name="leave_denied_subject" value="<?php echo htmlspecialchars($current_settings['leave_denied_subject'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="leave_denied_body">Leave Denied Body</label>
                    <textarea id="leave_denied_body" name="leave_denied_body" rows="4" required><?php echo htmlspecialchars($current_settings['leave_denied_body'] ?? ''); ?></textarea>
                </div>
            </fieldset>
            
            <input type="submit" class="btn" value="Save Settings">
        </form>
    </div>
</div>

</body>
</html>

