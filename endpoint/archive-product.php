<?php
session_start();
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = $_POST['product_id'];
    
    $conn->beginTransaction();

    // Get product data with category
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            COALESCE(pc.category_name, 'No Category') as category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id 
        WHERE p.product_id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Insert into archive_products
    $archiveStmt = $conn->prepare("
        INSERT INTO archive_products 
        (product_id, product_name, price, quantity, category_id, category_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $archiveStmt->execute([
        $product['product_id'],
        $product['product_name'],
        $product['price'],
        $product['quantity'],
        $product['category_id'],
        $product['category_name']
    ]);

    // Delete from products
    $deleteStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $deleteStmt->execute([$productId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Archive Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>