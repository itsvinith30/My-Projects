<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Universal compatibility version of the code
$stmt = $conn->prepare("SELECT id, description, amount, category, expense_date, receipt_url FROM expenses WHERE user_id = ? ORDER BY expense_date DESC, id DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$stmt->bind_result($id, $description, $amount, $category, $expense_date, $receipt_url);

$data = [];
while ($stmt->fetch()) {
    $data[] = [
        'id' => $id,
        'description' => $description,
        'amount' => $amount,
        'category' => $category,
        'expense_date' => $expense_date,
        'receipt_url' => $receipt_url
    ];
}

echo json_encode(['success' => true, 'expenses' => $data]);

$stmt->close();
$conn->close();
?>

