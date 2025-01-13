<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

$user_role = $_SESSION['user_role'];

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%$search%";

// Sorting logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$orderBy = '';
switch ($sort) {
    case 'category_asc':
        $orderBy = 'pc.category_name ASC';
        break;
    case 'category_desc':
        $orderBy = 'pc.category_name DESC';
        break;
    case 'price_asc':
        $orderBy = 's.price ASC';
        break;
    case 'price_desc':
        $orderBy = 's.price DESC';
        break;
    case 'name_asc':
        $orderBy = 's.product_name ASC';
        break;
    case 'name_desc':
        $orderBy = 's.product_name DESC';
        break;
    case 'sales_asc':
        $orderBy = 'total_sales ASC';
        break;
    case 'sales_desc':
        $orderBy = 'total_sales DESC';
        break;
    default:
        $orderBy = 's.id DESC';
        break;
}

// Handle sales deletion
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];

        // Start transaction
        $conn->beginTransaction();

        // Get sale details before deletion
        $stmt = $conn->prepare("SELECT product_id, quantity FROM sales WHERE id = :id");
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            // Update product quantity (add back the sold items)
            $updateStmt = $conn->prepare("UPDATE products SET quantity = quantity + :qty WHERE product_id = :pid");
            $updateStmt->bindParam(':qty', $sale['quantity'], PDO::PARAM_INT);
            $updateStmt->bindParam(':pid', $sale['product_id'], PDO::PARAM_INT);
            $updateStmt->execute();

            // Delete the sale record
            $deleteStmt = $conn->prepare("DELETE FROM sales WHERE id = :id");
            $deleteStmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
            $deleteStmt->execute();

            // Commit transaction
            $conn->commit();

            $_SESSION['success_message'] = "Sale deleted and product quantity restored.";
        }

        header("Location: manage_sales.php");
        exit();
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: manage_sales.php");
        exit();
    }
}

// Pagination
$productsPerPage = 10; // Number of sales records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $productsPerPage; // Offset for SQL query

// Get total number of sales (with optional search filter)
$totalSalesQuery = $conn->prepare("SELECT COUNT(*) FROM sales WHERE product_name LIKE :search");
$totalSalesQuery->bindValue(':search', "%$search%", PDO::PARAM_STR);
$totalSalesQuery->execute();
$totalSales = $totalSalesQuery->fetchColumn();
$totalPages = ceil($totalSales / $productsPerPage);

// Fetch sales data with limit and offset (with optional search filter)
$stmt = $conn->prepare("
    SELECT 
        s.*,
        COALESCE(s.category_name, pc.category_name, ac.category_name) as category_name
    FROM 
        sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        LEFT JOIN archive_categories ac ON p.category_id = ac.id
    WHERE 
        s.product_name LIKE :search
    ORDER BY 
        $orderBy
    LIMIT :offset, :limit
");
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);


