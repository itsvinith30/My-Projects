<?php
// manage_students.php
// Admin page to add, view, search, and delete student accounts.
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
    $message = "Student details updated successfully.";
}

// Handle adding a new student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $roll_number = trim($_POST['roll_number']);
    $class_id = $_POST['class_id'];
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($roll_number) || empty($class_id)) {
        $error = "Please fill in all required student details.";
    } else {
        $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        $sql_insert = "INSERT INTO students (name, roll_number, class_id, email, password_hash) VALUES (?, ?, ?, ?, ?)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssiss", $name, $roll_number, $class_id, $email, $password_hash);
            if ($stmt_insert->execute()) {
                $message = "New student added successfully.";
                log_action('STUDENT_CREATED', "Admin created new student: {$name}.");
            } else {
                $error = "Failed to add new student. The email might already be in use.";
            }
            $stmt_insert->close();
        }
    }
}

// Handle deleting a student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    log_action('STUDENT_DELETED', "Admin initiated deletion for student ID: {$student_id}.");
    $sql_delete = "DELETE FROM students WHERE student_id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $student_id);
        if ($stmt_delete->execute()) {
            $message = "Student deleted successfully.";
        } else {
            $error = "Failed to delete student.";
        }
        $stmt_delete->close();
    }
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

// Fetch all students with search functionality
$students = [];
$sql_students = "SELECT s.student_id, s.name, s.roll_number, c.class_name, c.section 
                 FROM students s
                 LEFT JOIN classes c ON s.class_id = c.class_id
                 WHERE 1=1";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $sql_students .= " AND s.name LIKE ?";
    $stmt_students = $conn->prepare($sql_students);
    $search_param = "%" . $search_term . "%";
    $stmt_students->bind_param("s", $search_param);
} else {
    // THIS IS THE FIX: Order by the numeric part of the class name first.
    $sql_students .= " ORDER BY CAST(SUBSTRING_INDEX(c.class_name, ' ', -1) AS UNSIGNED) ASC, c.section ASC, s.roll_number ASC"; 
    $stmt_students = $conn->prepare($sql_students);
}

$stmt_students->execute();
$result_students = $stmt_students->get_result();
if ($result_students) {
    while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
    }
}
$stmt_students->close();

?>

<div class="main-content">
    <div class="container">
        <title>Manage Students</title>
        <h2>Manage Students</h2>
        <p>Add, remove, and search for student records.</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <!-- Card for adding a new student -->
            <div class="form-card">
                <h3>Add New Student</h3>
                <form action="manage_students.php" method="post">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="roll_number">Roll Number</label>
                        <input type="text" name="roll_number" id="roll_number" required>
                    </div>
                    <div class="form-group">
                        <label for="class_id">Class</label>
                        <select name="class_id" id="class_id" required>
                            <option value="">Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <p><strong>Student Login (Optional)</strong></p>
                    <div class="form-group">
                        <label for="email">Student Email</label>
                        <input type="email" name="email" id="email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password">
                    </div>
                    <input type="submit" name="add_student" class="btn" value="Add Student">
                </form>
            </div>

            <!-- Card for the list of all students -->
            <div class="form-card">
                <h3>All Students</h3>
                <form action="manage_students.php" method="get" class="search-container">
                    <input type="text" name="search" placeholder="Search by student name..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">No students found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class_name'] . ' - ' . $student['section']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                            <form action="manage_students.php" method="post" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                <input type="submit" name="delete_student" class="btn btn-sm btn-danger" value="Delete">
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

