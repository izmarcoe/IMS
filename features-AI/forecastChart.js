async function fetchData(endpoint) {
    try {
        const response = await fetch(endpoint);
        if (!response.ok) {
            throw new Error(`Network response was not ok for ${endpoint}: ${response.statusText}`);
        }
        const data = await response.json();
        if (data.error) {
            throw new Error(`Error from ${endpoint}: ${data.error}`);
        }
        return data;
    } catch (error) {
        document.getElementById('error').textContent = 'Error fetching data: ' + error.message;
        console.error('Error fetching data from', endpoint, ':', error);
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
        const salesData = await fetchData('get_sales_data.php');
        const inventoryData = await fetchData('get_inventory_data.php');

        // Preprocess sales data
        const dates = salesData.map(d => new Date(d.sale_date).getTime());
        const quantities = salesData.map(d => d.quantity);

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

        // Round predicted quantities
        const roundedPredictedQuantities = Array.from(predictedQuantities).map(Math.round);

        // Calculate key metrics
        const safetyStock = calculateSafetyStock(salesData);
        const avgDailyDemand = quantities.reduce((a, b) => a + b) / quantities.length;
        const leadTime = 7; // Assumed 7 days lead time
        const reorderPoint = calculateReorderPoint(avgDailyDemand, leadTime, safetyStock);
        const predictedMonthlyDemand = roundedPredictedQuantities.reduce((a, b) => a + b, 0);
        const stockOutRisk = calculateStockOutRisk(roundedPredictedQuantities, safetyStock);

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
                        y: Math.round(safetyStock + roundedPredictedQuantities[i])
                    })),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: true,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)'
                }, {
                    label: 'Reorder Point',
                    data: futureTimestamps.map(date => ({
                        x: new Date(date),
                        y: Math.round(reorderPoint)
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

        // Create inventory vs demand chart
        const inventoryVsDemandCtx = document.getElementById('inventoryVsDemandChart').getContext('2d');
        new Chart(inventoryVsDemandCtx, {
            type: 'bar',
            data: {
                labels: inventoryData.map(d => d.product_name),
                datasets: [{
                    label: 'Inventory Levels',
                    data: inventoryData.map(d => d.quantity),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Predicted Demand',
                    data: roundedPredictedQuantities,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Inventory Levels vs. Predicted Demand'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Products'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Units'
                        }
                    }
                }
            }
        });

        // Create stockout and overstocking rate tracking chart
        const stockoutOverstockCtx = document.getElementById('stockoutOverstockChart').getContext('2d');
        new Chart(stockoutOverstockCtx, {
            type: 'line',
            data: {
                labels: salesData.map(d => d.sale_date),
                datasets: [{
                    label: 'Stockout Rate',
                    data: calculateStockoutRate(salesData),
                    borderColor: 'rgba(255, 99, 132, 1)',
                    fill: false
                }, {
                    label: 'Overstocking Rate',
                    data: calculateOverstockingRate(salesData),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Stockout and Overstocking Rates'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Months'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Rates (%)'
                        }
                    }
                }
            }
        });

        // Populate dead stock table
        updateDeadStockTable(inventoryData, roundedPredictedQuantities);

    } catch (error) {
        console.error('Error:', error);
        document.getElementById('error').textContent = 'Error: ' + error.message;
    }
}

function updateDeadStockTable(inventoryData, predictedQuantities) {
    const deadStockTableBody = document.getElementById('deadStockTableBody');

    // Calculate dead stock
    const deadStockItems = inventoryData.map((product, index) => {
        const predictedDemand = predictedQuantities[index] || 0;
        const price = product.price || 0;
        
        console.log(`Product: ${product.product_name}`);
        console.log(`Price: ${price}`);
        
        const excessQuantity = product.quantity - predictedDemand;
        const deadStockValue = excessQuantity > 0 ? excessQuantity * price : 0;
        
        console.log(`Excess Quantity: ${excessQuantity}`);
        console.log(`Dead Stock Value: ${deadStockValue}`);

        return {
            product_name: product.product_name,
            quantity: product.quantity,
            predictedDemand: predictedDemand,
            deadStockValue: deadStockValue.toFixed(2)
        };
    }).filter(item => item.quantity > item.predictedDemand);


    // Sort dead stock items by dead stock value in descending order
    deadStockItems.sort((a, b) => parseFloat(b.deadStockValue) - parseFloat(a.deadStockValue));

    // Get top 10 dead stock items
    const topDeadStockItems = deadStockItems.slice(0, 10);

    // Clear existing table rows
    deadStockTableBody.innerHTML = '';

    // Populate table with top dead stock items
    topDeadStockItems.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.product_name}</td>
            <td>${item.quantity}</td>
            <td>${item.predictedDemand}</td>
            <td>₱${item.deadStockValue}</td>
        `;
        deadStockTableBody.appendChild(row);
    });
}

function calculateStockOutRisk(predictedDemand, safetyStock) {
    const exceedsSafetyCount = predictedDemand.filter(q => q > safetyStock).length;
    return exceedsSafetyCount / predictedDemand.length;
}

function calculateStockoutRate(salesData) {
    // Implement stockout rate calculation logic
    return salesData.map(d => Math.random() * 100); // Placeholder
}

function calculateOverstockingRate(salesData) {
    // Implement overstocking rate calculation logic
    return salesData.map(d => Math.random() * 100); // Placeholder
}

window.addEventListener('load', main);