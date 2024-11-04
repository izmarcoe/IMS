<?php
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

        $_SESSION['notification'] = 'Product updated successfully';
        header("Location: ../features/manage_products.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['notification'] = 'Error updating product: ' . $e->getMessage();
        header("Location: ../features/manage_products.php");
        exit();
    }
}
?>