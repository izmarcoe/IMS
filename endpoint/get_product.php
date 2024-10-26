<!--THIS IS FOR add_product.php-->


<?php
session_start();
include('../conn/conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    http_response_code(403);
    exit('Unauthorized');
}

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.category_id,
            p.price,
            p.quantity,
            pc.category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.product_id = :id
    ");
    
    $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($product);
}