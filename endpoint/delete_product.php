<?php
header('Content-Type: application/json');
session_start();
include('../conn/conn.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    try {
        // Prepare the delete statement
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Return JSON response instead of redirecting
        echo json_encode([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error deleting product: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
