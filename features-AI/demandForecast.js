class DemandForecaster {
    constructor() {
        this.model = null;
        this.searchInput = document.getElementById('productSearch');
        this.tableBody = document.getElementById('forecastTableBody');
        this.itemsPerPage = 10;
        this.currentPage = 1;
        this.allProducts = [];
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.searchInput.addEventListener('input', () => this.handleSearch());
    }

    async initialize() {
        await this.loadData();
        await this.trainModel();
        this.displayForecasts();
    }

    async loadData() {
        const salesData = await fetch('../features-AI/get_sales_data.php').then(r => r.json());
        const inventoryData = await fetch('../features-AI/get_inventory_data.php').then(r => r.json());
        
        // Process and combine data
        this.processData(salesData, inventoryData);
    }

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

    async displayForecasts(products = this.allProducts) {
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
            
            const recommendedStock = Math.max(1, prediction + safetyBuffer - product.quantity);
            
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

    // Helper method to calculate sales variability
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

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    const forecaster = new DemandForecaster();
    forecaster.initialize();
});