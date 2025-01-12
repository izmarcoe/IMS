<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = $_POST['product_id'];
$requested_quantity = $_POST['quantity'];
$original_quantity = isset($_POST['original_quantity']) ? $_POST['original_quantity'] : 0;

try {
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Add original quantity back to available stock for validation
    $available_stock = $product['quantity'] + $original_quantity;
    $is_available = $requested_quantity <= $available_stock;

    echo json_encode([
        'success' => true,
        'isAvailable' => $is_available,
        'availableStock' => $available_stock
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>