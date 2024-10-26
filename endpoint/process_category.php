<?php
session_start();
include('../conn/conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    try {
        if ($action == 'add') {
            $category_name = trim($_POST['category_name']);
            $description = trim($_POST['description']);

            $stmt = $conn->prepare("INSERT INTO product_categories (category_name, description) VALUES (:name, :description)");
            $stmt->bindParam(':name', $category_name);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
        } 
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $category_name = trim($_POST['category_name']);
            $description = trim($_POST['description']);

            $stmt = $conn->prepare("UPDATE product_categories SET category_name = :name, description = :description WHERE id = :id");
            $stmt->bindParam(':name', $category_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header("Location: ../features/category.php");
exit();
?>