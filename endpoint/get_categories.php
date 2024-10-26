<!--THIS IS FOR CATEGORY.PHP-->


<?php
include('../conn/conn.php');

try {
    $stmt = $conn->prepare("SELECT * FROM product_categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($categories);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Failed to fetch categories: " . $e->getMessage()]);
}
?>