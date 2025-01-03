<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php
require '../endpoint/employeeAuth.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

$currentMonth = date('F'); // Get the current month name

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
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class=bg-gray-200>

    <!-- Header -->
    <?php include '../features/header.php' ?>
    <!-- Content -->
    <main class="flex">
        <div>
            <!-- Sidebar -->
            <?php include '../features/sidebar.php' ?>
        </div>
        <!-- Main Content -->
        <div class="flex-grow p-3">
            <h2 class="text-center text-5xl my-5">Welcome, <?php echo htmlspecialchars($fname) . ' ' . htmlspecialchars($lname); ?>!</h2>

            <div class="flex flex-wrap justify-center items-center gap-6 mt-5">
                <!-- Total Number of categories -->
                <div class="flex flex-col justify-between text-white p-8 bg-orange-600 rounded-lg shadow-md h-full w-80">
                    <!-- Label -->
                    <div class="flex items-center justify-center bg-orange-600 text-white p-4 rounded-full h-[40%]">
                        <img src="../icons/Category.svg" alt="User Icon" class="w-24 h-24 object-contain">
                    </div>
                    <!-- Value and Label -->
                    <div class="flex flex-col items-center  text-white p-4 rounded-lg h-[60%]">
                        <span class="text-2xl font-bold"><?php echo htmlspecialchars($totalCategories); ?></span>
                        <span class="text-md">Categories</span>
                    </div>
                </div>

                <!-- Total Number of products -->
                <div class="flex flex-col justify-between text-white p-8 bg-blue-600 rounded-lg shadow-md h-full w-80">
                    <!-- Label -->
                    <div class="flex items-center justify-center bg-blue-600 text-white p-4 rounded-full h-[40%]">
                        <img src="../icons/cart 4.svg" alt="User Icon" class="w-24 h-24 object-contain">
                    </div>
                    <!-- Value and Label -->
                    <div class="flex flex-col items-center  text-white p-4 rounded-lg h-[60%]">
                        <span class="text-2xl font-bold"><?php echo htmlspecialchars($totalProducts); ?></span>
                        <span class="text-md">Products</span>
                    </div>
                </div>

                <!-- Total Number of sales -->
                <div class="flex flex-col justify-between p-8 bg-green-600 rounded-lg shadow-md h-full w-80">
                    <!-- Label -->
                    <div class="flex items-center justify-center bg-green-600 text-white p-4 rounded-full h-[40%]">
                        <img src="../icons/pesosign.svg" alt="User Icon" class="w-24 h-24 object-contain">
                    </div>
                    <!-- Value and Label -->
                    <div class="flex flex-col items-center text-white p-4 rounded-lg h-[60%]">
                        <span class="text-2xl font-bold"><?php echo htmlspecialchars($totalSales); ?></span>
                        <span class="text-md">Sales</span>
                    </div>
                </div>
            </div>


            <!-- Grid Container -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                <!-- Highest Selling Products -->
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-3">Highest Selling Products</h2>
                    <div class="overflow-y-auto max-h-64">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($highestSellingProducts as $product): ?>
                                <li class="py-2 px-3 hover:bg-gray-50 flex justify-between items-center">
                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    <span class="text-sm text-green-600 font-medium"><?php echo htmlspecialchars($product['total_quantity']); ?> units</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Latest Sales -->
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-3">Latest Sales</h2>
                    <div class="overflow-y-auto max-h-64">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($latestSales as $sale): ?>
                                <li class="py-2 px-3 hover:bg-gray-50">
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($sale['product_name']); ?></p>
                                    <span class="text-xs text-green-600 font-bold">
                                        <?php echo htmlspecialchars($sale['quantity']); ?> units -
                                        <?php echo date('M d, Y', strtotime($sale['sale_date'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Recently Added Products -->
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-3">Recently Added Products</h2>
                    <div class="overflow-y-auto max-h-64">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recentlyAddedProducts as $product): ?>
                                <li class="py-2 px-3 hover:bg-gray-50">
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                    <span class="text-xs text-green-600 font-bold">
                                        Added <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
    </main>


    <!-- JS -->
    <script src="../JS/employeeAuth.js"></script>
    <script src="../JS/time.js"></script>
</body>

</html>