<?php
// This is the main entry point of your application.
// It will handle routing to show the correct page (login, register, dashboard).
require_once 'config.php';

// Simple Router Logic
// Check for a 'page' parameter in the URL, e.g., index.php?page=login
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serenity Harbor Counseling</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <header>
        <nav class="container">
            <a href="index.php?page=home" class="logo">Serenity Harbor</a>
            <div class="nav-links">
                <ul>
                    <?php if ($is_logged_in): ?>
                        <li><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li><a href="index.php?page=logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="index.php?page=login">Login</a></li>
                        <li><a href="index.php?page=register">Register</a></li>
                    <?php endif; ?>
                </ul>
                <div class="theme-switch-wrapper">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" />
                        <div class="slider round">
                            <span class="sun-icon"><i data-feather="sun"></i></span>
                            <span class="moon-icon"><i data-feather="moon"></i></span>
                        </div>
                    </label>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <?php
        switch ($page) {
            case 'login':
                include 'views/login.php';
                break;
            case 'register':
                include 'views/register.php';
                break;
            case 'dashboard':
                if ($is_logged_in) {
                    if ($user_role === 'patient') {
                        include 'views/patient_dashboard.php';
                    } else {
                        include 'views/counselor_dashboard.php';
                    }
                } else {
                    redirect('index.php?page=login');
                }
                break;
            case 'logout':
                // Destroy the session and redirect
                session_unset();
                session_destroy();
                redirect('index.php?page=home');
                break;
            default: // Home page
                include 'views/home.php';
                break;
        }
        ?>
    </main>
    <script src="script.js"></script>
    <script>
      feather.replace() // Initialize Feather Icons
    </script>
</body>
</html>

