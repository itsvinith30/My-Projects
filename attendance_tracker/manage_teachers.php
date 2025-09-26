<?php
// manage_teachers.php
require_once 'header.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = ''; $error = ''; $search_term = '';

// Check for a success message from the edit page
if (isset($_GET['message']) && $_GET['message'] == 'updated') {
    $message = "Teacher details updated successfully.";
}

// Handle adding a new teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'teacher')";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("sss", $name, $email, $password_hash);
            if ($stmt_insert->execute()) {
                $message = "New teacher added successfully.";
                log_action('USER_CREATED', "Admin created new teacher: {$name} ({$email}).");
            } else {
                $error = "Failed to add new teacher. The email might already be in use.";
            }
            $stmt_insert->close();
        }
    }
}

// Handle deleting a teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    // Log before deleting to capture details
    log_action('USER_DELETED', "Admin initiated deletion for teacher ID: {$teacher_id}.");
    $sql_delete = "DELETE FROM users WHERE user_id = ? AND role = 'teacher'";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $teacher_id);
        if ($stmt_delete->execute()) {
            $message = "Teacher deleted successfully.";
        } else {
            $error = "Failed to delete teacher.";
        }
        $stmt_delete->close();
    }
}

// Fetch all teachers with search functionality
$teachers = [];
$sql_teachers = "SELECT user_id, name, email FROM users WHERE role = 'teacher'";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $sql_teachers .= " AND name LIKE ?";
    $stmt_teachers = $conn->prepare($sql_teachers);
    $search_param = "%" . $search_term . "%";
    $stmt_teachers->bind_param("s", $search_param);
} else {
    $sql_teachers .= " ORDER BY name";
    $stmt_teachers = $conn->prepare($sql_teachers);
}
$stmt_teachers->execute();
$result_teachers = $stmt_teachers->get_result();
if ($result_teachers) {
    while ($row = $result_teachers->fetch_assoc()) {
        $teachers[] = $row;
    }
}
$stmt_teachers->close();
?>

<div class="main-content">
    <div class="container">
        <title>Manage Teachers</title>
        <h2>Manage Teachers</h2>
        <p>Add, remove, and search for teacher accounts.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="form-container">
            <div class="form-card">
                <h3>Add New Teacher</h3>
                <form action="manage_teachers.php" method="post">
                    <div class="form-group"><label for="name">Full Name</label><input type="text" name="name" id="name" required></div>
                    <div class="form-group"><label for="email">Email</label><input type="email" name="email" id="email" required></div>
                    <div class="form-group"><label for="password">Password</label><input type="password" name="password" id="password" required></div>
                    <input type="submit" name="add_teacher" class="btn" value="Add Teacher">
                </form>
            </div>
            <div class="form-card">
                <h3>All Teachers</h3>
                <form action="manage_teachers.php" method="get" class="search-container">
                    <input type="text" name="search" placeholder="Search by teacher name..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="3" style="text-align:center;">No teachers found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_teacher.php?id=<?php echo $teacher['user_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                            <form action="manage_teachers.php" method="post" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                                                <input type="submit" name="delete_teacher" class="btn btn-sm btn-danger" value="Delete">
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

