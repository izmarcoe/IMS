<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

try {
    $product_id = $_POST['product_id'];
    $requested_quantity = $_POST['quantity'];

    $stmt = $conn->prepare("SELECT quantity, product_name FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    $available = $product['quantity'];
    $isAvailable = $available >= $requested_quantity;

    echo json_encode([
        'success' => true,
        'available' => $available,
        'isAvailable' => $isAvailable,
        'message' => $isAvailable ? 
            'Stock available' : 
            "Insufficient stock for {$product['product_name']}. Available: {$available}"
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}