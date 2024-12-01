<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

// Fetch categories for the dropdown
$categoriesStmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user roles for the header
$rolesStmt = $conn->prepare("SELECT user_id, role FROM login_db"); // Changed variable name
$rolesStmt->execute();
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC); // Store in different variable

$role = $_SESSION['user_role'];

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%$search%";

// Sorting logic
$sort = $_GET['sort'] ?? 'product_name'; // default sort column
$order = $_GET['order'] ?? 'asc'; // default sort order

// Pagination
$productsPerPage = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $productsPerPage; // Offset for SQL query

// Get total number of products with search
$totalProductsQuery = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_name LIKE :search");
$totalProductsQuery->bindParam(':search', $searchParam, PDO::PARAM_STR);
$totalProductsQuery->execute();
$totalProducts = $totalProductsQuery->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch products with limit, offset, search, and sorting
$stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.quantity,
        p.category_id,
        pc.category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE p.product_name LIKE :search
    ORDER BY $sort $order
    LIMIT :offset, :limit
");
$stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .pagination .page-link {
            color: #0F7505;
        }

        .pagination .page-link:hover {
            background-color: #0F7505;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: #0F7505;
            border-color: #0F7505;
        }

        .pagination .page-link:focus {
            box-shadow: none;
        }
    </style>
</head>

<body class="bg-[#DADBDF] h-screen overflow-hidden">
    <!-- Header -->
    <header class="flex flex-row">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold">Admin Dashboard</h2>
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
        <div class="container mt-3 p-4">
            <h2 class="text-2xl font-bold mb-4">Manage Products</h2>

            <?php if (isset($_SESSION['notification'])): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" id="notification">
                    <?php
                    echo $_SESSION['notification'];
                    unset($_SESSION['notification']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="mb-4">
                <div class="flex gap-2">
                    <input type="text" name="search" placeholder="Search by Product Name"
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-[300px] border border-gray-300 rounded px-3 py-2">
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" type="submit">Search</button>
                    <a href="manage_products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Clear</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse bg-white shadow-sm rounded-lg text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-3 py-3 text-left">
                                <div class="flex items-center gap-1">
                                    Product Name
                                    <div class="flex flex-col text-xs text-gray-400 ml-1">
                                        <a href="?sort=product_name&order=asc" class="hover:text-black">
                                            <i class="fas fa-caret-up"></i>
                                        </a>
                                        <a href="?sort=product_name&order=desc" class="hover:text-black" style="margin-top:-3px;">
                                            <i class="fas fa-caret-down"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left">
                                <div class="flex items-center gap-1">
                                    Category
                                    <div class="flex flex-col text-xs text-gray-400 ml-1">
                                        <a href="?sort=category_name&order=asc" class="hover:text-black">
                                            <i class="fas fa-caret-up"></i>
                                        </a>
                                        <a href="?sort=category_name&order=desc" class="hover:text-black" style="margin-top:-3px;">
                                            <i class="fas fa-caret-down"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left">
                                <div class="flex items-center gap-1">
                                    Price
                                    <div class="flex flex-col text-xs text-gray-400 ml-1">
                                        <a href="?sort=price&order=asc" class="hover:text-black">
                                            <i class="fas fa-caret-up"></i>
                                        </a>
                                        <a href="?sort=price&order=desc" class="hover:text-black" style="margin-top:-3px;">
                                            <i class="fas fa-caret-down"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left">
                                <div class="flex items-center gap-1">
                                    Quantity
                                    <div class="flex flex-col text-xs text-gray-400 ml-1">
                                        <a href="?sort=quantity&order=asc" class="hover:text-black">
                                            <i class="fas fa-caret-up"></i>
                                        </a>
                                        <a href="?sort=quantity&order=desc" class="hover:text-black" style="margin-top:-3px;">
                                            <i class="fas fa-caret-down"></i>
                                        </a>
                                    </div>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="px-3 py-2 text-center">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr class="border-t hover:bg-gray-50" data-product-id="<?php echo $product['product_id']; ?>">
                                    <td class="px-3 py-4"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td class="px-3 py-4"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                    <td class="px-3 py-4"><?php echo htmlspecialchars($product['price']); ?></td>
                                    <td class="px-3 py-4"><?php echo htmlspecialchars($product['quantity']); ?></td>
                                    <td class="px-3 py-4">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-xs">
                                            Edit
                                        </button>
                                        <button onclick="openDeleteModal(<?php echo $product['product_id']; ?>)"
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
    </main>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Product</h3>
                <form id="editProductForm" method="POST" action="../endpoint/edit_product.php">
                    <input type="hidden" name="product_id" id="editProductId">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editProductName">Product Name</label>
                        <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="editProductName" name="product_name" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editCategory">Category</label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="editCategory" name="category" required>
                            <option value="" disabled selected>Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editPrice">Price</label>
                        <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="editPrice" name="price" min="1" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editQuantity">Quantity</label>
                        <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="editQuantity" name="quantity" min="1" required>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Confirm Action</h3>
            <p class="mb-4">Are you sure you want to delete this item?</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <script src="../JS/time.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/manage_products.js"></script>
</body>

</html>