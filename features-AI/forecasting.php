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
    <script src="../features-AI/forecastChart.js"></script>
    <script src="../features-AI/demandForecast.js"></script>
    <link rel="stylesheet" href="../src/output.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 text-gray-900">

    <!-- Header -->
    <header class="flex flex-row sticky">
        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <!-- User dropdown component -->
            <div class="relative"
                x-data="{ isOpen: false }"
                @keydown.escape.stop="isOpen = false"
                @click.away="isOpen = false">

                <button class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    @click="isOpen = !isOpen"
                    type="button"
                    id="user-menu-button"
                    :aria-expanded="isOpen"
                    aria-haspopup="true">
                    <img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-2">
                    <span><?php echo htmlspecialchars($fname); ?></span>
                    <svg class="w-4 h-4 ml-2 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right">

                    <ul class="bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg"
                                href="../features/user_settings.php"
                                role="menuitem">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                        </li>
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg"
                                href="../endpoint/logout.php"
                                role="menuitem">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="container mx-auto p-4">
            <h1 class="text-2xl font-bold mb-4">Product Demand Forecast</h1>

            <h2 class="text-xl font-semibold mb-2">Top-Performing Products</h2>
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b text-center bg-gray-50">Product ID</th>
                        <th class="px-4 py-2 border-b text-left bg-gray-50">Product Name</th>
                        <th class="px-4 py-2 border-b text-center bg-gray-50">Total Quantity Sold</th>
                    </tr>
                </thead>
                <tbody id="topPerformingTableBody"></tbody>
            </table>

            <h2 class="text-xl font-semibold mt-8 mb-2">Underperforming Products</h2>
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b text-center bg-gray-50">Product ID</th>
                        <th class="px-4 py-2 border-b text-left bg-gray-50">Product Name</th>
                        <th class="px-4 py-2 border-b text-center bg-gray-50">Total Quantity Sold</th>
                    </tr>
                </thead>
                <tbody id="underPerformingTableBody"></tbody>
            </table>

            <div class="mt-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Product Demand Forecasts</h2>
                    <div class="relative">
                        <input
                            type="text"
                            id="productSearch"
                            placeholder="Search products..."
                            class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Product ID</th>
                            <th class="px-4 py-2 border-b text-left bg-gray-50">Product Name</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Current Stock</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Previous Month Sales</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Predicted Demand</th>
                            <th class="px-4 py-2 border-b text-center bg-gray-50">Recommended Stock</th>
                        </tr>
                    </thead>
                    <tbody id="forecastTableBody"></tbody>
                </table>

                <div id="paginationControls" class="mt-4 flex justify-center space-x-2"></div>
            </div>
        </div>
    </main>

    <script src="../JS/time.js"></script>
</body>

</html>