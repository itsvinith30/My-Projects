<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$category = $input['category'] ?? '';
$amount = $input['amount'] ?? 0;
$month = $input['month'] ?? date('n');
$year = $input['year'] ?? date('Y');

if (empty($category) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO budgets (user_id, category, amount, month, year) 
    VALUES (?, ?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE amount = ?
");
$stmt->bind_param("isdiid", $user_id, $category, $amount, $month, $year, $amount);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Budget saved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving budget: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>