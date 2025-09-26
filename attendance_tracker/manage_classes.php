<?php
// manage_classes.php
// Admin page to add, view, search, and delete classes.
require_once 'header.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';
$search_term = '';

// Check for a success message from the edit page
if (isset($_GET['message']) && $_GET['message'] == 'updated') {
    $message = "Class details updated successfully.";
}

// Handle adding a new class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;

    if (empty($class_name) || empty($section)) {
        $error = "Please fill in all required fields.";
    } else {
        $sql_insert = "INSERT INTO classes (class_name, section, teacher_id) VALUES (?, ?, ?)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssi", $class_name, $section, $teacher_id);
            if ($stmt_insert->execute()) {
                $message = "New class added successfully.";
                log_action('CLASS_CREATED', "Admin created new class: {$class_name} - {$section}.");
            } else {
                $error = "Failed to add new class. Please try again.";
            }
            $stmt_insert->close();
        }
    }
}

// Handle deleting a class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_class'])) {
    $class_id = $_POST['class_id'];
    log_action('CLASS_DELETED', "Admin initiated deletion for class ID: {$class_id}.");
    $sql_delete = "DELETE FROM classes WHERE class_id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $class_id);
        if ($stmt_delete->execute()) {
            $message = "Class deleted successfully.";
        } else {
            $error = "Failed to delete class.";
        }
        $stmt_delete->close();
    }
}

// Fetch all available teachers for the dropdown
$teachers = [];
$sql_all_teachers = "SELECT user_id, name FROM users WHERE role = 'teacher' ORDER BY name";
$result_teachers = $conn->query($sql_all_teachers);
if ($result_teachers) {
    while ($row = $result_teachers->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Fetch all classes with search functionality
$classes = [];
$sql_classes = "SELECT c.class_id, c.class_name, c.section, u.name as teacher_name 
                FROM classes c 
                LEFT JOIN users u ON c.teacher_id = u.user_id 
                WHERE 1=1";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $sql_classes .= " AND (c.class_name LIKE ? OR c.section LIKE ? OR u.name LIKE ?)";
    $stmt_classes = $conn->prepare($sql_classes);
    $search_param = "%" . $search_term . "%";
    $stmt_classes->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    // Order by the numeric part of the class name first.
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
$stmt_classes->close();
?>

<div class="main-content">
    <div class="container">
        <title>Manage Classes</title>
        <h2>Manage Classes</h2>
        <p>Add, remove, and search for classes.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <!-- Card for adding a new class -->
            <div class="form-card">
                <h3>Add New Class</h3>
                <form action="manage_classes.php" method="post">
                    <div class="form-group">
                        <label for="class_name">Class Name (e.g., Grade 10)</label>
                        <input type="text" name="class_name" id="class_name" required>
                    </div>
                    <div class="form-group">
                        <label for="section">Section (e.g., A)</label>
                        <input type="text" name="section" id="section" required>
                    </div>
                    <div class="form-group">
                        <label for="teacher_id">Assign Teacher (Optional)</label>
                        <select name="teacher_id" id="teacher_id">
                            <option value="">None</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['user_id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="submit" name="add_class" class="btn" value="Add Class">
                </form>
            </div>

            <!-- Card for the list of all classes -->
            <div class="form-card">
                <h3>All Classes</h3>
                <form action="manage_classes.php" method="get" class="search-container">
                    <input type="text" name="search" placeholder="Search by class, section, or teacher..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Assigned Teacher</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No classes found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['section']); ?></td>
                                    <td><?php echo htmlspecialchars($class['teacher_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_class.php?id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                            <form action="manage_classes.php" method="post" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <input type="submit" name="delete_class" class="btn btn-sm btn-danger" value="Delete">
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

