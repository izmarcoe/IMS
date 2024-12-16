// forecastChart.js

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

async function main() {
    try {
        // Fetch and parse data properly
        const [salesResponse, inventoryResponse] = await Promise.all([
            fetch('../features-AI/get_sales_data.php'),
            fetch('../features-AI/get_inventory_data.php')
        ]);

        // Parse JSON responses
        const salesData = await salesResponse.json();
        const inventoryData = await inventoryResponse.json();

        // Validate data
        if (!Array.isArray(salesData) || !Array.isArray(inventoryData)) {
            throw new Error('Invalid data format received from server');
        }

        if (salesData.length === 0 || inventoryData.length === 0) {
            throw new Error('No data available for analysis');
        }

        // Process data for time-series forecasting
        const predictions = await generatePredictions(salesData, inventoryData);

        if (predictions.length > 0) {
            updatePredictionsTable(predictions);
            createRiskCharts(predictions);
        } else {
            throw new Error('No valid predictions generated');
        }

    } catch (error) {
        console.error('Error in main function:', error);
        document.getElementById('error').textContent = `Error: ${error.message}`;
    }
}

// Add debug logging
async function generatePredictions(salesData, inventoryData) {
    const predictions = [];

    for (const product of inventoryData) {
        try {
            // Filter sales data for current product
            const productSales = salesData
                .filter(sale => sale.product_id === product.product_id)
                .map(sale => parseFloat(sale.quantity))
                .filter(qty => !isNaN(qty));

            if (productSales.length < 6) {
                console.warn(`Insufficient data for product ${product.product_name}`);
                continue;
            }

            // Preprocess data
            const { inputs, outputs } = preprocessData(productSales);

            // Create and train model
            const model = await createTimeSeriesModel();
            await trainModel(model, inputs, outputs);

            // Make prediction
            const lastWindow = productSales.slice(-5);
            const predictedDemand = await makePredictions(model, lastWindow);

            // Calculate risks
            const { stockoutRisk, overstockRisk } = calculateRiskScores(
                product.quantity,
                predictedDemand
            );

            predictions.push({
                product_name: product.product_name,
                current_stock: product.quantity,
                predicted_demand: Math.round(predictedDemand),
                stockout_risk: stockoutRisk,
                overstock_risk: overstockRisk,
                action: getActionRecommendation(stockoutRisk, overstockRisk)
            });

            model.dispose();
            inputs.dispose();
            outputs.dispose();

        } catch (error) {
            console.error(`Error processing product ${product.product_name}:`, error);
        }
    }

    return predictions.sort((a, b) => b.stockout_risk - a.stockout_risk).slice(0, 10);
}

async function trainTimeSeriesModel(xsData, ysData) {
    const xs = tf.tensor2d(xsData, [xsData.length, 1]);
    const ys = tf.tensor2d(ysData, [ysData.length, 1]);

    const model = tf.sequential();
    model.add(tf.layers.dense({ units: 50, activation: 'relu', inputShape: [1] }));
    model.add(tf.layers.dense({ units: 25, activation: 'relu' }));
    model.add(tf.layers.dense({ units: 1 }));

    model.compile({ optimizer: 'adam', loss: 'meanSquaredError' });

    await model.fit(xs, ys, { epochs: 100, verbose: 0 });

    xs.dispose();
    ys.dispose();

    return model;
}

function calculateRiskScores(currentStock, predictedDemand) {
    const stockoutRisk = Math.max(0, Math.min(100, ((predictedDemand - currentStock) / predictedDemand) * 100));
    const overstockRisk = Math.max(0, Math.min(100, ((currentStock - predictedDemand) / currentStock) * 100));

    return { stockoutRisk, overstockRisk };
}

function getActionRecommendation(stockoutRisk, overstockRisk) {
    if (stockoutRisk > 70) return 'URGENT: Restock Required';
    if (stockoutRisk > 40) return 'Consider Restocking';
    if (overstockRisk > 70) return 'Reduce Future Orders';
    if (overstockRisk > 40) return 'Monitor Stock Levels';
    return 'Stock Levels Optimal';
}

