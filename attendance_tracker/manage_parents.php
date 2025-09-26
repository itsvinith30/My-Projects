<?php
// manage_parents.php
// Admin page to create parent accounts and link them to students.
require_once 'header.php';

// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    header("location: index.php");
    exit;
}

$message = '';
$error = '';

// Handle adding a new parent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_parent'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all parent details.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO parents (name, email, password_hash) VALUES (?, ?, ?)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("sss", $name, $email, $password_hash);
            if ($stmt_insert->execute()) {
                $message = "New parent account created successfully.";
                log_action('PARENT_CREATED', "Admin created new parent: {$name} ({$email}).");
            } else {
                $error = "Failed to create parent account. The email may already be in use.";
            }
            $stmt_insert->close();
        }
    }
}

// Handle linking a student to a parent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_student'])) {
    $parent_id = $_POST['parent_id'];
    $student_id = $_POST['student_id'];

    if (empty($parent_id) || empty($student_id)) {
        $error = "Please select both a parent and a student to link.";
    } else {
        $sql_link = "INSERT INTO parent_student_link (parent_id, student_id) VALUES (?, ?)";
        if ($stmt_link = $conn->prepare($sql_link)) {
            $stmt_link->bind_param("ii", $parent_id, $student_id);
            if ($stmt_link->execute()) {
                $message = "Student linked to parent successfully.";
                log_action('PARENT_STUDENT_LINKED', "Admin linked student ID {$student_id} to parent ID {$parent_id}.");
            } else {
                $error = "Failed to link student. They may already be linked to this parent.";
            }
            $stmt_link->close();
        }
    }
}

// Fetch all parents for the dropdowns and the table
$parents = [];
$sql_parents = "SELECT parent_id, name, email FROM parents ORDER BY name";
$result_parents = $conn->query($sql_parents);
if ($result_parents) {
    while ($row = $result_parents->fetch_assoc()) {
        $parents[] = $row;
    }
}

// Fetch all students for the dropdown
$all_students = [];
$sql_all_students = "SELECT student_id, name FROM students ORDER BY name";
$result_all_students = $conn->query($sql_all_students);
if ($result_all_students) {
    while ($row = $result_all_students->fetch_assoc()) {
        $all_students[] = $row;
    }
}

// Fetch existing links for the display table
$links = [];
$sql_links = "SELECT p.name as parent_name, s.name as student_name
              FROM parent_student_link psl
              JOIN parents p ON psl.parent_id = p.parent_id
              JOIN students s ON psl.student_id = s.student_id
              ORDER BY p.name, s.name";
$result_links = $conn->query($sql_links);
if ($result_links) {
    while($row = $result_links->fetch_assoc()) {
        $links[] = $row;
    }
}
?>

<div class="main-content">
    <div class="container">
        <title>Manage Parents</title>
        <h2>Manage Parents</h2>
        <p>Create parent accounts and link them to their children.</p>

        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="form-container">
            <!-- Card for adding a new parent -->
            <div class="form-card">
                <h3>Add New Parent</h3>
                <form action="manage_parents.php" method="post">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Parent Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <input type="submit" name="add_parent" class="btn" value="Add Parent">
                </form>
            </div>

            <!-- Card for linking a parent to a student -->
            <div class="form-card">
                <h3>Link Parent to Student</h3>
                <form action="manage_parents.php" method="post">
                    <div class="form-group">
                        <label for="parent_id">Select Parent</label>
                        <select name="parent_id" id="parent_id" required>
                            <option value="">-- Choose a parent --</option>
                            <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['parent_id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Select Student</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">-- Choose a student --</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="submit" name="link_student" class="btn" value="Link Student">
                </form>
            </div>
        </div>

        <hr>

        <!-- Table for existing links -->
        <h3>Existing Parent-Student Links</h3>
        <table>
            <thead>
                <tr>
                    <th>Parent Name</th>
                    <th>Linked Student</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($links)): ?>
                    <tr><td colspan="2" style="text-align:center;">No links found.</td></tr>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($link['parent_name']); ?></td>
                            <td><?php echo htmlspecialchars($link['student_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

