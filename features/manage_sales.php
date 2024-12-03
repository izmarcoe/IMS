<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

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

        $stmt = $conn->prepare("DELETE FROM sales WHERE id = :id");
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: manage_sales.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
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
    SELECT s.id, s.product_id, s.product_name, s.price, s.quantity, s.sale_date, (s.price * s.quantity) AS total_sales, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE s.product_name LIKE :search
    ORDER BY $orderBy
    LIMIT :offset, :limit
");
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <header class="flex flex-row">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="flex justify-end text-white mx-8">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-1"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <div class="container mt-3 p-4 mx-auto">
                <h2 class="text-2xl font-bold mb-4">Manage Sales</h2>

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
                                        Sales ID
                                    </div>
                                </th>
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
                                        <td class="px-3 py-4 sale-id"><?php echo htmlspecialchars($sale['id']); ?></td>
                                        <td class="px-3 py-4 product-name"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                        <td class="px-3 py-4 category-name"><?php echo htmlspecialchars($sale['category_name'] ?? 'No Category'); ?></td>
                                        <td class="px-3 py-4 price"><?php echo htmlspecialchars($sale['price']); ?></td>
                                        <td class="px-3 py-4 quantity"><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                        <td class="px-3 py-4 total-sales"><?php echo htmlspecialchars($sale['total_sales']); ?></td>
                                        <td class="px-3 py-4 sale-date"><?php echo htmlspecialchars(date('F j, Y', strtotime($sale['sale_date']))); ?></td>
                                        <td class="px-3 py-4">
                                            <button onclick='openEditModal(<?php echo json_encode($sale); ?>)'
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-xs">
                                                Edit
                                            </button>
                                            <button onclick="deleteSale(<?php echo $sale['id']; ?>)"
                                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-xs">
                                                Delete
                                            </button>
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
                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                                id="editProductName" name="product_name" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editPrice">Price</label>
                            <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                                id="editPrice" name="price" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editQuantity">Quantity</label>
                            <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="editQuantity" name="quantity" min="1" required>
                            <small id="stockInfo" class="text-gray-500"></small>
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

</body>

</html>