<?php
// --- DEBUGGING CODE START ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- DEBUGGING CODE END ---

session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? $_GET['month'] : date('n');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 1. Category Spending
$category_spending = [];
$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ? GROUP BY category");
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$stmt->bind_result($category, $total);
while ($stmt->fetch()) {
    $category_spending[] = ['category' => $category, 'total' => $total];
}
$stmt->close();

// 2. Budgets vs Actual
$budget_data = [];
$stmt = $conn->prepare("SELECT b.category, b.amount as budget, COALESCE(e.total_spent, 0) as spent FROM budgets b LEFT JOIN (SELECT category, SUM(amount) as total_spent FROM expenses WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ? GROUP BY category) e ON b.category = e.category WHERE b.user_id = ? AND b.month = ? AND b.year = ?");
$stmt->bind_param("iiiiii", $user_id, $month, $year, $user_id, $month, $year);
$stmt->execute();
$stmt->bind_result($category, $budget, $spent);
while ($stmt->fetch()) {
    $budget_data[] = ['category' => $category, 'budget' => $budget, 'spent' => $spent];
}
$stmt->close();

// --- THIS IS THE FIX ---
// 3. Expense Prediction (Query rewritten for ONLY_FULL_GROUP_BY compatibility)
$monthly_totals_raw = [];
$stmt = $conn->prepare("
    SELECT YEAR(expense_date) as expense_year, MONTH(expense_date) as expense_month, SUM(amount) as total
    FROM expenses WHERE user_id = ?
    GROUP BY expense_year, expense_month
    ORDER BY expense_year DESC, expense_month DESC
    LIMIT 12
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($expense_year, $expense_month, $total);
while ($stmt->fetch()) {
    $monthly_totals_raw[] = ['year' => $expense_year, 'month' => $expense_month, 'total' => $total];
}
$stmt->close();

// Reverse the array to be in chronological order for calculation
$monthly_totals_raw = array_reverse($monthly_totals_raw);

// Manually create the month_index for prediction calculation
$monthly_totals = [];
if (!empty($monthly_totals_raw)) {
    $first_month = new DateTime($monthly_totals_raw[0]['year'] . '-' . $monthly_totals_raw[0]['month'] . '-01');
    foreach ($monthly_totals_raw as $row) {
        $current_month = new DateTime($row['year'] . '-' . $row['month'] . '-01');
        $interval = $first_month->diff($current_month);
        $month_index = $interval->y * 12 + $interval->m;
        $monthly_totals[] = ['month_index' => $month_index, 'total' => $row['total']];
    }
}

$prediction = 0;
$trend_text = "Not enough data to determine a trend.";
if (count($monthly_totals) > 2) {
    $n = count($monthly_totals); $sum_x = 0; $sum_y = 0; $sum_xy = 0; $sum_x2 = 0;
    foreach($monthly_totals as $row) { $x = (int)$row['month_index']; $y = (float)$row['total']; $sum_x += $x; $sum_y += $y; $sum_xy += $x * $y; $sum_x2 += $x * $x; }
    $denominator = ($n * $sum_x2 - $sum_x * $sum_x);
    if ($denominator != 0) {
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / $denominator;
        $intercept = ($sum_y - $slope * $sum_x) / $n;
        if (!empty($monthly_totals)) {
            $last_month_index = end($monthly_totals)['month_index'];
            $prediction = $slope * ($last_month_index + 1) + $intercept;
        }
        if ($prediction < 0) $prediction = 0;
        if ($slope > 500) $trend_text = "Spending is trending up."; elseif ($slope < -500) $trend_text = "Spending is trending down."; else $trend_text = "Spending is stable.";
    }
} elseif (!empty($monthly_totals)) {
    $total = 0; foreach($monthly_totals as $row) { $total += $row['total']; }
    $prediction = $total / count($monthly_totals);
    $trend_text = "Based on monthly average.";
}

// 4. Insights
$insights = ['largest_purchase' => null, 'top_category' => null];
$stmt = $conn->prepare("SELECT description, amount FROM expenses WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ? ORDER BY amount DESC LIMIT 1");
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$stmt->bind_result($description, $amount);
if ($stmt->fetch()) {
    $insights['largest_purchase'] = ['description' => $description, 'amount' => $amount];
}
$stmt->close();
if (!empty($category_spending)) {
    $insights['top_category'] = array_reduce($category_spending, function($a, $b) {
        if ($a === null) return $b;
        return $a['total'] > $b['total'] ? $a : $b;
    });
}

echo json_encode([
    'success' => true,
    'categorySpending' => $category_spending,
    'budgetStatus' => $budget_data,
    'spendingPrediction' => ['amount' => round($prediction, 2), 'trend' => $trend_text],
    'insights' => $insights
]);

$conn->close();
?>

