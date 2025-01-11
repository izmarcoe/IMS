<?php
session_start();
include('../conn/conn.php');

// Initialize variables
$archivedSales = [];
$error = null;

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) FROM archive_sales");
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);

    // Get archived sales with proper joins - fixed alias syntax
    $stmt = $conn->prepare("
        SELECT 
            ars.id,
            ars.product_id,
            ars.quantity,
            ars.price,
            ars.sale_date,
            ars.archived_date,
            p.product_name,
            pc.category_name,
            (ars.quantity * ars.price) as total_price
        FROM archive_sales ars
        LEFT JOIN products p ON ars.product_id = p.product_id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        ORDER BY ars.archived_date DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $archivedSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = $e->getMessage();
    error_log("Database Error: " . $error);
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
    <title>Archived Sales</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <?php include '../features/header.php' ?>

    <!-- Add error display after the header -->
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Archived Sales</h2>
                <a href="manage_sales.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Sales
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Sales ID</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Total Sales</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Sale Date</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <!-- Add empty state handling in the table body -->
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($archivedSales)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    No archived sales found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($archivedSales as $sale): ?>
                                <tr id="archived-sale-<?php echo $sale['id']; ?>">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($sale['id']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($sale['category_name'] ?? 'No Category'); ?></td>
                                    <td class="px-6 py-4">₱<?php echo number_format($sale['price'], 2); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td class="px-6 py-4">₱<?php echo number_format($sale['total_price'], 2); ?></td>
                                    <td class="px-6 py-4"><?php echo date('F j, Y', strtotime($sale['sale_date'])); ?></td>
                                    <td class="px-6 py-4">
                                        <button onclick="restoreSale(<?php echo $sale['id']; ?>)"
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md">
                                            Restore
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        function restoreSale(saleId) {
            Swal.fire({
                title: 'Restore Sale?',
                text: 'This will move the sale back to active sales.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../endpoint/restore-sale.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `sale_id=${saleId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Restored!', 'Sale has been restored.', 'success')
                            .then(() => {
                                document.getElementById(`archived-sale-${saleId}`).remove();
                            });
                        } else {
                            throw new Error(data.error || 'Failed to restore sale');
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