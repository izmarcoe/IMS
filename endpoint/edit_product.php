<?php
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("UPDATE products SET 
            product_name = :product_name,
            category = :category,
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

        // Fetch the updated product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $_POST['product_id']]);
        $updatedProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $updatedProduct
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating product: ' . $e->getMessage()
        ]);
    }
}
?>
