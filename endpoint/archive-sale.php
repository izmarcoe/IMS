<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['sale_id'])) {
        throw new Exception('Sale ID is required');
    }

    $saleId = $_POST['sale_id'];
    $conn->beginTransaction();

    // Get sale data
    $stmt = $conn->prepare("
        SELECT s.*, p.product_name, pc.category_name, (s.quantity * s.price) as total_sales
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE s.id = ?
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sale not found');
    }

    // Insert into archive_sales
    $archiveStmt = $conn->prepare("
        INSERT INTO archive_sales 
        (id, product_id, product_name, category_name, quantity, price, sale_date, total_sales)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $archiveStmt->execute([
        $sale['id'],
        $sale['product_id'],
        $sale['product_name'],
        $sale['category_name'],
        $sale['quantity'],
        $sale['price'],
        $sale['sale_date'],
        $sale['total_sales']
    ]);

    // Delete from sales
    $deleteStmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $deleteStmt->execute([$saleId]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Sale archived successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>