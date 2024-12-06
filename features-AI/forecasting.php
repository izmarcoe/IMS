<?php
session_start();
include '../conn/conn.php';

// Check if the user is logged in and has the appropriate role to manage users
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory & Sales Forecast</title>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../features-AI/css/forecasting.css">
</head>

<body style="background-color: #DADBDF;">
    
    <!-- Header -->
    <header class="flex flex-row">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

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
                    <span>user</span>
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
    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="dashboard">
            <h1>Inventory & Sales Forecast</h1>

            <!-- Key Metrics -->
            <div class="container mx-auto px-4 mt-3 text-center">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Predicted Monthly Sales -->
                    <div class="bg-white rounded-lg shadow-lg p-4 h-full">
                        <div class="flex flex-col h-full">
                            <h3 class="text-lg font-semibold mb-2">Predicted Monthly Sales</h3>
                            <div id="predictedSales" class="mt-auto">...</div>
                        </div>
                    </div>

                    <!-- Recommended Stock Level -->
                    <div class="bg-white rounded-lg shadow-lg p-4 h-full">
                        <div class="flex flex-col h-full">
                            <h3 class="text-lg font-semibold mb-2">Recommended Stock Level</h3>
                            <div id="recommendedStock" class="mt-auto">...</div>
                        </div>
                    </div>

                    <!-- Stock Out Risk -->
                    <div class="bg-white rounded-lg shadow-lg p-4 h-full">
                        <div class="flex flex-col h-full">
                            <h3 class="text-lg font-semibold mb-2">Stock Out Risk</h3>
                            <div id="stockOutRisk" class="mt-auto">...</div>
                        </div>
                    </div>

                    <!-- Reorder Point -->
                    <div class="bg-white rounded-lg shadow-lg p-4 h-full">
                        <div class="flex flex-col h-full">
                            <h3 class="text-lg font-semibold mb-2">Reorder Point</h3>
                            <div id="reorderPoint" class="mt-auto">...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="mt-4 px-4">
                <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
                    <canvas id="inventoryForecastChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
                    <canvas id="inventoryVsDemandChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <canvas id="stockoutOverstockChart"></canvas>
                </div>
            </div>

            <!-- Dead Stock Identification -->
            <div class="container mx-auto px-4 mt-4">
                <h2 class="text-xl font-bold mb-4">Dead Stock Identification</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Stock
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Predicted Demand
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Dead Stock Value
                                </th>
                            </tr>
                        </thead>
                        <tbody id="deadStockTableBody" class="divide-y divide-gray-200">
                            <!-- Dead stock data will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="error" class="text-red-500 mt-4 px-4"></div>
        </div>
    </main>
    <script src="../features-AI/forecastChart.js"></script>
    <script src="../JS/time.js"></script>

</body>

</html>