function updatePredictionsTable(predictions) {
    const tbody = document.getElementById('predictionsBody');
    tbody.innerHTML = predictions.map(pred => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">${pred.product_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right">${pred.current_stock}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right">${pred.predicted_demand}</td>
            <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center">
                        <span class="text-xs w-20">Stockout:</span>
                        <div class="flex-1 bg-gray-200 rounded h-2">
                            <div class="bg-red-500 h-2 rounded" style="width: ${pred.stockout_risk}%"></div>
                        </div>
                        <span class="text-xs ml-2">${pred.stockout_risk.toFixed(1)}%</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xs w-20">Overstock:</span>
                        <div class="flex-1 bg-gray-200 rounded h-2">
                            <div class="bg-yellow-500 h-2 rounded" style="width: ${pred.overstock_risk}%"></div>
                        </div>
                        <span class="text-xs ml-2">${pred.overstock_risk.toFixed(1)}%</span>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getRiskClass(pred.stockout_risk, pred.overstock_risk)}">
                    ${pred.action}
                </span>
            </td>
        </tr>
    `).join('');
}

function createRiskCharts(predictions) {
    // Stockout Risk Chart
    new Chart(document.getElementById('stockoutChart'), {
        type: 'bar',
        data: {
            labels: predictions.map(p => p.product_name),
            datasets: [{
                label: 'Stockout Risk (%)',
                data: predictions.map(p => p.stockout_risk),
                backgroundColor: 'rgba(239, 68, 68, 0.5)',
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });

    // Overstock Risk Chart
    new Chart(document.getElementById('overstockChart'), {
        type: 'bar',
        data: {
            labels: predictions.map(p => p.product_name),
            datasets: [{
                label: 'Overstock Risk (%)',
                data: predictions.map(p => p.overstock_risk),
                backgroundColor: 'rgba(245, 158, 11, 0.5)',
                borderColor: 'rgb(245, 158, 11)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
}

function getRiskClass(stockoutRisk, overstockRisk) {
    if (stockoutRisk > 70) return 'bg-red-100 text-red-800';
    if (stockoutRisk > 40) return 'bg-yellow-100 text-yellow-800';
    if (overstockRisk > 70) return 'bg-blue-100 text-blue-800';
    if (overstockRisk > 40) return 'bg-gray-100 text-gray-800';
    return 'bg-green-100 text-green-800';
}

async function createTimeSeriesModel() {
    const model = tf.sequential();
    
    // Add layers
    model.add(tf.layers.dense({
        inputShape: [5], // Look back 5 time steps
        units: 64,
        activation: 'relu'
    }));
    
    model.add(tf.layers.dropout(0.2));
    
    model.add(tf.layers.dense({
        units: 32,
        activation: 'relu'
    }));
    
    model.add(tf.layers.dense({
        units: 1
    }));

    // Compile the model
    model.compile({
        optimizer: tf.train.adam(0.001),
        loss: 'meanSquaredError',
        metrics: ['mse']
    });

    return model;
}

function preprocessData(salesData, windowSize = 5) {
    const xs = [];
    const ys = [];

    for (let i = windowSize; i < salesData.length; i++) {
        const window = salesData.slice(i - windowSize, i);
        xs.push(window);
        ys.push(salesData[i]);
    }

    return {
        inputs: tf.tensor2d(xs),
        outputs: tf.tensor2d(ys, [ys.length, 1])
    };
}

async function trainModel(model, inputs, outputs, epochs = 100) {
    const history = await model.fit(inputs, outputs, {
        epochs: epochs,
        validationSplit: 0.2,
        shuffle: true,
        callbacks: {
            onEpochEnd: (epoch, logs) => {
                console.log(`Epoch ${epoch + 1}: loss = ${logs.loss.toFixed(4)}`);
            }
        }
    });
    return history;
}

async function makePredictions(model, lastWindow) {
    const input = tf.tensor2d([lastWindow], [1, 5]);
    const prediction = await model.predict(input).data();
    return prediction[0];
}

window.addEventListener('load', main);