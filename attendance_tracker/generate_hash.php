<?php
// generate_hash.php
// A simple script to generate a password hash.

// --- IMPORTANT ---
// 1. Place this file in your 'attendance-tracker' project folder.
// 2. Open your browser and go to: http://localhost/attendance-tracker/generate_hash.php
// 3. Copy the generated hash.
// 4. DO NOT leave this file on a live server.

$passwordToHash = 'teacherpass'; // Change to 'teacherpass' for the teacher account

$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

echo "<h1>Password Hash Generator</h1>";
echo "<p>Password to hash: <strong>" . htmlspecialchars($passwordToHash) . "</strong></p>";
echo "<p>Generated Hash (copy this):</p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hashedPassword) . "</textarea>";

?>
