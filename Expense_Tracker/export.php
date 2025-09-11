<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
$user_id = $_SESSION['user_id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="expenses_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('Date', 'Description', 'Category', 'Amount', 'Receipt URL'));

$stmt = $conn->prepare("SELECT expense_date, description, category, amount, receipt_url FROM expenses WHERE user_id = ? ORDER BY expense_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// --- UNIVERSAL COMPATIBILITY FIX ---
// Binds result columns to variables
$stmt->bind_result($expense_date, $description, $category, $amount, $receipt_url);
// Fetches results one by one and writes them to the CSV file
while($stmt->fetch()){
    fputcsv($output, [$expense_date, $description, $category, $amount, $receipt_url]);
}

fclose($output);
$stmt->close();
$conn->close();
exit();
?>