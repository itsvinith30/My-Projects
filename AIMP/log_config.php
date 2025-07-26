<?php
$host = "localhost";
$user = "root";
$password = "302004";
$dbname = "user_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>