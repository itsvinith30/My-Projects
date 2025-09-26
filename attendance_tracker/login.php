<?php
// login.php
// Handles user login and authentication.
require_once 'db_connect.php';

$email = $password = "";
$email_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $login_err = "Please enter email and password.";
    } else {
        // --- Try logging in as a user (admin/teacher) ---
        $sql_user = "SELECT user_id, name, email, password_hash, role FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql_user)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $db_email, $hashed_password, $role);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = $role;
                        log_action('LOGIN_SUCCESS', "User '{$email}' logged in successfully.");
                        header("location: index.php");
                        exit();
                    } else {
                        $login_err = "The password you entered was not valid.";
                        log_action('LOGIN_FAILURE', "Failed login attempt for user '{$email}': Invalid password.");
                    }
                }
            } else {
                // --- Try logging in as a student ---
                $sql_student = "SELECT student_id, name, email, password_hash FROM students WHERE email = ?";
                if ($stmt_student = $conn->prepare($sql_student)) {
                    $stmt_student->bind_param("s", $email);
                    $stmt_student->execute();
                    $stmt_student->store_result();
                    if ($stmt_student->num_rows == 1) {
                        $stmt_student->bind_result($id, $name, $db_email, $hashed_password);
                        if ($stmt_student->fetch()) {
                            if ($hashed_password && password_verify($password, $hashed_password)) {
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["role"] = "student";
                                log_action('LOGIN_SUCCESS', "Student '{$email}' logged in successfully.");
                                header("location: student_dashboard.php");
                                exit();
                            } else {
                                $login_err = "The password you entered was not valid.";
                                log_action('LOGIN_FAILURE', "Failed login attempt for student '{$email}': Invalid password.");
                            }
                        }
                    } else {
                        // --- Try logging in as a parent ---
                        $sql_parent = "SELECT parent_id, name, email, password_hash FROM parents WHERE email = ?";
                        if ($stmt_parent = $conn->prepare($sql_parent)) {
                            $stmt_parent->bind_param("s", $email);
                            $stmt_parent->execute();
                            $stmt_parent->store_result();
                            if ($stmt_parent->num_rows == 1) {
                                $stmt_parent->bind_result($id, $name, $db_email, $hashed_password);
                                if ($stmt_parent->fetch()) {
                                    if (password_verify($password, $hashed_password)) {
                                        $_SESSION["loggedin"] = true;
                                        $_SESSION["id"] = $id;
                                        $_SESSION["name"] = $name;
                                        $_SESSION["role"] = "parent";
                                        log_action('LOGIN_SUCCESS', "Parent '{$email}' logged in successfully.");
                                        header("location: parent_dashboard.php");
                                        exit();
                                    } else {
                                        $login_err = "The password you entered was not valid.";
                                        log_action('LOGIN_FAILURE', "Failed login attempt for parent '{$email}': Invalid password.");
                                    }
                                }
                            } else {
                                $login_err = "No account found with that email.";
                                log_action('LOGIN_FAILURE', "Failed login attempt: No account found for email '{$email}'.");
                            }
                            $stmt_parent->close();
                        }
                    }
                    $stmt_student->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Classroom Attendance Tracker</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p>Admin: admin@gmail.com | Pass: adminpass</p>
            
        </form>
    </div>
</body>
</html>

