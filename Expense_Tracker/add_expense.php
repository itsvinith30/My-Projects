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

$description = $input['description'] ?? '';
$amount = $input['amount'] ?? 0;
$category = $input['category'] ?? 'Uncategorized';
$expense_date = $input['date'] ?? date('Y-m-d');
$receipt_url = $input['receipt_url'] ?? '';

if (empty($description) || !is_numeric($amount) || $amount <= 0 || empty($expense_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input provided.']);
    exit;
}

// --- Smart Categorization Logic ---
if ($category === 'Uncategorized') {
    $keywords = [
        'Food' => ['restaurant', 'cafe', 'grocery', 'food', 'lunch', 'dinner', 'snack', 'coffee', 'zomato', 'swiggy'],
        'Transport' => ['gas', 'taxi', 'bus', 'train', 'uber', 'lyft', 'fuel', 'parking', 'ola', 'metro'],
        'Shopping' => ['clothes', 'mall', 'online store', 'amazon', 'walmart', 'target', 'flipkart', 'myntra'],
        'Utilities' => ['electricity', 'water', 'internet', 'phone', 'bill', 'rent', 'broadband', 'gas bill'],
        'Entertainment' => ['movie', 'concert', 'game', 'bar', 'party', 'netflix', 'spotify', 'hotstar', 'cinema'],
        'Health' => ['pharmacy', 'doctor', 'hospital', 'medicine', 'gym'],
    ];
    $final_category = 'Uncategorized';
    foreach ($keywords as $cat => $keys) {
        foreach ($keys as $key) {
            if (stripos(strtolower($description), $key) !== false) {
                $final_category = $cat;
                break 2;
            }
        }
    }
} else {
    $final_category = $category;
}

// --- Insert into Database using a Prepared Statement ---
// This is the corrected SQL statement with all required columns
$stmt = $conn->prepare("INSERT INTO expenses (user_id, description, amount, category, expense_date, receipt_url) VALUES (?, ?, ?, ?, ?, ?)");

// Bind all 6 variables to the placeholders
// "isdsss" means Integer, String, Double, String, String, String
$stmt->bind_param("isdsss", $user_id, $description, $amount, $final_category, $expense_date, $receipt_url);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Expense added successfully!']);
} else {
    // This will now give a more specific error if something is wrong
    echo json_encode(['success' => false, 'message' => 'Error adding expense: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

