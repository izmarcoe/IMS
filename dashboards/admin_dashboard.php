<?php
session_start();
include('../conn/conn.php');
require '../endpoint/adminAuth.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: http://localhost/IMS/");
    exit();
}
$currentMonth = date('F'); // Get the current month name

// User ID from session
$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['Fname']) || !isset($_SESSION['Lname'])) {
    // Check if the connection variable is set
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT Fname, Lname, role FROM login_db WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Use bindParam for PDO
        $stmt->execute();

        // Fetch the user data
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Set Fname and Lname in the session
            $_SESSION['Fname'] = $user['Fname'];
            $_SESSION['Lname'] = $user['Lname'];
            $_SESSION['user_role'] = $user['role'];
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

$fname = $_SESSION['Fname'];
$lname = $_SESSION['Lname'];
$user_role = $_SESSION['user_role'];

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

// Fetch total sales for the current month
$totalSalesStmt = $conn->prepare("
    SELECT SUM(quantity) AS total_sales
    FROM sales
    WHERE MONTH(sale_date) = MONTH(CURRENT_DATE())
    AND YEAR(sale_date) = YEAR(CURRENT_DATE())
");
$totalSalesStmt->execute();
$totalSales = $totalSalesStmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

// Update the SQL query to get weekly data
$weeklyOrdersStmt = $conn->prepare("
    SELECT 
        DATE(sale_date) as sale_day,
        COUNT(*) as order_count
    FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 2 WEEK)
    GROUP BY DATE(sale_date)
    ORDER BY sale_day DESC
");
$weeklyOrdersStmt->execute();
$weeklyOrders = $weeklyOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

// Near the top where queries are executed
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM login_db WHERE status = 'active'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalActivatedUsers = $result['total'];

// Format data for Chart.js
$currentWeekData = array_fill(0, 7, 0);
$lastWeekData = array_fill(0, 7, 0);

foreach ($weeklyOrders as $order) {
    $dayDiff = (strtotime('today') - strtotime($order['sale_day'])) / (60 * 60 * 24);
    if ($dayDiff < 7) {
        // Current week
        $dayIndex = date('w', strtotime($order['sale_day']));
        $currentWeekData[$dayIndex] = $order['order_count'];
    } else {
        // Last week
        $dayIndex = date('w', strtotime($order['sale_day']));
        $lastWeekData[$dayIndex] = $order['order_count'];
    }
}
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

            <!-- weekly orders graph-->
            <div class="w-full lg:w-3/4 mx-auto p-6 bg-white rounded-lg shadow-lg mb-8 mt-8">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Weekly Orders</h2>
                </div>
                <div class="bg-white p-4 rounded-lg relative overflow-x-auto">
                    <div class="min-w-[300px]"> <!-- Minimum width container -->
                        <div class="h-[200px]">
                            <canvas id="monthlyOrdersChart" style="width:100% !important; height:100% !important;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap justify-center items-center gap-6 mt-5">
                <!-- Total Number of Users -->
                <a href="../features/manage_users.php" class="block hover:opacity-90 transition-opacity cursor-pointer">
                    <div class="flex flex-col justify-between text-white p-8 bg-pink-600 rounded-lg shadow-md h-full w-80">
                        <!-- Label -->
                        <div class="flex items-center justify-center bg-pink-600 text-white p-4 rounded-full h-[40%]">
                            <img src="../icons/user.svg" alt="User Icon" class="w-24 h-24 object-contain">
                        </div>
                        <!-- Value and Label -->
                        <div class="flex flex-col items-center text-white p-4 rounded-lg h-[60%]">
                            <span class="text-2xl font-bold"><?php echo htmlspecialchars($totalActivatedUsers); ?></span>
                            <span class="text-md">Activated Accounts</span>
                        </div>
                    </div>
                </a>

                <!-- Total Number of categories -->
                <a href="../features/category.php" class="block hover:opacity-90 transition-opacity cursor-pointer">
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
                </a>

                <!-- Total Number of products -->
                <a href="../features/manage_products.php" class="block hover:opacity-90 transition-opacity cursor-pointer">
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
                </a>

                    <!-- Total Number of sales -->
                <a href="../features/manage_sales.php" class="block hover:opacity-90 transition-opacity cursor-pointer">
                    <div class="flex flex-col justify-between p-8 bg-green-600 rounded-lg shadow-md h-full w-80">
                        <!-- Label -->
                        <div class="flex items-center justify-center bg-green-600 text-white p-4 rounded-full h-[40%]">
                            <img src="../icons/sales_db.svg" alt="User Icon" class="w-24 h-24 object-contain">
                        </div>
                        <!-- Value and Label -->
                        <div class="flex flex-col items-center text-white p-4 rounded-lg h-[60%]">
                            <span class="text-2xl font-bold"><?php echo htmlspecialchars(empty($totalSales) ? '0' : $totalSales); ?></span>
                            <span class="text-md">Sales this month</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Grid Container -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                <!-- Highest Selling Products Button -->
                <button onclick="openModal('highestSellingModal')" class="bg-white rounded-lg shadow-lg p-4 hover:shadow-xl transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-800">Highest Selling Products</h2>
                </button>

                <!-- Latest Sales Button -->
                <button onclick="openModal('latestSalesModal')" class="bg-white rounded-lg shadow-lg p-4 hover:shadow-xl transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-800">Latest Sales</h2>
                </button>

                <!-- Recently Added Products Button -->
                <button onclick="openModal('recentProductsModal')" class="bg-white rounded-lg shadow-lg p-4 hover:shadow-xl transition-shadow">
                    <h2 class="text-lg font-semibold text-gray-800">Recently Added Products</h2>
                </button>
            </div>

            <!-- Modal Templates -->
            <!-- Highest Selling Products Modal -->
            <div id="highestSellingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center border-b pb-2 mb-3">
                        <h3 class="text-lg font-semibold text-gray-800">Highest Selling Products</h3>
                        <button onclick="closeModal('highestSellingModal')" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <div class="mt-2">
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
            </div>

            <!-- Latest Sales Modal -->
            <div id="latestSalesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center border-b pb-2 mb-3">
                        <h3 class="text-lg font-semibold text-gray-800">Latest Sales</h3>
                        <button onclick="closeModal('latestSalesModal')" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <div class="mt-2">
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
            </div>

            <!-- Recently Added Products Modal -->
            <div id="recentProductsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center border-b pb-2 mb-3">
                        <h3 class="text-lg font-semibold text-gray-800">Recently Added Products</h3>
                        <button onclick="closeModal('recentProductsModal')" class="text-gray-500 hover:text-gray-700">&times;</button>
                    </div>
                    <div class="mt-2">
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
        </div>
    </main>

    <!-- JS -->
    <script src="../JS/time.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('monthlyOrdersChart').getContext('2d');
        // Update Chart.js configuration
        const monthlyOrdersChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: [{
                        label: 'Current Week',
                        data: <?php echo json_encode($currentWeekData); ?>,
                        borderColor: 'rgb(170, 255, 0)',
                        tension: 0.01,
                        fill: false
                    },
                    {
                        label: 'Previous Week',
                        data: <?php echo json_encode($lastWeekData); ?>,
                        borderColor: 'rgb(238, 75, 43)',
                        tension: 0.01,
                        fill: false,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: (context) => {
                                return context[0].label;
                            },
                            label: (context) => {
                                return `Orders: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    }
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {}
                }
            }
        });

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>

</html>