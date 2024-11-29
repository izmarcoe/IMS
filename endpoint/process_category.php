<?php
session_start();
include('../conn/conn.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
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

            echo json_encode([
                'status' => 'success', 
                'message' => 'Category added successfully',
                'category' => [
                    'id' => $conn->lastInsertId(),
                    'category_name' => $category_name,
                    'description' => $description
                ]
            ]);
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

            echo json_encode([
                'status' => 'success', 
                'message' => 'Category updated successfully',
                'category' => [
                    'id' => $id,
                    'category_name' => $category_name,
                    'description' => $description
                ]
            ]);
        }
        elseif ($action == 'delete') {
            $id = $_POST['id'];

            $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'status' => 'success', 
                'message' => 'Category deleted successfully',
                'id' => $id
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Error: " . $e->getMessage()
        ]);
    }
    exit();
}
?>