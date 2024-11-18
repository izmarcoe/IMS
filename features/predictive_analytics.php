<?php
include '../conn/conn.php';

header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "SELECT sale_date, quantity FROM sales ORDER BY sale_date";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sale_date = strtotime($row['sale_date']);
        if ($sale_date === false) {
            continue;
        }
        
        $quantity = filter_var($row['quantity'], FILTER_VALIDATE_INT);
        if ($quantity === false) {
            continue;
        }
        
        $data[] = array(
            'sale_date' => date('Y-m-d', $sale_date),
            'quantity' => $quantity
        );
    }

    if (empty($data)) {
        throw new Exception('No valid data found');
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn = null;
?>