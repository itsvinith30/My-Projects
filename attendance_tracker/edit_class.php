<?php
// edit_class.php
// Admin page to edit an existing class's details.

// --- ALL PHP LOGIC MUST GO BEFORE ANY HTML OUTPUT ---
require_once 'db_connect.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$class = null;

// --- Handle form submission for updating the class ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_class'])) {
    $class_id = $_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;

    if (empty($class_name) || empty($section)) {
        $error = "Class name and section are required.";
    } else {
        $sql_update = "UPDATE classes SET class_name = ?, section = ?, teacher_id = ? WHERE class_id = ?";
        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("ssii", $class_name, $section, $teacher_id, $class_id);
            if ($stmt->execute()) {
                log_action('CLASS_UPDATED', "Admin updated details for class ID: {$class_id}.");
                header("location: manage_classes.php?message=updated");
                exit; // Crucial to stop script execution after redirect
            } else {
                $error = "Failed to update class details.";
            }
            $stmt->close();
        }
    }
}

// --- Fetch the class's current details for the form ---
if ($class_id > 0) {
    $sql_fetch = "SELECT class_id, class_name, section, teacher_id FROM classes WHERE class_id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $class_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows == 1) {
            $class = $result->fetch_assoc();
        } else {
            $error = "No class found with that ID.";
        }
        $stmt_fetch->close();
    }
} else {
    $error = "Invalid class ID provided.";
}

// Fetch all teachers for the dropdown
$teachers = [];
$sql_all_teachers = "SELECT user_id, name FROM users WHERE role = 'teacher' ORDER BY name";
$result_teachers = $conn->query($sql_all_teachers);
if ($result_teachers) {
    while ($row = $result_teachers->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// --- NOW WE CAN INCLUDE THE HEADER AND START THE HTML ---
require_once 'header.php';
?>

<div class="main-content">
    <div class="container">
        <title>Edit Class</title>
        <h2>Edit Class</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="form-container" style="justify-content: center;">
                <div class="form-card">
                    <form action="edit_class.php?id=<?php echo $class_id; ?>" method="post">
                        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                        
                        <div class="form-group">
                            <label for="class_name">Class Name</label>
                            <input type="text" name="class_name" id="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="section">Section</label>
                            <input type="text" name="section" id="section" value="<?php echo htmlspecialchars($class['section']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="teacher_id">Assign Teacher</label>
                            <select name="teacher_id" id="teacher_id">
                                <option value="">None</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['user_id']; ?>" <?php echo ($class['teacher_id'] == $teacher['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="submit" name="update_class" class="btn" value="Update Class">
                        <a href="manage_classes.php" class="btn btn-secondary" style="margin-top: 10px; text-align: center; display: block; background-color: var(--muted-text-color);">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

