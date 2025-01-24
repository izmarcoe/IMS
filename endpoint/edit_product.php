<?php
session_start();
header('Content-Type: application/json');
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        exit;
    }

    $updateFields = [];
    $params = [];

    // Only include fields that were sent and not empty
    if (!empty($_POST['product_name'])) {
        $updateFields[] = "product_name = ?";
        $params[] = $_POST['product_name'];
    }
    if (!empty($_POST['category_id'])) {
        $updateFields[] = "category_id = ?";
        $params[] = $_POST['category_id'];
    }
    if (!empty($_POST['price'])) {
        $updateFields[] = "price = ?";
        $params[] = $_POST['price'];
    }
    if (!empty($_POST['quantity'])) {
        $updateFields[] = "quantity = ?";
        $params[] = $_POST['quantity'];
    }

    if (!empty($_POST['additional_quantity'])) {
        $current_quantity = $_POST['current_quantity'] ?? 0;
        $additional_quantity = $_POST['additional_quantity'] ?? 0;
        $new_total = $current_quantity + $additional_quantity;
        
        if ($new_total > 999) {
            echo json_encode(['success' => false, 'error' => 'Total quantity cannot exceed 999']);
            exit;
        }
        
        $updateFields[] = "quantity = ?";
        $params[] = $new_total;
    }

    if (!empty($updateFields)) {
        $params[] = $product_id;
        $query = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE product_id = ?";
        
        try {
            $stmt = $conn->prepare($query);
            $result = $stmt->execute($params);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => true]); // No fields to update
    }
}