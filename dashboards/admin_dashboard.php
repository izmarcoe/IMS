<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php
require '../endpoint/adminAuth.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: http://localhost/IMS/");
    exit();
}

// User ID from session
$user_id = $_SESSION['user_id'];

// Check if Fname and Lname are set in session; if not, fetch them from the database
if (!isset($_SESSION['Fname']) || !isset($_SESSION['Lname'])) {
    // Check if the connection variable is set
    if (isset($conn)) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT Fname, Lname FROM login_db WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Use bindParam for PDO
        $stmt->execute();

        // Fetch the user data
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Set Fname and Lname in the session
            $_SESSION['Fname'] = $user['Fname'];
            $_SESSION['Lname'] = $user['Lname'];
        } else {
            // Handle case where user data is not found (optional)
            echo "User data not found.";
            exit();
        }

        // Close the statement
        $stmt = null;
    } else {
        die("Database connection not established.");
    }
}

// Now Fname and Lname are guaranteed to be in the session
$fname = $_SESSION['Fname'];
$lname = $_SESSION['Lname'];

// Fetch highest selling products
$highestSellingStmt = $conn->prepare("
    SELECT product_name, SUM(quantity) AS total_quantity
    FROM sales
    GROUP BY product_name
    ORDER BY total_quantity DESC
    LIMIT 5
");
$highestSellingStmt->execute();
$highestSellingProducts = $highestSellingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest sales
$latestSalesStmt = $conn->prepare("
    SELECT product_name, sale_date, quantity
    FROM sales
    ORDER BY sale_date DESC
    LIMIT 5
");
$latestSalesStmt->execute();
$latestSales = $latestSalesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recently added products
$recentlyAddedStmt = $conn->prepare("
    SELECT product_name, created_at
    FROM products
    ORDER BY created_at DESC
    LIMIT 5
");
$recentlyAddedStmt->execute();
$recentlyAddedProducts = $recentlyAddedStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total numbers
$totalUsersStmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM login_db");
$totalUsersStmt->execute();
$totalUsers = $totalUsersStmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$totalCategoriesStmt = $conn->prepare("SELECT COUNT(*) AS total_categories FROM product_categories");
$totalCategoriesStmt->execute();
$totalCategories = $totalCategoriesStmt->fetch(PDO::FETCH_ASSOC)['total_categories'];

$totalProductsStmt = $conn->prepare("SELECT COUNT(*) AS total_products FROM products");
$totalProductsStmt->execute();
$totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$totalSalesStmt = $conn->prepare("SELECT SUM(quantity) AS total_sales FROM sales");
$totalSalesStmt->execute();
$totalSales = $totalSalesStmt->fetch(PDO::FETCH_ASSOC)['total_sales'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <!-- Bootstrap CSS -->
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>

<body style="background-color: #DADBDF;">
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
    <!-- Content -->
    <main class="d-flex">
        <div>
            <!-- Sidebar -->
            <?php include '../features/sidebar.php' ?>
        </div>
        <!-- Main Content -->
        <div class="flex-grow-1 p-3">
            <h2 class="text-center">Welcome, <?php echo htmlspecialchars($fname) . ' ' . htmlspecialchars($lname); ?>!</h2>
            <p class="text-center">This is the admin dashboard.</p>

            <!-- Dashboard Boxes -->
            <div class="row">
                <!-- Total Numbers -->
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-header text-white" style="background-color: #B67F97;">Total Users</div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($totalUsers); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-header text-white" style="background-color: #FF8359;">Total Categories</div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($totalCategories); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-header text-white" style="background-color: #7789EE;">Total Products</div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($totalProducts); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-header text-white" style="background-color: #A0BE6E;">Total Sales</div>
                        <div class="card-body">
                            <p class="card-text"><?php echo htmlspecialchars($totalSales); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Highest Selling Products -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">Highest Selling Products</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($highestSellingProducts as $product): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($product['product_name']) . ' - ' . htmlspecialchars($product['total_quantity']) . ' units'; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Latest Sales -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">Latest Sales</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($latestSales as $sale): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($sale['product_name']) . ' - ' . htmlspecialchars($sale['quantity']) . ' units on ' . htmlspecialchars($sale['sale_date']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recently Added Products -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-white">Recently Added Products</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($recentlyAddedProducts as $product): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($product['product_name']) . ' - added on ' . htmlspecialchars($product['created_at']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JS -->
    <script src="../JS/employeeAuth.js"></script>
    <script src="../JS/time.js"></script>
    <script src="../JS/preventBack.js"></script>
</body>

</html>