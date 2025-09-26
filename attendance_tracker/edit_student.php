<?php
// edit_student.php
// Page for admins to edit an existing student's details.

// --- IMPORTANT: ALL PHP LOGIC MUST GO BEFORE ANY HTML OUTPUT ---
require_once 'db_connect.php'; // This starts the session and includes helpers

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student = null;

// --- Handle form submission for updating the student ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id = $_POST['student_id'];
    $name = trim($_POST['name']);
    $roll_number = trim($_POST['roll_number']);
    $class_id = $_POST['class_id'];
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($roll_number) || empty($class_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Prepare the update statement
        $sql_update = "UPDATE students SET name = ?, roll_number = ?, class_id = ?, email = ?";
        
        $params = [$name, $roll_number, $class_id, $email];
        $types = "ssis";
        
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash = ?";
            $params[] = $password_hash;
            $types .= "s";
        }
        
        $sql_update .= " WHERE student_id = ?";
        $params[] = $student_id;
        $types .= "i";

        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                log_action('STUDENT_UPDATED', "Admin updated details for student ID: {$student_id}.");
                // THIS IS THE FIX: Redirect happens before any HTML is output
                header("location: manage_students.php?message=updated");
                exit; // Crucial to stop script execution after redirect
            } else {
                $error = "Failed to update student. The email may already be in use by another student.";
            }
            $stmt->close();
        }
    }
}

// --- Fetch the student's current details to display in the form ---
// This part runs only if the page is loaded via GET or if the POST update failed.
if ($student_id > 0) {
    $sql_fetch = "SELECT student_id, name, roll_number, class_id, email FROM students WHERE student_id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $student_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows == 1) {
            $student = $result->fetch_assoc();
        } else {
            $error = "No student found with that ID.";
        }
        $stmt_fetch->close();
    }
} else {
    $error = "Invalid student ID provided.";
}

// Fetch all classes for the dropdown
$classes = [];
$sql_all_classes = "SELECT class_id, class_name, section FROM classes ORDER BY class_name, section";
$result_classes = $conn->query($sql_all_classes);
if ($result_classes) {
    while ($row = $result_classes->fetch_assoc()) {
        $classes[] = $row;
    }
}

// --- NOW WE CAN INCLUDE THE HEADER AND START THE HTML ---
require_once 'header.php';
?>

<div class="main-content">
    <div class="container">
        <title>Edit Student</title>
        <h2>Edit Student</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="form-container" style="justify-content: center;">
                <div class="form-card">
                    <form action="edit_student.php?id=<?php echo $student_id; ?>" method="post">
                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="roll_number">Roll Number</label>
                            <input type="text" name="roll_number" id="roll_number" value="<?php echo htmlspecialchars($student['roll_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="class_id">Class</label>
                            <select name="class_id" id="class_id" required>
                                <option value="">Select a class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>" <?php echo ($student['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <hr>
                        <p><strong>Student Login (Optional)</strong></p>
                        <div class="form-group">
                            <label for="email">Student Email</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password" placeholder="Leave blank to keep current password">
                        </div>
                        <input type="submit" name="update_student" class="btn" value="Update Student">
                        <a href="manage_students.php" class="btn btn-secondary" style="margin-top: 10px; text-align: center; display: block; background-color: var(--muted-text-color);">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

