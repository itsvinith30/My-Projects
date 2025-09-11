<?php
header('Content-Type: application/json');
include 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password_hash);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now log in.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed.']);
}

$stmt->close();
$conn->close();
?>