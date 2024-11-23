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
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../features-AI/css/forecasting.css">
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
    <main class="d-flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="dashboard">
            <h1>Inventory & Sales Forecast</h1>

            <!-- Key Metrics -->
            <div class="container mt-3 text-center">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="card-title">Predicted Monthly Sales</div>
                                <div id="predictedSales" class="card-text mt-auto">...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="card-title">Recommended Stock Level</div>
                                <div id="recommendedStock" class="card-text mt-auto">...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="card-title">Stock Out Risk</div>
                                <div id="stockOutRisk" class="card-text mt-auto">...</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="card-title">Reorder Point</div>
                                <div id="reorderPoint" class="card-text mt-auto">...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-container mt-4">
                <canvas id="inventoryForecastChart"></canvas>
            </div>
            <div class="chart-container mt-4">
                <canvas id="inventoryVsDemandChart"></canvas>
            </div>
            <div class="chart-container mt-4">
                <canvas id="stockoutOverstockChart"></canvas>
            </div>
            <div class="container mt-4">
                <h2>Dead Stock Identification</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Current Stock</th>
                            <th>Predicted Demand</th>
                            <th>Dead Stock Value</th>
                        </tr>
                    </thead>
                    <tbody id="deadStockTableBody">
                        <!-- Dead stock data will be populated here -->
                    </tbody>
                </table>
            </div>

            <div id="error" style="color: red; margin-top: 10px;"></div>
        </div>
    </main>
    <script src="../features-AI/forecastChart.js"></script>
    <script src="../JS/time.js"></script>

</body>

</html>