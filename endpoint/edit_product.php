<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("UPDATE products SET 
            product_name = :product_name,
            category_id = :category, 
            price = :price,
            quantity = :quantity
            WHERE product_id = :product_id");

        $stmt->execute([
            ':product_name' => $_POST['product_name'],
            ':category' => $_POST['category'],
            ':price' => $_POST['price'],
            ':quantity' => $_POST['quantity'],
            ':product_id' => $_POST['product_id']
        ]);

        // Get updated category name
        $categoryStmt = $conn->prepare("SELECT category_name FROM product_categories WHERE id = :category_id");
        $categoryStmt->execute([':category_id' => $_POST['category']]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'category_name' => $category['category_name']
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error updating product: ' . $e->getMessage()
        ]);
    }
    exit();
}
?>