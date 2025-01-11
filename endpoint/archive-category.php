<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['category_id'])) {
        throw new Exception('Category ID is required');
    }

    $categoryId = $_POST['category_id'];
    
    // Start transaction
    $conn->beginTransaction();

    // Get category data first
    $stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception('Category not found');
    }

    // Get all products in this category
    $productStmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
    $productStmt->execute([$categoryId]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    // Archive all products in this category
    foreach ($products as $product) {
        // Insert into archive_products
        $archiveProductStmt = $conn->prepare("
            INSERT INTO archive_products (
                product_id, product_name, price, quantity, category_id
            ) VALUES (
                :id, :name, :price, :quantity, :category_id
            )
        ");
        $archiveProductStmt->execute([
            'id' => $product['product_id'],
            'name' => $product['product_name'],
            'price' => $product['price'],
            'quantity' => $product['quantity'],
            'category_id' => $product['category_id']
        ]);

        // Delete from products
        $deleteProductStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $deleteProductStmt->execute([$product['product_id']]);
    }

    // Archive category
    $archiveCategoryStmt = $conn->prepare("
        INSERT INTO archive_categories (
            id, category_name, description
        ) VALUES (
            :id, :category_name, :description
        )
    ");
    $archiveCategoryStmt->execute([
        'id' => $category['id'],
        'category_name' => $category['category_name'],
        'description' => $category['description']
    ]);

    // Delete original category
    $deleteCategoryStmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
    $deleteCategoryStmt->execute([$categoryId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Category and related products archived successfully',
        'archived_products_count' => count($products)
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}