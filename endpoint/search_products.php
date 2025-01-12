<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$searchTerm = $_GET['term'] ?? '';

try {
    $stmt = $conn->prepare("
        SELECT product_id, product_name, price, quantity 
        FROM products 
        WHERE product_name LIKE :term 
        AND quantity > 0
        ORDER BY product_name
        LIMIT 10
    ");
    
    $stmt->execute(['term' => "%$searchTerm%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'products' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>