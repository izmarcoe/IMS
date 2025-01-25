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

$fname = $_SESSION['Fname'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/roleMonitor.js"></script>
</head>

<body class="bg-[#DADBDF] h-screen">
    <!-- Header -->
    <?php include '../features/header.php' ?>
    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <div class="container mt-3 p-4 mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-4xl sm:text-2xl font-bold">Manage Products</h2>
                    <div class="flex space-x-4">
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="archive-products-table.php" class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-archive mr-2"></i>View Archived Products
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

                <form method="GET" class="mb-4">
                    <div class="flex gap-2">
                        <input type="text" name="search" placeholder="Search by Product Name"
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-[300px] border border-gray-300 rounded px-3 py-2">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" type="submit">Search</button>
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
                                        <td class="px-3 py-4">₱<?php echo htmlspecialchars($product['price']); ?></td>
                                        <td class="px-3 py-4"><?php echo htmlspecialchars($product['quantity']); ?></td>
                                        <td class="px-3 py-4">
                                            <?php if ($_SESSION['user_role'] === 'employee'): ?>
                                                <button onclick="createModificationRequest(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md text-sm">
                                                    Request Edit
                                                </button>
                                            <?php else: ?>
                                                <div class="flex flex-row items-center">
                                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 mr-2 py-1 rounded-md text-sm">
                                                        Edit
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                    <button onclick="openArchiveModal(<?php echo $product['product_id']; ?>)"
                                                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md text-sm">
                                                        Archive
                                                    </button>
                                                </div>
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
                        <!--
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Previous
                        </a>
                        -->
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
                    <!--
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Next
                        </a>
                        --->
                    <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"
                        class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        Last
                    </a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Product</h3>
                <form id="editProductForm" method="POST">
                    <input type="hidden" name="product_id" id="editProductId">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editProductName">
                            Product Name
                        </label>
                        <input type="text"
                            id="editProductName"
                            name="product_name"
                            maxlength="25"
                            oninput="updateEditCharCount(this)"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            placeholder="Enter product name (max 25 characters)"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editCategory">Category</label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="editCategory" name="category_id" required>
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
                        <input type="text"
                            id="editPrice"
                            name="price"
                            pattern="^\d{1,5}(\.\d{0,2})?$"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            placeholder="Enter price (max 99,999.99)"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editCurrentQuantity">
                            Current Quantity
                        </label>
                        <input type="number"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline"
                            id="editCurrentQuantity"
                            name="current_quantity">
                    </div>

                    <!-- Update the quantity display section -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="editAdditionalQuantity">
                            Additional Quantity
                        </label>
                        <input type="number"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="editAdditionalQuantity"
                            name="additional_quantity"
                            min="1"
                            max="999"
                            maxlength="3"
                            onkeydown="return event.keyCode !== 190 && event.keyCode !== 110"
                            oninput="javascript: if (this.value.length > 3) this.value = this.value.slice(0, 3);"
                            placeholder="Enter quantity (1-999)">

                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Archive Product</h3>
            <p class="mb-4">Are you sure you want to archive this product?</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded" onclick="closeArchiveModal()">Cancel</button>
                <button type="button" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded" id="confirmArchive">Archive</button>
            </div>
        </div>
    </div>

    <script src="../JS/time.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/manage_products.js"></script>
    <script>
        function openArchiveModal(productId) {
            Swal.fire({
                title: 'Archive Product?',
                text: 'This will move the product to archives. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../endpoint/archive-product.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `product_id=${productId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Archived!',
                                    'Product has been moved to archives.',
                                    'success'
                                ).then(() => {
                                    document.getElementById(`product-${productId}`).remove();
                                });
                            } else {
                                throw new Error(data.error || 'Failed to archive product');
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error!',
                                error.message,
                                'error'
                            );
                        });
                }
            });
        }
        document.getElementById('editQuantity').addEventListener('input', function(e) {
            const value = parseInt(this.value);
            if (value < 1 || value > 999) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Please enter a quantity between 1 and 999',
                    confirmButtonColor: '#3085d6'
                });
                this.value = value < 1 ? 1 : 999;
            }
        });

        // Update existing form submission handler
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            const quantity = document.getElementById('editQuantity').value;

            if (quantity <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Quantity must be greater than 0',
                    confirmButtonColor: '#3085d6'
                });
                document.getElementById('editQuantity').value = 1;
                return false;
            }
        });

        document.getElementById('editPrice').addEventListener('change', function(e) {
            const price = parseFloat(this.value);

            if (isNaN(price) || price < 1 || price > 99999) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Price',
                    text: 'Please enter a price between 1 and 99,999',
                    confirmButtonColor: '#3085d6'
                }).then((result) => {
                    this.value = ''; // Clear invalid input
                    this.focus(); // Return focus to price field
                });
            }
        });
    </script>
    <script>
        function validateQuantity(input, currentQty) {
            currentQty = parseInt(currentQty) || 0;
            const additionalQty = parseInt(input.value) || 0;
            const totalQty = currentQty + additionalQty;

            // Update max quantity hint
            document.getElementById('maxQuantityHint').textContent =
                `Maximum additional quantity allowed: ${999 - currentQty}`;

            if (input.value.length > 3) {
                input.value = input.value.slice(0, 3);
            }

            if (totalQty > 999) {
                input.value = 999 - currentQty;
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Total quantity cannot exceed 999',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const currentQty = parseInt(document.getElementById('editCurrentQuantity').value) || 0;
            const additionalQty = parseInt(document.getElementById('editAdditionalQuantity').value) || 0;
            const totalQty = currentQty + additionalQty;

            if (!additionalQty || additionalQty < 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Additional quantity must be at least 1'
                });
                return false;
            }

            if (totalQty > 999) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Total quantity cannot exceed 999'
                });
                return false;
            }

            // Continue with form submission if validation passes
            // ...existing submission code...
        });

        document.getElementById('editPrice').addEventListener('input', function(e) {
            // Remove any non-numeric characters except decimal point
            let value = this.value.replace(/[^\d.]/g, '');

            // Ensure only one decimal point
            const decimalCount = (value.match(/\./g) || []).length;
            if (decimalCount > 1) {
                value = value.replace(/\.(?=.*\.)/g, '');
            }

            // Split number into integer and decimal parts
            const parts = value.split('.');

            // Limit integer part to 5 digits
            if (parts[0].length > 5) {
                parts[0] = parts[0].slice(0, 5);
            }

            // Limit decimal part to 2 digits
            if (parts[1] && parts[1].length > 2) {
                parts[1] = parts[1].slice(0, 2);
            }

            // Reconstruct the value
            value = parts.join('.');

            // Validate final value
            const price = parseFloat(value);
            if (price > 99999.99) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Price',
                    text: 'Maximum price is 99,999.99',
                    confirmButtonColor: '#3085d6'
                });
                value = '99999.99';
            }

            this.value = value;
        });
    </script>
</body>

</html>