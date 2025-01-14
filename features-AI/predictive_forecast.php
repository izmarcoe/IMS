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

// Add this code near the top of the file after session_start()
$currentMonth = date('n'); // Get current month number (1-12)
$currentYear = date('Y');

// Calculate next month and year
if ($currentMonth == 12) {
    $nextMonth = 1;
    $nextYear = $currentYear + 1;
} else {
    $nextMonth = $currentMonth + 1;
    $nextYear = $currentYear;
}

// Get month name
$nextMonthName = date('F', mktime(0, 0, 0, $nextMonth, 1, $nextYear));

// Calculate previous month
if ($currentMonth == 1) {
    $previousMonth = 12;
    $previousYear = $currentYear - 1;
} else {
    $previousMonth = $currentMonth - 1;
    $previousYear = $currentYear;
}

// Get previous month name
$previousMonthName = date('F', mktime(0, 0, 0, $previousMonth, 1, $previousYear));

// Check if Fname and Lname are set in session; if not, fetch them from the database
if (!isset($_SESSION['Fname']) || !isset($_SESSION['Lname'])) {
    // Check if the connection variable is set
    if (isset($conn)) {
        // Prepare the SQL statement
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecasting</title>
    <script src="../node_modules/@tensorflow/tfjs/dist/tf.min.js"></script>
    <script src="../features-AI/PredictDemand.js"></script>
    <script src="../features-AI/demandForecast.js"></script>
    <script type="module" src="../features-AI/seasonalTrends.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-gray-200 to-gray-400 text-gray-900 min-h-screen">
    <!-- Header -->
    <?php include '../features/header.php' ?>
    <main class="py-6">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Breadcrumb -->
            <nav class="flex mb-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="../dashboards/admin_dashboard.php" class="group flex items-center text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200">
                            <i class="fas fa-home mr-2"></i>
                            Dashboard
                            <i class="fas fa-chevron-right ml-2 text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <span class="text-sm text-gray-500">Demand Forecast</span>
                    </li>
                </ol>
            </nav>

            <h1 class="text-2xl font-bold mb-4">Product Demand Forecast</h1>


            <div class="mt-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Product Demand Forecasts</h2>
                </div>

                <div class="mb-4 flex items-center">
                    <div class="relative">
                        <input type="text"
                            id="productSearch"
                            class="w-64 pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-green-600"
                            placeholder="Search products...">
                        <div class="absolute left-3 top-2.5">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                    <button id="searchButton"
                        class="ml-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-700">
                        Search
                    </button>
                </div>

                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Product ID</th>
                            <th class="px-4 py-2 border-b text-left bg-gray-50">Product Name</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Current Stock</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Last Month Sales (<?php echo $previousMonthName; ?>)</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Predicted Demand for <?php echo $nextMonthName; ?></th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Recommended Stock (at least)</th>
                        </tr>
                    </thead>
                    <tbody id="forecastTableBody" class="mb-4">
                        <tr id="loadingRow" class="animate-pulse">
                            <td colspan="6" class="text-center py-8">
                                <div class="flex items-center justify-center">
                                    <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                                <p class="mt-4 text-gray-600 text-sm">Loading forecasts...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="paginationControls" class="my-4 flex justify-center space-x-2"></div>
            </div>

            <!-- Top Products Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 transition-all duration-300 hover:shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-2 text-green-500"></i>
                    Top-5 Performing Products
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity Sold</th>
                                </t>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="topPerformingTableBody">
                        <tbody id="topPerformingTableBody"></tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Products Card -->
            <div class="bg-white rounded-lg shadow-md p-6 transition-all duration-300 hover:shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-red-500"></i>
                    Bottom-5 Underperforming Products
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Quantity Sold</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="underperformingTableBody">
                        <tbody id="underPerformingTableBody"></tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-8 mt-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-chart-line mr-2 text-blue-500"></i>
                    Seasonal Trends Analysis
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <canvas id="seasonalTrendsChart"></canvas>
                    </div>
                    <div id="seasonalInsights" class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold mb-2">Key Insights</h3>
                        <ul id="trendsList" class="space-y-2"></ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../JS/time.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchButton = document.getElementById('searchButton');
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('table tbody tr');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }

            searchButton.addEventListener('click', performSearch);

            // Also allow search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        });
    </script>
</body>

</html>