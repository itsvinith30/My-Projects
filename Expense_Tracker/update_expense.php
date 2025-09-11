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

$id = $input['id'] ?? 0;
$description = $input['description'] ?? '';
$amount = $input['amount'] ?? 0;
$category = $input['category'] ?? 'Uncategorized';
$expense_date = $input['date'] ?? date('Y-m-d');
$receipt_url = $input['receipt_url'] ?? '';

if (empty($id) || empty($description) || !is_numeric($amount) || $amount <= 0 || empty($expense_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input provided.']);
    exit;
}

$stmt = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, category = ?, expense_date = ?, receipt_url = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sdsssii", $description, $amount, $category, $expense_date, $receipt_url, $id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update expense. It may not exist or you do not have permission.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating expense: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>