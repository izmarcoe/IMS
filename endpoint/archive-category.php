<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['category_id'])) {
        throw new Exception('Category ID is required');
    }

    $categoryId = $_POST['category_id'];
    $conn->beginTransaction();

    // 1. Get category data
    $categoryStmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
    $categoryStmt->execute([$categoryId]);
    $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception('Category not found');
    }

    // 2. Get products with this category
    $productStmt = $conn->prepare("SELECT * FROM products WHERE category_id = ?");
    $productStmt->execute([$categoryId]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Archive products first
    foreach ($products as $product) {
        // Insert into archive_products with category_name
        $archiveProductStmt = $conn->prepare("
            INSERT INTO archive_products 
            (product_id, product_name, price, quantity, category_id, category_name) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $archiveProductStmt->execute([
            $product['product_id'],
            $product['product_name'],
            $product['price'],
            $product['quantity'],
            $product['category_id'],
            $category['category_name']  // Store category name directly
        ]);

        // Delete from products
        $deleteProductStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $deleteProductStmt->execute([$product['product_id']]);
    }

    // 4. Archive category
    $archiveCategoryStmt = $conn->prepare("
        INSERT INTO archive_categories 
        (id, category_name, description) 
        VALUES (?, ?, ?)
    ");
    $archiveCategoryStmt->execute([
        $category['id'],
        $category['category_name'],
        $category['description']
    ]);

    // 5. Delete original category
    $deleteCategoryStmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
    $deleteCategoryStmt->execute([$categoryId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Category and related products archived',
        'productsArchived' => count($products)
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}