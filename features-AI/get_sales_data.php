<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Modified query to ensure we get complete data
    $sql = "SELECT s.id, s.product_id, p.product_name, s.sale_date, s.quantity 
            FROM sales s
            JOIN products p ON s.product_id = p.product_id
            WHERE s.quantity > 0
            ORDER BY s.product_id, s.sale_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug output
    error_log('Sales data count: ' . count($data));
    
    echo json_encode($data);

} catch (Exception $e) {
    error_log('Sales API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}