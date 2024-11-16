<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Check if the sale ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $saleId = $_POST['id'];

    // Delete the sale record
    $deleteStmt = $conn->prepare("DELETE FROM sales WHERE id = :id");
    $deleteStmt->bindParam(':id', $saleId, PDO::PARAM_INT);
    $deleteStmt->execute();

    $_SESSION['notification'] = "Sale deleted successfully!";
    header("Location: ../features/manage_sales.php");
    exit();
} else {
    header("Location: manage_sales.php");
    exit();
}
?>
