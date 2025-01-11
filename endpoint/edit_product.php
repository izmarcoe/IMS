<?php
header('Content-Type: application/json');
include('../conn/conn.php');

try {
    // Validate inputs
    if (empty($_POST['product_id']) || empty($_POST['product_name']) || 
        empty($_POST['category_id']) || empty($_POST['price']) || 
        empty($_POST['quantity'])) {
        throw new Exception('All fields are required');
    }

    $conn->beginTransaction();

    // Update product
    $stmt = $conn->prepare("
        UPDATE products 
        SET product_name = :name,
            category_id = :category_id,
            price = :price,
            quantity = :quantity
        WHERE product_id = :id
    ");

    $stmt->execute([
        'name' => $_POST['product_name'],
        'category_id' => $_POST['category_id'],
        'price' => $_POST['price'],
        'quantity' => $_POST['quantity'],
        'id' => $_POST['product_id']
    ]);

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully'
    ]);

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