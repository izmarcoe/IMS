<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

try {
    error_log('Request received: ' . print_r($_POST, true));
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get current product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$_POST['product_id']]);
    $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentProduct) {
        throw new Exception('Product not found');
    }

    $stmt = $conn->prepare("
        INSERT INTO product_modification_requests 
        (product_id, employee_id, old_name, new_name, old_price, new_price, old_quantity, new_quantity) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $_POST['product_id'],
        $_SESSION['user_id'],
        $currentProduct['product_name'],
        $_POST['new_name'],
        $currentProduct['price'],
        $_POST['new_price'],
        $currentProduct['quantity'],
        $_POST['new_quantity']
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Modification request submitted successfully'
        ]);
    } else {
        throw new Exception('Failed to submit request');
    }

} catch (Exception $e) {
    error_log('Error in create_product_request: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}