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
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-title">Predicted Monthly Sales</div>
                    <div id="predictedSales" class="metric-value">...</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Recommended Stock Level</div>
                    <div id="recommendedStock" class="metric-value">...</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Stock Out Risk</div>
                    <div id="stockOutRisk" class="metric-value">...</div>
                </div>
                <div class="metric-card">
                    <div class="metric-title">Reorder Point</div>
                    <div id="reorderPoint" class="metric-value">...</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="chart-container">
                <canvas id="inventoryForecastChart"></canvas>
            </div>

            <div id="error" style="color: red; margin-top: 10px;"></div>
        </div>
    </main>

    <script>
        async function fetchData() {
            try {
                const response = await fetch('predictive_analytics.php');
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                return data;
            } catch (error) {
                document.getElementById('error').textContent = 'Error fetching data: ' + error.message;
                throw error;
            }
        }

        function calculateSafetyStock(data) {
            // Calculate safety stock based on demand variability and lead time
            const quantities = data.map(d => d.quantity);
            const stdDev = calculateStandardDeviation(quantities);
            const leadTime = 7; // Assumed 7 days lead time
            const serviceLevel = 1.96; // 95% service level (z-score)
            return Math.ceil(serviceLevel * stdDev * Math.sqrt(leadTime));
        }

        function calculateStandardDeviation(values) {
            const mean = values.reduce((a, b) => a + b) / values.length;
            const variance = values.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / values.length;
            return Math.sqrt(variance);
        }

        function calculateReorderPoint(avgDailyDemand, leadTime, safetyStock) {
            return Math.ceil(avgDailyDemand * leadTime + safetyStock);
        }
        /*
        async function trainModel(dates, quantities) {
            const xs = tf.tensor2d(dates, [dates.length, 1]);
            const ys = tf.tensor2d(quantities, [quantities.length, 1]);

            const model = tf.sequential();
            model.add(tf.layers.dense({
                units: 64,
                activation: 'relu',
                inputShape: [1]
            }));
            model.add(tf.layers.dense({
                units: 32,
                activation: 'relu'
            }));
            model.add(tf.layers.dense({
                units: 1
            }));

            model.compile({
                optimizer: tf.train.adam(0.01),
                loss: 'meanSquaredError'
            });

            await model.fit(xs, ys, {
                epochs: 50,
                validationSplit: 0.2,
                verbose: 0
            });

            return model;
        }
        */
        // THIS IF FOR THE TRAINING MODEL, MORE ADVANCED, FOR MORE LAYERS, AND MORE DATA. (GENERATE MORE DATA FIRST IN THE DATABASE)
        async function trainModel(dates, quantities) {
        const xs = tf.tensor2d(dates, [dates.length, 1]);
        const ys = tf.tensor2d(quantities, [quantities.length, 1]);

        const model = tf.sequential();
        model.add(tf.layers.dense({ units: 128, activation: 'relu', inputShape: [1] }));
        model.add(tf.layers.dropout({ rate: 0.2 }));
        model.add(tf.layers.dense({ units: 64, activation: 'relu' }));
        model.add(tf.layers.dropout({ rate: 0.2 }));
        model.add(tf.layers.dense({ units: 32, activation: 'relu' }));
        model.add(tf.layers.dense({ units: 1 }));

        model.compile({
            optimizer: tf.train.adam(0.001),
            loss: 'meanSquaredError'
        });

        await model.fit(xs, ys, {
            epochs: 200,
            batchSize: 32,
            validationSplit: 0.2,
            verbose: 0
        });

        return model;
    }
        
        async function main() {
            try {
                const data = await fetchData();

                // Preprocess data
                const dates = data.map(d => new Date(d.sale_date).getTime());
                const quantities = data.map(d => d.quantity);

                // Normalize dates for training
                const minDate = Math.min(...dates);
                const maxDate = Math.max(...dates);
                const normalizedDates = dates.map(d => (d - minDate) / (maxDate - minDate));

                // Train model
                const model = await trainModel(normalizedDates, quantities);

                // Generate future dates
                const futureDays = 30;
                const futureTimestamps = [];
                const lastDate = new Date(Math.max(...dates));
                for (let i = 1; i <= futureDays; i++) {
                    const futureDate = new Date(lastDate);
                    futureDate.setDate(futureDate.getDate() + i);
                    futureTimestamps.push(futureDate.getTime());
                }

                // Normalize future dates and predict
                const normalizedFutureDates = futureTimestamps.map(d =>
                    (d - minDate) / (maxDate - minDate)
                );
                const futurePredictions = model.predict(tf.tensor2d(normalizedFutureDates, [normalizedFutureDates.length, 1]));
                const predictedQuantities = await futurePredictions.data();

                // Calculate key metrics
                const safetyStock = calculateSafetyStock(data);
                const avgDailyDemand = quantities.reduce((a, b) => a + b) / quantities.length;
                const leadTime = 7; // Assumed 7 days lead time
                const reorderPoint = calculateReorderPoint(avgDailyDemand, leadTime, safetyStock);
                const predictedMonthlyDemand = predictedQuantities.reduce((a, b) => a + b, 0);
                const stockOutRisk = calculateStockOutRisk(predictedQuantities, safetyStock);

                // Update metrics display
                document.getElementById('predictedSales').textContent = Math.round(predictedMonthlyDemand);
                document.getElementById('recommendedStock').textContent = Math.round(safetyStock + predictedMonthlyDemand);
                document.getElementById('stockOutRisk').textContent = `${(stockOutRisk * 100).toFixed(1)}%`;
                document.getElementById('reorderPoint').textContent = Math.round(reorderPoint);

                // Create inventory forecast chart
                const inventoryCtx = document.getElementById('inventoryForecastChart').getContext('2d');
                new Chart(inventoryCtx, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'Recommended Stock Level',
                            data: futureTimestamps.map((date, i) => ({
                                x: new Date(date),
                                y: safetyStock + predictedQuantities[i]
                            })),
                            borderColor: 'rgba(54, 162, 235, 1)',
                            fill: true,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)'
                        }, {
                            label: 'Reorder Point',
                            data: futureTimestamps.map(date => ({
                                x: new Date(date),
                                y: reorderPoint
                            })),
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderDash: [5, 5]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Inventory Level Forecast'
                            }
                        },
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'day'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Quantity'
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('error').textContent = 'Error: ' + error.message;
            }
        }

        function calculateStockOutRisk(predictedDemand, safetyStock) {
            const exceedsSafetyCount = predictedDemand.filter(q => q > safetyStock).length;
            return exceedsSafetyCount / predictedDemand.length;
        }

        window.addEventListener('load', main);
    </script>
    <script src="../JS/time.js"></script>

</body>

</html>