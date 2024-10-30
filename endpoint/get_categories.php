<?php
include('../conn/conn.php');
error_reporting(E_ALL); // Enable error reporting
ini_set('display_errors', 1); // Display errors

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
