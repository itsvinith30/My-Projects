<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $conn->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // --- UNIVERSAL COMPATIBILITY FIX ---
        // Binds result columns to variables
        $stmt->bind_result($id, $name);
        $categories = [];
        // Fetches results one by one into the bound variables
        while($stmt->fetch()){
            $categories[] = ['id' => $id, 'name' => $name];
        }
        echo json_encode(['success' => true, 'categories' => $categories]);
        break;

    case 'POST':
        // Use isset() for broader compatibility
        $input = json_decode(file_get_contents('php://input'), true);
        $name = isset($input['name']) ? $input['name'] : '';
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category added.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add category.']);
        }
        break;

    case 'DELETE':
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category.']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        break;
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>