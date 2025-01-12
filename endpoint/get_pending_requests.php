<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT r.*, p.product_name as old_name, p.price as old_price, p.quantity as old_quantity,
               u.Fname, u.Lname
        FROM product_modification_requests r
        JOIN products p ON r.product_id = p.product_id
        JOIN login_db u ON r.employee_id = u.user_id
        WHERE r.status = 'pending'
        ORDER BY r.request_date DESC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}