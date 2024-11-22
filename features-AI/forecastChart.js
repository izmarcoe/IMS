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
