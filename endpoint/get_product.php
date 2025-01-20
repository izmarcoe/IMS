<?php
session_start();
include('../conn/conn.php');

// Set header to return JSON
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin') {
    http_response_code(403);
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'No product ID provided'
    ]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT product_id, product_name, price, quantity FROM products WHERE product_id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Product not found'
        ]);
        exit();
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>