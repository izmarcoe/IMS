<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to the first day of the current month if not provided
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default to today's date if not provided

// Pagination settings
$limit = 15; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of sales for the given date range
$totalQuery = $conn->prepare("SELECT COUNT(*) FROM sales WHERE sale_date BETWEEN :start_date AND :end_date");
$totalQuery->bindParam(':start_date', $startDate);
$totalQuery->bindParam(':end_date', $endDate);
$totalQuery->execute();
$totalSales = $totalQuery->fetchColumn();

// Fetch sales data for the given date range with pagination
$query = $conn->prepare("SELECT * FROM sales WHERE sale_date BETWEEN :start_date AND :end_date LIMIT :limit OFFSET :offset");
$query->bindParam(':start_date', $startDate);
$query->bindParam(':end_date', $endDate);
$query->bindParam(':limit', $limit, PDO::PARAM_INT);
$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$sales = $query->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages
$totalPages = ceil($totalSales / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Date Range</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <style>
        .date-input {
            max-width: 600px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="d-flex flex-row">
        <div class="d-flex justify-content text-center align-items-center text-white" style="background-color: #0F7505;">
            <div class="" style="width: 300px">
                <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
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
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main>
        <div class="d-flex">
            <aside>
                <?php include '../features/sidebar.php'; ?>
            </aside>
            <div class="container mt-5">
                <h2>Sales Report from <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?></h2>
                <form method="GET" action="salesDateRange.php" class="mb-4">
                    <div class="input-group date-input">
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>" max="<?php echo date('Y-m-d'); ?>">
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>" max="<?php echo date('Y-m-d'); ?>" min="<?php echo htmlspecialchars($startDate); ?>">
                        <button type="submit" class="btn btn-primary">View Sales</button>
                    </div>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Sale Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No sales found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['price']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination controls -->
                <nav>
                    <ul class="pagination d-flex justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('start_date').addEventListener('change', function() {
            var startDate = this.value;
            document.getElementById('end_date').setAttribute('min', startDate);
        });
    </script>
    <script src="../JS/time.js"></script>
</body>

</html>