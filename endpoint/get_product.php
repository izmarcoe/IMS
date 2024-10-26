<?php
include('../conn/conn.php'); // Database connection file

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Product not found.']);
    }
}