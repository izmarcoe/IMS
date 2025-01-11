<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['sale_id'])) {
        throw new Exception('Sale ID is required');
    }

    $saleId = $_POST['sale_id'];
    $conn->beginTransaction();

    // Get archived sale
    $stmt = $conn->prepare("SELECT * FROM archive_sales WHERE id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Archived sale not found');
    }

    // Restore to sales table
    $restoreStmt = $conn->prepare("
        INSERT INTO sales 
        (id, product_id, product_name, quantity, price, sale_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $restoreStmt->execute([
        $sale['id'],
        $sale['product_id'],
        $sale['product_name'],
        $sale['quantity'],
        $sale['price'],
        $sale['sale_date']
    ]);

    // Delete from archive
    $deleteStmt = $conn->prepare("DELETE FROM archive_sales WHERE id = ?");
    $deleteStmt->execute([$saleId]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Sale restored successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>