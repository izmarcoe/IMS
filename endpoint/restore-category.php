<?php
include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['category_id'])) {
        throw new Exception('Category ID is required');
    }

    $categoryId = $_POST['category_id'];
    $conn->beginTransaction();

    // 1. Get all data first
    $categoryStmt = $conn->prepare("SELECT * FROM archive_categories WHERE id = ?");
    $categoryStmt->execute([$categoryId]);
    $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception('Archived category not found');
    }

    $productStmt = $conn->prepare("SELECT * FROM archive_products WHERE category_id = ?");
    $productStmt->execute([$categoryId]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Delete products from archive first
    if (!empty($products)) {
        $deleteProductsStmt = $conn->prepare("DELETE FROM archive_products WHERE category_id = ?");
        $deleteProductsStmt->execute([$categoryId]);
    }

    // 3. Delete category from archive
    $deleteCategoryStmt = $conn->prepare("DELETE FROM archive_categories WHERE id = ?");
    $deleteCategoryStmt->execute([$categoryId]);

    // 4. Restore category
    $restoreCategoryStmt = $conn->prepare("
        INSERT INTO product_categories (id, category_name, description)
        VALUES (:id, :category_name, :description)
    ");
    $restoreCategoryStmt->execute([
        'id' => $category['id'],
        'category_name' => $category['category_name'],
        'description' => $category['description']
    ]);

    // 5. Restore products
    if (!empty($products)) {
        $restoreProductStmt = $conn->prepare("
            INSERT INTO products (product_id, product_name, price, quantity, category_id)
            VALUES (:id, :name, :price, :quantity, :category_id)
        ");

        foreach ($products as $product) {
            $restoreProductStmt->execute([
                'id' => $product['product_id'],
                'name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $product['quantity'],
                'category_id' => $product['category_id']
            ]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}