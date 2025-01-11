<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = $_POST['product_id'];
    $conn->beginTransaction();

    // Get archived product
    $stmt = $conn->prepare("SELECT * FROM archive_products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Archived product not found');
    }

    // Restore to products
    $restoreStmt = $conn->prepare("
        INSERT INTO products (product_name, price, quantity, category_id)
        VALUES (:name, :price, :quantity, :category_id)
    ");
    $restoreStmt->execute([
        'name' => $product['product_name'],
        'price' => $product['price'],
        'quantity' => $product['quantity'],
        'category_id' => $product['category_id']
    ]);

    // Delete from archive
    $deleteStmt = $conn->prepare("DELETE FROM archive_products WHERE product_id = ?");
    $deleteStmt->execute([$productId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}