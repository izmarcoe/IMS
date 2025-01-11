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

    // Get archived category data
    $stmt = $conn->prepare("SELECT * FROM archive_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception('Archived category not found');
    }

    // Insert back into product_categories
    $restoreStmt = $conn->prepare("
        INSERT INTO product_categories (category_name, description)
        VALUES (:category_name, :description)
    ");
    $restoreStmt->execute([
        'category_name' => $category['category_name'],
        'description' => $category['description']
    ]);

    // Delete from archive_categories
    $deleteStmt = $conn->prepare("DELETE FROM archive_categories WHERE id = ?");
    $deleteStmt->execute([$categoryId]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}