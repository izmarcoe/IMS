<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

try {
    // Get and validate input
    $sale_id = filter_input(INPUT_POST, 'sale_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $sale_date = $_POST['sale_date'];

    if (!$sale_id || !$quantity || !$sale_date) {
        throw new Exception('Invalid input data');
    }

    // Start transaction
    $conn->beginTransaction();

    // Get current sale info
    $stmt = $conn->prepare("SELECT product_id, quantity as old_quantity FROM sales WHERE id = :sale_id");
    $stmt->bindParam(':sale_id', $sale_id);
    $stmt->execute();
    $currentSale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentSale) {
        throw new Exception('Sale not found');
    }

    // Calculate quantity difference
    $quantity_difference = $quantity - $currentSale['old_quantity'];

    // Update product stock
    if ($quantity_difference != 0) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET quantity = quantity - :quantity_difference 
            WHERE product_id = :product_id");
        $stmt->bindParam(':quantity_difference', $quantity_difference);
        $stmt->bindParam(':product_id', $currentSale['product_id']);
        $stmt->execute();
    }

    // Update the sale record
    $stmt = $conn->prepare("
        UPDATE sales 
        SET quantity = :quantity,
            sale_date = :sale_date,
            total_sales = price * :quantity
        WHERE id = :sale_id");

    $stmt->bindParam(':sale_id', $sale_id);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':sale_date', $sale_date);
    
    $stmt->execute();
    $conn->commit();

    // Fetch updated sale data
    $stmt = $conn->prepare("
        SELECT s.*, pc.category_name
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE s.id = :sale_id");
    $stmt->bindParam(':sale_id', $sale_id);
    $stmt->execute();
    $updatedSale = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Sale updated successfully',
        'sale' => $updatedSale
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}