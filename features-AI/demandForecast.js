/**
 * DemandForecaster - Handles product demand prediction and stock recommendations
 * Uses machine learning to analyze sales history and predict future demand
 * 
 * @class DemandForecaster
 * @description Neural Network model for demand prediction
 * 
 * Architecture:
 * - Input Layer: 3 neurons (3-month historical data)
 * - Hidden Layer 1: 32 neurons with ReLU activation
 * - Hidden Layer 2: 16 neurons with ReLU activation
 * - Output Layer: 1 neuron (next month prediction)
 * 
 * Features:
 * - Uses TensorFlow.js sequential model
 * - Adam optimizer with learning rate 0.001
 * - Mean Squared Error loss function
 * - Includes seasonal adjustments and growth rate calculations
 */
class DemandForecaster {
    /**
     * Initialize forecaster with required DOM elements and default values
     * @constructor
     */
    constructor() {
        // DOM element references
        this.model = null;                                    // TensorFlow model instance
        this.searchInput = document.getElementById('productSearch');  // Search input field
        this.tableBody = document.getElementById('forecastTableBody'); // Table body for results
        this.loadingRow = document.getElementById('loadingRow');      // Loading indicator row
        
        // Pagination settings
        this.itemsPerPage = 10;        // Number of items per page
        this.currentPage = 1;          // Current active page
        
        // Data storage
        this.allProducts = [];         // Holds all product data
        
        this.setupEventListeners();
    }

    /**
     * Set up event listeners for user interactions
     */
    setupEventListeners() {
        this.searchInput.addEventListener('input', () => this.handleSearch());
    }

    /**
     * Initialize the forecaster by loading data and training the model
     * @async
     */
    async initialize() {
        await this.loadData();
        await this.trainModel();
        this.displayForecasts();
    }

    /**
     * Load sales and inventory data from the server
     * @async
     */
    async loadData() {
        const salesData = await fetch('../features-AI/get_sales_data.php').then(r => r.json());
        const inventoryData = await fetch('../features-AI/get_inventory_data.php').then(r => r.json());
        this.processData(salesData, inventoryData);
    }

    /**
     * Process raw sales and inventory data into usable format
     * @param {Array} salesData - Raw sales data from database
     * @param {Array} inventoryData - Raw inventory data from database
     */
    processData(salesData, inventoryData) {
        // Group sales by product and month
        const salesByProduct = {};
        
        salesData.forEach(sale => {
            const date = new Date(sale.sale_date);
            const monthKey = `${date.getFullYear()}-${date.getMonth()+1}`;
            
            if (!salesByProduct[sale.product_id]) {
                salesByProduct[sale.product_id] = {};
            }
            
            if (!salesByProduct[sale.product_id][monthKey]) {
                salesByProduct[sale.product_id][monthKey] = 0;
            }
            
            salesByProduct[sale.product_id][monthKey] += sale.quantity;
        });

        // Combine with inventory data
        this.allProducts = inventoryData.map(product => {
            const salesHistory = salesByProduct[product.product_id] || {};
            return {
                ...product,
                salesHistory
            };
        });
    }

    async trainModel() {
        const model = tf.sequential();
        
        // Reduce input shape to 3 months instead of 6
        model.add(tf.layers.dense({
            units: 32,
            activation: 'relu',
            inputShape: [3] // Changed from 6 to 3 months
        }));
        
        // Rest of the model architecture remains the same
        model.add(tf.layers.dense({
            units: 16,
            activation: 'relu'
        }));
        
        model.add(tf.layers.dense({
            units: 1
        }));

        model.compile({
            optimizer: tf.train.adam(0.001),
            loss: 'meanSquaredError'
        });

        const trainingData = this.prepareTrainingData();
        
        if (trainingData.xs.length > 0) {
            const xs = tf.tensor2d(trainingData.xs);
            const ys = tf.tensor2d(trainingData.ys);
            
            await model.fit(xs, ys, {
                epochs: 100,
                batchSize: 32,
                shuffle: true
            });
            
            this.model = model;
        }
    }

    prepareTrainingData() {
        const xs = [];
        const ys = [];
        
        this.allProducts.forEach(product => {
            const history = Object.values(product.salesHistory);
            // Changed from 7 to 4 months minimum
            if (history.length >= 4) {
                for (let i = 0; i < history.length - 3; i++) {
                    xs.push(history.slice(i, i + 3));
                    ys.push([history[i + 3]]);
                }
            }
        });
        
        return { xs, ys };
    }

