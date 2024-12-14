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
    <title>Inventory Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="bg-gray-100">
    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        
        <div class="flex-1 p-8">
            <h1 class="text-2xl font-bold mb-6">Inventory Prediction Analysis</h1>

            <!-- Top Products Analysis -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Top 10 Products - Demand Prediction</h2>
                <div class="overflow-x-auto">
                    <table id="predictionsTable" class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Predicted Demand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risk Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action Needed</th>
                            </tr>
                        </thead>
                        <tbody id="predictionsBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Risk Analysis Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Stockout Risk Analysis</h2>
                    <canvas id="stockoutChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Overstock Risk Analysis</h2>
                    <canvas id="overstockChart"></canvas>
                </div>
            </div>

            <div id="error" class="text-red-500"></div>
        </div>
    </main>
    <script src="../features-AI/forecastChart.js"></script>
</body>

</html>