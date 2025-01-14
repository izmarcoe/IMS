<?php
session_start();
include('../conn/conn.php');
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $conn->beginTransaction();

    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    // Get request details
    $stmt = $conn->prepare("
        SELECT * FROM product_modification_requests 
        WHERE request_id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Request not found');
    }

    
    // Validate quantity range (1-999)
    if ($action === 'approve' && ($request['new_quantity'] < 1 || $request['new_quantity'] > 999)) {
        throw new Exception('Quantity must be between 1 and 999');
    }

    if ($action === 'approve') {
        // Update product
        $updateStmt = $conn->prepare("
            UPDATE products 
            SET product_name = ?, price = ?, quantity = ?
            WHERE product_id = ?
        ");
        $updateStmt->execute([
            $request['new_name'],
            $request['new_price'],
            $request['new_quantity'],
            $request['product_id']
        ]);
    }

    // Update request status without response_date
    $statusStmt = $conn->prepare("
        UPDATE product_modification_requests 
        SET status = ?
        WHERE request_id = ?
    ");
    $statusStmt->execute([
        $action === 'approve' ? 'approved' : 'declined',
        $requestId
    ]);

    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}