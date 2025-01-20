<?php
include('../conn/conn.php');

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
        $stmt->execute([$_GET['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stock' => $result['quantity'] ?? 0
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>