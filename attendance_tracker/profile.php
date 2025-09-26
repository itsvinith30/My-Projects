<?php
// profile.php
// Page for users to change their own password.
require_once 'header.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password != $confirm_password) {
        $error = "New password and confirmation do not match.";
    } else {
        $user_id = $_SESSION['id'];
        $role = $_SESSION['role'];
        $table_name = '';
        $id_column = '';

        // Determine which table to query based on role
        switch ($role) {
            case 'admin':
            case 'teacher':
                $table_name = 'users';
                $id_column = 'user_id';
                break;
            case 'student':
                $table_name = 'students';
                $id_column = 'student_id';
                break;
            case 'parent':
                $table_name = 'parents';
                $id_column = 'parent_id';
                break;
        }

        if ($table_name) {
            // First, get the current hashed password from the database
            $sql_get_pass = "SELECT password_hash FROM {$table_name} WHERE {$id_column} = ?";
            if ($stmt_get = $conn->prepare($sql_get_pass)) {
                $stmt_get->bind_param("i", $user_id);
                $stmt_get->execute();
                $result = $stmt_get->get_result();
                $user = $result->fetch_assoc();

                if ($user && password_verify($current_password, $user['password_hash'])) {
                    // Current password is correct, now update with the new password
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE {$table_name} SET password_hash = ? WHERE {$id_column} = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("si", $new_password_hash, $user_id);
                        if ($stmt_update->execute()) {
                            $message = "Password updated successfully.";
                            log_action('PASSWORD_CHANGED', "User changed their own password.");
                        } else {
                            $error = "Failed to update password. Please try again.";
                        }
                        $stmt_update->close();
                    }
                } else {
                    $error = "The current password you entered is incorrect.";
                }
                $stmt_get->close();
            }
        } else {
            $error = "Invalid user role detected.";
        }
    }
}
?>

<div class="main-content">
    <div class="container">
        <title>My Profile</title>
        <h2>My Profile</h2>
        <p>Update your account details below.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- We use form-container to center the single card -->
        <div class="form-container" style="justify-content: center;">
            <div class="form-card">
                <h3>Change Password</h3>
                <form action="profile.php" method="post">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" id="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                    <input type="submit" class="btn" value="Update Password">
                </form>
            </div>
        </div>
    </div>
</div>

