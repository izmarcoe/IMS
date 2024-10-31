<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
// Fetch sales data with limit and offset (with optional search filter)
$stmt = $conn->prepare("
    SELECT id, product_name, category_id, price, quantity, sale_date, (price * quantity) AS total_sales
    FROM sales
    WHERE product_name LIKE :search
    ORDER BY id DESC
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
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <h2>Manage Sales</h2>

                <?php if (isset($_SESSION['notification'])): ?>
                    <div class="alert alert-info" id="notification">
                        <?php
                        echo $_SESSION['notification'];
                        unset($_SESSION['notification']);
                        ?>
                    </div>
                <?php endif; ?>


                <!-- Search Form -->
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by Product Name" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category ID</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total Sales</th> <!-- New column header -->
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
                                    <td><?php echo htmlspecialchars($sale['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['category_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['price']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['total_sales']); ?></td> <!-- Display Total Sales -->
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                    <td>
                                        <a href="../endpoint/edit_sale.php?id=<?php echo htmlspecialchars($sale['id']); ?>" class="btn btn-warning">Edit</a>
                                        <button class="btn btn-danger delete-btn" data-id="<?php echo htmlspecialchars($sale['id']); ?>">Delete</button>
                                        <form method="POST" action="../endpoint/delete_sales.php" class="delete-form" style="display:none;">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Modal body content will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hide the notification after 5 seconds
        setTimeout(function() {
            var notification = document.getElementById('notification');
            if (notification) {
                notification.style.transition = 'opacity 0.5s';
                notification.style.opacity = '0';
                setTimeout(() => notification.style.display = 'none', 500);
            }
        }, 3000);
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
            var modalTitle = document.getElementById('actionModalLabel');
            var modalBody = document.querySelector('#actionModal .modal-body');
            var confirmButton = document.getElementById('confirmAction');

            document.querySelectorAll('.delete-btn').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    modalTitle.textContent = 'Delete';
                    modalBody.innerHTML = '<p>Are you sure you want to delete this item?</p>';

                    confirmButton.onclick = function() {
                        var form = button.nextElementSibling;
                        form.submit();
                    };

                    actionModal.show();
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>