<?php
session_start();
include('../conn/conn.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    try {
        // Prepare the delete statement
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Set a session notification and redirect back to the products page
        $_SESSION['notification'] = "Product deleted successfully.";
        header("Location: ../features/manage_products.php");
        exit();
    } catch (PDOException $e) {
        // If there's an error, store it in the session and redirect
        $_SESSION['notification'] = "Error deleting product: " . $e->getMessage();
        header("Location:  ../features/manage_products.php");
        exit();
    }
} else {
    // Redirect if accessed improperly
    header("Location:  ../features/manage_products.php");
    exit();
}
