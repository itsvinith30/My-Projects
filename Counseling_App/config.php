<?php
// This file contains your database and email configuration.
// Keep it secure and do not expose it publicly.

// --- DATABASE CONFIGURATION ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); 
define('DB_PASS', '302004');     // Make sure this matches your XAMPP setup (usually empty)
define('DB_NAME', 'counseling_app');

// --- EMAIL CONFIGURATION (Using Gmail SMTP) ---
define('SMTP_HOST', 'smtp.gmail.com');                  // Gmail's SMTP server
define('SMTP_USER', 'avinithkumar2004@gmail.com');            // YOUR FULL GMAIL ADDRESS
define('SMTP_PASS', 'cqao bwgc tjpi pzdl');  // THE APP PASSWORD YOU GENERATED
define('SMTP_PORT', 587);                               // Port for TLS encryption

define('MAIL_FROM', 'avinithkumar2004@gmail.com');            // This MUST be the same as your SMTP_USER
define('MAIL_FROM_NAME', 'Serenity Harbor Counseling');

// --- SITE CONFIGURATION ---
define('SITE_URL', 'http://localhost/counseling-app'); // Your project's base URL

// --- Start Session ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// A simple helper function to redirect users
function redirect($url) {
    header('Location: ' . $url);
    exit();
}
?>

