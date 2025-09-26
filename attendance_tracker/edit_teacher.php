<?php
// edit_teacher.php
// Page for admins to edit an existing teacher's details.

// --- ALL PHP LOGIC GOES BEFORE ANY HTML OUTPUT ---
require_once 'db_connect.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher = null;

// --- Handle form submission for updating the teacher ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email)) {
        $error = "Name and email fields are required.";
    } else {
        // Prepare the update statement
        $sql_update = "UPDATE users SET name = ?, email = ?";
        
        $params = [$name, $email];
        $types = "ss";
        
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash = ?";
            $params[] = $password_hash;
            $types .= "s";
        }
        
        $sql_update .= " WHERE user_id = ? AND role = 'teacher'";
        $params[] = $teacher_id;
        $types .= "i";

        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                log_action('USER_UPDATED', "Admin updated details for teacher ID: {$teacher_id}.");
                header("location: manage_teachers.php?message=updated");
                exit;
            } else {
                $error = "Failed to update teacher. The email may already be in use.";
            }
            $stmt->close();
        }
    }
}

// --- Fetch the teacher's current details to display in the form ---
if ($teacher_id > 0) {
    $sql_fetch = "SELECT user_id, name, email FROM users WHERE user_id = ? AND role = 'teacher'";
    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $teacher_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows == 1) {
            $teacher = $result->fetch_assoc();
        } else {
            $error = "No teacher found with that ID.";
        }
        $stmt_fetch->close();
    }
} else {
    $error = "Invalid teacher ID provided.";
}

// --- NOW WE CAN INCLUDE THE HEADER AND START THE HTML ---
require_once 'header.php';
?>

<div class="main-content">
    <div class="container">
        <title>Edit Teacher</title>
        <h2>Edit Teacher</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="form-container" style="justify-content: center;">
                <div class="form-card">
                    <form action="edit_teacher.php?id=<?php echo $teacher_id; ?>" method="post">
                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password" placeholder="Leave blank to keep current password">
                        </div>
                        <input type="submit" name="update_teacher" class="btn" value="Update Teacher">
                        <a href="manage_teachers.php" class="btn btn-secondary" style="margin-top: 10px; text-align: center; display: block; background-color: var(--muted-text-color);">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
