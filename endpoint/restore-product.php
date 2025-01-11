<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = $_POST['product_id'];
    $conn->beginTransaction();

    // Get archived product data
    $stmt = $conn->prepare("SELECT * FROM archive_products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Archived product not found');
    }

    // Check if category is active
    $categoryStmt = $conn->prepare("SELECT id FROM product_categories WHERE id = ?");
    $categoryStmt->execute([$product['category_id']]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        throw new Exception('Cannot restore product: Category is archived. Please restore the category first.');
    }

    // Proceed with restore if category exists
    $restoreStmt = $conn->prepare("
        INSERT INTO products (product_id, product_name, price, quantity, category_id)
        VALUES (:id, :name, :price, :quantity, :category_id)
    ");
    
    $restoreStmt->execute([
        'id' => $product['product_id'],
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
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}