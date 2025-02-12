<?php
session_start();
include('../conn/conn.php');

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) FROM archive_products");
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);

    // Update query to use stored category_name
    $stmt = $conn->prepare("
        SELECT 
            ap.*,
            COALESCE(ap.category_name, pc.category_name, 'No Category') as category_name
        FROM archive_products ap
        LEFT JOIN product_categories pc ON ap.category_id = pc.id
        ORDER BY ap.archived_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $archivedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Failed to fetch archived products";
}

// Pagination
$productsPerPage = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $productsPerPage; // Offset for SQL query

$fname = $_SESSION['Fname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Products</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/roleMonitor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <?php include '../features/header.php' ?>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Archived Products</h2>
                <a href="manage_products.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Products
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($archivedProducts as $product): ?>
                            <tr id="archived-product-<?php echo $product['product_id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['price']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($product['quantity']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="restoreProduct(<?php echo $product['product_id']; ?>)"
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md">
                                        Restore
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                 <!-- Pagination -->
                 <div class="flex justify-center items-center mt-4 space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            First
                        </a>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    // Calculate the range of page numbers to display
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Next
                        </a>
                        <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Last
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function restoreProduct(productId) {
            Swal.fire({
                title: 'Restore Product?',
                text: 'This will move the product back to active products.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../endpoint/restore-product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Restored!', 'Product has been restored.', 'success')
                            .then(() => {
                                document.getElementById(`archived-product-${productId}`).remove();
                            });
                        } else {
                            throw new Error(data.error || 'Failed to restore product');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', error.message, 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>