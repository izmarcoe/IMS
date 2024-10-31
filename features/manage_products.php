<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :id");
    $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Pagination
$productsPerPage = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $productsPerPage; // Offset for SQL query

// Get total number of products
$totalProductsQuery = $conn->query("SELECT COUNT(*) FROM products");
$totalProducts = $totalProductsQuery->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch products with limit and offset
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
    LIMIT :offset, :limit
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

//search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Fetch products with limit and offset and optional search
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
    LIMIT :offset, :limit
");
$searchParam = "%$search%";
$stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
$totalProductsQuery = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_name LIKE :search");
$totalProductsQuery->bindParam(':search', $searchParam, PDO::PARAM_STR);
$totalProductsQuery->execute();
$totalProducts = $totalProductsQuery->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body>
    <!-- Header -->
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>

    <!-- Content -->
    <main class="d-flex">
        <!-- Sidebar -->
        <?php include '../features/sidebar.php'; ?>




        <div class="container mt-5">
            <h2>Manage Products</h2>
            <!-- Search Form -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by Product Name" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
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
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                <td><?php echo htmlspecialchars($product['price']); ?></td>
                                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo $product['product_id']; ?>)">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?php echo $product['product_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Controls Below the Table -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <input type="hidden" id="edit_product_id" name="id">
                            <div class="mb-3">
                                <label for="edit_product_name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <select class="form-control" id="edit_category" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php
                                    // Fetch categories
                                    $stmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
                                    $stmt->execute();
                                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category['id']) . '">'
                                            . htmlspecialchars($category['category_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="edit_quantity" name="quantity" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Delete Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this product?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../JS/time.js"></script>
        <script src="../JS/manage_products.js"></script>

    </main>
</body>

</html>