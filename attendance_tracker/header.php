<?php
// header.php
// This file contains the shared HTML head, navigation, and session checks.
require_once 'db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get the current page name to set the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The title will be set on each individual page -->
    <link rel="stylesheet" href="style.css">
    <script>
        // Apply theme on initial load to prevent Flash of Unstyled Content (FOUC)
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand"><?php echo htmlspecialchars($app_settings['app_name'] ?? 'Attendance Tracker'); ?></a>
            
            <!-- Hamburger Menu Button -->
            <button class="menu-toggle" id="mobile-menu">
                &#9776; <!-- This is the hamburger icon character -->
            </button>

            <nav id="main-nav">
                <ul>
                    <!-- Core Links -->
                    <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
                    
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher'): ?>
                        <li><a href="take_attendance.php" class="<?php echo ($current_page == 'take_attendance.php') ? 'active' : ''; ?>">Take Attendance</a></li>
                        <li><a href="view_reports.php" class="<?php echo ($current_page == 'view_reports.php') ? 'active' : ''; ?>">View Reports</a></li>
                    <?php endif; ?>

                    <!-- Management Dropdown for Admin -->
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">Management &#9662;</a>
                            <ul class="dropdown-menu">
                                <li><a href="manage_teachers.php">Teachers</a></li>
                                <li><a href="manage_classes.php">Classes</a></li>
                                <li><a href="manage_students.php">Students</a></li>
                                <li><a href="manage_parents.php">Parents</a></li>
                                <li><a href="manage_leave.php">Leave Requests</a></li>
                            </ul>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">System &#9662;</a>
                            <ul class="dropdown-menu">
                                <li><a href="settings.php">Settings</a></li>
                                <li><a href="audit_logs.php">Audit Logs</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Single Manage Leave for Teacher -->
                    <?php if ($_SESSION['role'] == 'teacher'): ?>
                        <li><a href="manage_leave.php" class="<?php echo ($current_page == 'manage_leave.php') ? 'active' : ''; ?>">Manage Leave</a></li>
                    <?php endif; ?>

                    <!-- Right-aligned User Links -->
                    <li class="right-align"><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
            <!-- Theme Toggle Button -->
            <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
        </div>
    </header>

    <script>
        // JavaScript to toggle the mobile menu
        document.getElementById('mobile-menu').addEventListener('click', function() {
            document.getElementById('main-nav').getElementsByTagName('ul')[0].classList.toggle('show');
        });

        // JavaScript for Theme Toggle
        document.getElementById('theme-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark-mode');
            const currentTheme = document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', currentTheme);
        });

        // JavaScript for Dropdown Menus
        document.querySelectorAll('.dropdown-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                // Close other open dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== item.nextElementSibling) {
                        menu.classList.remove('show');
                    }
                });
                // Toggle current dropdown
                item.nextElementSibling.classList.toggle('show');
            });
        });

        // Close dropdowns if clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.dropdown-toggle')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>

