<?php
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
        SELECT p.*, pc.category_name 
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
        INSERT INTO archive_products (product_id, product_name, price, quantity, category_id)
        VALUES (:id, :name, :price, :quantity, :category_id)
    ");
    $archiveStmt->execute([
        'id' => $product['product_id'],
        'name' => $product['product_name'],
        'price' => $product['price'],
        'quantity' => $product['quantity'],
        'category_id' => $product['category_id']
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
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}