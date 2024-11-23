<?php
include '../conn/conn.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "SELECT product_id, product_name, quantity, price FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = array(
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'], 
            'price' => $row['price']  // Make sure this line is added

        );
    }

    if (empty($data)) {
        throw new Exception('No valid data found');
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error in get_inventory_data.php: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
