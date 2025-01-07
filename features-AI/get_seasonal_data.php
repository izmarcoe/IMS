<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT 
        YEAR(s.sale_date) as year,
        MONTH(s.sale_date) as month,
        SUM(s.quantity) as total_sales
    FROM sales s
    GROUP BY YEAR(s.sale_date), MONTH(s.sale_date)
    ORDER BY YEAR(s.sale_date), MONTH(s.sale_date)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}