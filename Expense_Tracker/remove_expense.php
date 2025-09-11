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
$expense_id = $input['id'] ?? 0;

if (empty($expense_id) || !is_numeric($expense_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Expense ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $expense_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Expense removed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Expense not found or you do not have permission to delete it.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error removing expense: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>