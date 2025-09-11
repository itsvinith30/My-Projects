<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01', strtotime('-6 months'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-t');


// --- THIS IS THE FIX ---
// Data for bar chart (Query rewritten for ONLY_FULL_GROUP_BY compatibility)
$monthly_data = [];
$stmt = $conn->prepare("
    SELECT YEAR(expense_date) as year, MONTH(expense_date) as month_num, SUM(amount) as total
    FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?
    GROUP BY YEAR(expense_date), MONTH(expense_date) 
    ORDER BY year, month_num
");
$stmt->bind_param("iss", $user_id, $startDate, $endDate);
$stmt->execute();
$stmt->bind_result($year, $month_num, $total);
while ($stmt->fetch()) {
    // Convert month number to month name in PHP
    $month_name = date("F", mktime(0, 0, 0, $month_num, 10));
    $monthly_data[] = ['year' => $year, 'month' => $month_name, 'total' => $total];
}
$stmt->close();

// Data for transaction table
$transactions = [];
$stmt = $conn->prepare("
    SELECT id, description, amount, category, expense_date, receipt_url 
    FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?
    ORDER BY expense_date DESC, id DESC
");
$stmt->bind_param("iss", $user_id, $startDate, $endDate);
$stmt->execute();
$stmt->bind_result($id, $description, $amount, $category, $expense_date, $receipt_url);
while ($stmt->fetch()) {
    $transactions[] = [
        'id' => $id,
        'description' => $description,
        'amount' => $amount,
        'category' => $category,
        'expense_date' => $expense_date,
        'receipt_url' => $receipt_url
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'monthlyData' => $monthly_data,
    'transactions' => $transactions
]);

$conn->close();
?>