    async predictDemand(product) {
        if (!this.model) return 1;
        
        const history = Object.values(product.salesHistory);
        if (history.length < 3) {
            return Math.max(1, Math.round(history.reduce((a, b) => a + b, 0) / history.length)) || 1;
        }

        // Calculate month-over-month growth rate
        const monthlyGrowthRates = [];
        for (let i = 1; i < history.length; i++) {
            if (history[i - 1] !== 0) {
                const growthRate = (history[i] - history[i - 1]) / history[i - 1];
                monthlyGrowthRates.push(growthRate);
            }
        }

        // Get average growth rate
        const avgGrowthRate = monthlyGrowthRates.length > 0 
            ? monthlyGrowthRates.reduce((a, b) => a + b, 0) / monthlyGrowthRates.length 
            : 0;

        // Get seasonal factor (compare current month with same month last year)
        const currentMonth = new Date().getMonth();
        const lastYearSameMonth = history[history.length - 12] || history[history.length - 1];
        const seasonalFactor = lastYearSameMonth ? history[history.length - 1] / lastYearSameMonth : 1;

        // Use TensorFlow model for base prediction
        const input = tf.tensor2d([history.slice(-3)]);
        const basePrediction = await this.model.predict(input).data();
        
        // Combine base prediction with growth rate and seasonality
        const adjustedPrediction = basePrediction[0] * (1 + avgGrowthRate) * seasonalFactor;
        
        // Round and ensure minimum of 1
        return Math.max(1, Math.round(adjustedPrediction));
    }

    handleSearch() {
        const searchTerm = this.searchInput.value.toLowerCase();
        const filtered = this.allProducts.filter(product => 
            product.product_name.toLowerCase().includes(searchTerm)
        );
        this.displayForecasts(filtered);
    }

    /**
     * Calculate recommended stock level based on predictions and current inventory
     * @param {number} prediction - Predicted demand for next month
     * @param {Object} product - Product information including current quantity
     * @param {number} lastMonthSales - Sales from previous month
     * @returns {number} Recommended stock level
     */
    calculateRecommendedStock(prediction, product, lastMonthSales) {
        const minimumStockLevel = Math.ceil(prediction * 0.2);  // 20% minimum stock
        const leadTimeFactor = 1.1;                             // 10% buffer for lead time
        const safetyStock = Math.ceil(prediction * 0.15);       // 15% safety stock
        
        const totalRequired = Math.ceil(prediction * leadTimeFactor) + safetyStock;
        return Math.max(minimumStockLevel, totalRequired - product.quantity);
    }

    /**
     * Display forecasts in the table with pagination
     * @async
     * @param {Array} products - Products to display (defaults to all products)
     */
    async displayForecasts(products = this.allProducts) {
        this.loadingRow.classList.remove('hidden');
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageProducts = products.slice(start, end);
        
        this.tableBody.innerHTML = '';
        
        for (const product of pageProducts) {
            const prediction = await this.predictDemand(product);
            const lastMonthSales = Object.values(product.salesHistory).pop() || 0;
            
            // Calculate safety buffer based on sales variability
            const history = Object.values(product.salesHistory);
            const salesVariability = this.calculateSalesVariability(history);
            const safetyBuffer = Math.ceil(prediction * salesVariability);
            
            const recommendedStock = this.calculateRecommendedStock(prediction, product, lastMonthSales);
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-4 py-2 border-b text-center">${product.product_id}</td>
                <td class="px-4 py-2 border-b">${product.product_name}</td>
                <td class="px-4 py-2 border-b text-center">${product.quantity}</td>
                <td class="px-4 py-2 border-b text-center">${lastMonthSales}</td>
                <td class="px-4 py-2 border-b text-center">${prediction}</td>
                <td class="px-4 py-2 border-b text-center ${recommendedStock > 1 ? 'text-red-600 font-semibold' : 'text-green-600'}">${recommendedStock}</td>
            `;
            this.tableBody.appendChild(row);
        }
        
        this.updatePagination(products.length);
        this.loadingRow.classList.add('hidden');
    }

    updatePagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const controls = document.getElementById('paginationControls');
        controls.innerHTML = '';
        
        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');
            button.innerText = i;
            button.className = `px-3 py-1 border rounded ${this.currentPage === i ? 'bg-blue-500 text-white' : 'bg-white'}`;
            button.addEventListener('click', () => {
                this.currentPage = i;
                this.displayForecasts();
            });
            controls.appendChild(button);
        }
    }

    /**
     * Calculate sales variability for safety stock
     * @param {Array} history - Historical sales data
     * @returns {number} Variability factor
     */
    calculateSalesVariability(history) {
        if (history.length < 2) return 0.1; // Default 10% buffer
        
        // Calculate standard deviation of sales
        const mean = history.reduce((a, b) => a + b, 0) / history.length;
        const variance = history.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / history.length;
        const stdDev = Math.sqrt(variance);
        
        // Return coefficient of variation (standardized measure of variability)
        return mean !== 0 ? Math.min(stdDev / mean, 0.5) : 0.1;
    }
}

// Initialize forecaster when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const forecaster = new DemandForecaster();
    forecaster.initialize();
});