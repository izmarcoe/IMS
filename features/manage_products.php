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
        $orderBy = 'p.price ASC';
        break;
    case 'price_desc':
        $orderBy = 'p.price DESC';
        break;
    case 'name_asc':
        $orderBy = 'p.product_name ASC';
        break;
    case 'name_desc':
        $orderBy = 'p.product_name DESC';
        break;
    case 'quantity_asc':
        $orderBy = 'p.quantity ASC';
        break;
    case 'quantity_desc':
        $orderBy = 'p.quantity DESC';
        break;
    default:
        $orderBy = 'p.product_id DESC';
        break;
}

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
    ORDER BY $orderBy
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
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>

    <div class="d-flex">
        <?php include '../features/sidebar.php'; ?>

        <main class="flex-grow-1">
            <div class="container mt-5">
                <h2>Manage Products</h2>

                <?php if (isset($_SESSION['notification'])): ?>
                    <div class="alert alert-info" id="notification">
                        <?php
                        echo $_SESSION['notification'];
                        unset($_SESSION['notification']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="GET" class="mb-3">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="search" placeholder="Search by Product Name" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <a href="manage_products.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>

                <!-- Sorting dropdown beside the table header -->
                <form method="GET" class="d-inline-flex align-items-center">
                    <label class="me-2" for="sort">Sort By:</label>
                    <select class="form-select form-select-sm" id="sort" name="sort" onchange="this.form.submit()">
                        <option value="">Select</option>
                        <option value="category_asc" <?php if ($sort == 'category_asc') echo 'selected'; ?>>Category (A-Z)</option>
                        <option value="category_desc" <?php if ($sort == 'category_desc') echo 'selected'; ?>>Category (Z-A)</option>
                        <option value="price_asc" <?php if ($sort == 'price_asc') echo 'selected'; ?>>Price (Low to High)</option>
                        <option value="price_desc" <?php if ($sort == 'price_desc') echo 'selected'; ?>>Price (High to Low)</option>
                        <option value="name_asc" <?php if ($sort == 'name_asc') echo 'selected'; ?>>Product Name (A-Z)</option>
                        <option value="name_desc" <?php if ($sort == 'name_desc') echo 'selected'; ?>>Product Name (Z-A)</option>
                        <option value="quantity_asc" <?php if ($sort == 'quantity_asc') echo 'selected'; ?>>Quantity (Low to High)</option>
                        <option value="quantity_desc" <?php if ($sort == 'quantity_desc') echo 'selected'; ?>>Quantity (High to Low)</option>
                    </select>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr data-product-id="<?php echo $product['product_id']; ?>">
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                    <td><?php echo htmlspecialchars($product['price']); ?></td>
                                    <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm edit-btn" data-product='<?php echo json_encode($product); ?>'>Edit</button>
                                        <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                                        <form method="POST" action="../endpoint/delete_product.php" style="display:none;">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination Controls -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </main>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" method="POST" action="../endpoint/edit_product.php">
                        <input type="hidden" name="product_id" id="editProductId">
                        <div class="mb-3">
                            <label for="editProductName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="editProductName" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select class="form-control" id="editCategory" name="category" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPrice" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="editPrice" name="price" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="editQuantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="editQuantity" name="quantity" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/manage_products.js"></script>
</body>

</html>