$fname = $_SESSION['Fname'];
$lname = $_SESSION['Lname'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sales</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body style="background-color: #DADBDF;">
    <!-- Header -->
    <?php include '../features/header.php' ?>

    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <div class="container mt-3 p-4 mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Manage Sales</h2>
                    <div class="flex space-x-4">
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="archive-sales-table.php" class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-archive mr-2"></i>View Archived Sales
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['notification'])): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" id="notification">
                        <?php
                        echo $_SESSION['notification'];
                        unset($_SESSION['notification']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <form method="GET" class="mb-4">
                    <div class="flex gap-2">
                        <input type="text" name="search" placeholder="Search by Product Name"
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-[300px] border border-gray-300 rounded px-3 py-2">
                        <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" type="submit">Search</button>
                        <a href="manage_sales.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Clear</a>
                    </div>
                </form>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse bg-white shadow-sm rounded-lg text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Product Name
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=name_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=name_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Category
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=category_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=category_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Price
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=price_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=price_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Quantity
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=quantity_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=quantity_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Total Sales
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=sales_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=sales_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex items-center gap-1">
                                        Sale Date
                                        <div class="flex flex-col text-xs text-gray-400 ml-1">
                                            <a href="?sort=date_asc" class="hover:text-black">
                                                <i class="fas fa-caret-up"></i>
                                            </a>
                                            <a href="?sort=date_desc" class="hover:text-black" style="margin-top:-3px;">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-left">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="8" class="px-3 py-2 text-center">No sales records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr class="border-t hover:bg-gray-50" data-sale-id="<?php echo htmlspecialchars($sale['id']); ?>">
                                        <td class="px-3 py-4 product-name"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td class="px-3 py-4 category-name"><?php echo htmlspecialchars($sale['category_name'] ?? 'No Category'); ?></td>
                                        <td class="px-3 py-4 price"><?php echo htmlspecialchars($sale['price']); ?></td>
                                        <td class="px-3 py-4 quantity"><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                        <td class="px-3 py-4 total-sales"><?php echo htmlspecialchars($sale['total_sales']); ?></td>
                                        <td class="px-3 py-4 sale-date"><?php echo htmlspecialchars(date('F j, Y', strtotime($sale['sale_date']))); ?></td>
                                        <td class="px-3 py-4">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sale)); ?>)"
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-sm">
                                                Edit
                                            </button>
                                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <button onclick="archiveSale(<?php echo $sale['id']; ?>)"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-sm">
                                                    Archive
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

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

        <!-- Edit Sales Modal -->
        <div id="editSalesModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Sale</h3>
                    <form id="editSaleForm" method="POST" action="../endpoint/update_sale.php">
                        <input type="hidden" name="sale_id" id="editSaleId">
                        <input type="hidden" name="old_quantity" id="editOldQuantity">
                        <input type="hidden" name="product_id" id="editProductId">

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editProductName">Product</label>
                            <?php if ($user_role == 'admin'): ?>
                                <div class="relative">
                                    <input type="text" 
                                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                           id="editProductName" 
                                           name="product_name" 
                                           autocomplete="off"
                                           placeholder="Search product...">
                                    
                                    <div id="productDropdownContainer" class="hidden absolute z-50 w-full mt-1">
                                        <select id="editProductSelect" 
                                                class="w-full max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg" 
                                                size="5">
                                            <?php
                                            $stmt = $conn->prepare("SELECT product_id, product_name, price, quantity FROM products WHERE quantity > 0 ORDER BY product_name");
                                            $stmt->execute();
                                            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            foreach($products as $product): ?>
                                                <option value="<?php echo $product['product_id']; ?>"
                                                        data-id="<?php echo $product['product_id']; ?>"
                                                        data-price="<?php echo $product['price']; ?>"
                                                        data-stock="<?php echo $product['quantity']; ?>"
                                                        class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="text" 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                                       id="editProductName" 
                                       name="product_name" 
                                       readonly>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editPrice">Price</label>
                            <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                                id="editPrice" name="price" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editQuantity">Quantity</label>
                            <input type="number" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php if ($user_role == 'employee') echo 'bg-gray-100'; ?>"
                                   id="editQuantity" 
                                   name="quantity" 
                                   min="1" 
                                   required
                                   <?php if ($user_role == 'employee') echo 'readonly'; ?>>
                            <small id="stockInfo" class="text-gray-500"></small>
                            <div id="quantityError" class="text-red-500 text-sm hidden"></div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editTotalSales">Total Amount</label>
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                                id="editTotalSales" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editSaleDate">Sale Date</label>
                            <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="editSaleDate" name="sale_date" required>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Sale</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="../JS/manage_sales.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>
    <script>
        // Update the delete button code in manage_sales.php
        function archiveSale(saleId) {
            Swal.fire({
                title: 'Archive Sale?',
                text: 'This sale will be moved to archives.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../endpoint/archive-sale.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `sale_id=${saleId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Archived!', 'Sale has been archived.', 'success')
                            .then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.error || 'Failed to archive sale');
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