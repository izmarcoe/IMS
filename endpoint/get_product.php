<?php
session_start();
include('../conn/conn.php');

// Set header to return JSON
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
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
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.category_id,
            p.price,
            p.quantity,
            pc.category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.product_id = :id
    ");
    
    $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product === false) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Product not found'
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'product' => $product
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>