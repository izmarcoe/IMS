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
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <!-- Header -->
    <header class="d-flex flex-row">
        <div class="d-flex justify-content text-center bg-danger align-items-center text-white">
            <div class="" style="width: 300px">
                <h4 class="m-0">INVENTORY SYSTEM</h4>
            </div>
        </div>


        <div class="d-flex align-items-center text-black p-3 flex-grow-1" style="background-color: gray;">
            <div class="d-flex justify-content-start flex-grow-1 text-white">
                <span class="px-4" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" style="width: 20px; height: 20px; margin-right: 5px;"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="d-flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>

        <main class="flex-grow-1">
            <div class="container mt-5">
                <h2>Manage Sales</h2>

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
                        <a href="manage_sales.php" class="btn btn-secondary">Clear</a>
                    </div>
                    <div class="input-group mb-3">
                        <label class="input-group-text" for="sort">Sort By</label>
                        <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                            <option value="">Select</option>
                            <option value="category_asc" <?php if ($sort == 'category_asc') echo 'selected'; ?>>Category (A-Z)</option>
                            <option value="category_desc" <?php if ($sort == 'category_desc') echo 'selected'; ?>>Category (Z-A)</option>
                            <option value="price_asc" <?php if ($sort == 'price_asc') echo 'selected'; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php if ($sort == 'price_desc') echo 'selected'; ?>>Price (High to Low)</option>
                            <option value="name_asc" <?php if ($sort == 'name_asc') echo 'selected'; ?>>Product Name (A-Z)</option>
                            <option value="name_desc" <?php if ($sort == 'name_desc') echo 'selected'; ?>>Product Name (Z-A)</option>
                            <option value="sales_asc" <?php if ($sort == 'sales_asc') echo 'selected'; ?>>Total Sales (Low to High)</option>
                            <option value="sales_desc" <?php if ($sort == 'sales_desc') echo 'selected'; ?>>Total Sales (High to Low)</option>
                        </select>
                    </div>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total Sales</th>
                            <th>Sale Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No sales records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['category_name'] ?? 'No Category'); ?></td>
                                    <td><?php echo htmlspecialchars($sale['price']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['total_sales']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                    <td>
                                        <a href="../endpoint/edit_sale.php?id=<?php echo htmlspecialchars($sale['id']); ?>" class="btn btn-warning">Edit</a>
                                        <button class="btn btn-danger btn-sm" onclick="deleteSale(<?php echo $sale['id']; ?>)">
                                            Delete
                                        </button>
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sale['id']); ?>">
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

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteSaleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Sale</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this sale record?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>

    <script>
        // Initialize modals
        const deleteSaleModal = new bootstrap.Modal(document.getElementById('deleteSaleModal'));
        let saleToDelete = null;

        function deleteSale(id) {
            saleToDelete = id;
            deleteSaleModal.show();
        }

        // Handle delete confirmation
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (saleToDelete) {
                window.location.href = `manage_sales.php?delete_id=${saleToDelete}`;
            }
        });
    </script>

</body>

</html>