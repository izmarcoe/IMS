/**
 * @class SeasonalTrendAnalyzer
 * @description Statistical model for seasonal trend analysis
 * 
 * Components:
 * 1. Seasonal Decomposition
 *    - Calculates seasonal indices
 *    - Identifies yearly patterns
 * 
 * 2. Growth Trend Analysis
 *    - Weighted average of recent (30%) and historical (20%) growth
 *    - Market adjustment factor (8% growth assumption)
 * 
 * 3. Prediction Formula:
 *    prediction = baseValue * (1 + weightedGrowthRate)^monthsAhead * 
 *                 seasonalFactor * marketAdjustment * varianceFactor
 * 
 * Confidence calculation:
 * - Decreases by 5% per month into the future
 */
class SeasonalTrendAnalyzer {
    constructor() {
        this.yearlyPatterns = {};
        this.initialized = false;
        this.colorPalette = [
            'rgb(59, 130, 246)',   // Blue - 2023
            'rgb(34, 197, 94)',    // Green - 2024
            'rgb(168, 85, 247)',   // Purple - 2025
            'rgb(239, 68, 68)',    // Red - future
            'rgb(251, 146, 60)'    // Orange - future
        ];
        this.chart = null;
    }

    async initialize() {
        try {
            const data = await this.loadSeasonalData();
            await this.analyzePatterns(data);
            this.initialized = true;
            this.renderChart();
            this.showInsights();
        } catch (error) {
            console.error('Error initializing seasonal analyzer:', error);
            document.getElementById('seasonalTrendsLoading').innerHTML = `
                <div class="text-red-500">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Error loading seasonal data
                </div>`;
        }
    }

    async loadSeasonalData() {
        const response = await fetch('../features-AI/get_seasonal_data.php');
        return await response.json();
    }

    async analyzePatterns(data) {
        if (!data || data.length === 0) return;

        // Reset patterns
        this.yearlyPatterns = {};

        // Group by year and month
        data.forEach(sale => {
            const year = sale.year;
            const month = parseInt(sale.month) - 1;
            const sales = parseInt(sale.total_sales);

            if (!this.yearlyPatterns[year]) {
                this.yearlyPatterns[year] = Array(12).fill(0); // Initialize with zeros
            }
            this.yearlyPatterns[year][month] = sales;
        });

        console.log('Data received:', data);
        console.log('Yearly patterns:', this.yearlyPatterns);
    }

    predictRemainingMonths() {
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth();
        
        // Get all available years
        const years = Object.keys(this.yearlyPatterns).sort();
        if (years.length === 0) return null;

        // Calculate weighted seasonal patterns across all years
        const seasonalPatterns = Array(12).fill(0);
        let totalWeight = 0;

        years.forEach((year, index) => {
            const weight = Math.pow(1.1, index); // More recent years get higher weight
            totalWeight += weight;
            
            this.yearlyPatterns[year].forEach((value, month) => {
                seasonalPatterns[month] += (value * weight);
            });
        });

        // Normalize patterns
        seasonalPatterns.forEach((value, index) => {
            seasonalPatterns[index] = value / totalWeight;
        });

        // Calculate growth trends using all years
        const growthRates = [];
        years.forEach(year => {
            const yearData = this.yearlyPatterns[year];
            for (let i = 1; i < yearData.length; i++) {
                if (yearData[i-1] && yearData[i]) {
                    growthRates.push((yearData[i] - yearData[i-1]) / yearData[i-1]);
                }
            }
        });

        // Use conservative growth rate
        const recentGrowthWeight = 0.5;  // Reduced weight
        const historicalGrowthWeight = 0.1; // Reduced weight
        const recentGrowth = growthRates.slice(-3).reduce((a, b) => a + b, 0) / 3;
        const historicalGrowth = growthRates.reduce((a, b) => a + b, 0) / growthRates.length;
        const weightedGrowthRate = Math.min(
            (recentGrowth * recentGrowthWeight) + 
            (historicalGrowth * historicalGrowthWeight),
            0.05  // Maximum 5% growth rate
        );

        // Reduced market adjustment
        const marketAdjustment = 1.02; // Reduced from 1.08

        // Predict with caps
        const predictions = Array(12).fill(null);
        for (let month = currentMonth + 1; month < 12; month++) {
            const baseValue = seasonalPatterns[month];
            const monthsAhead = month - currentMonth;
            
            // Cap variance factor
            const varianceFactor = Math.min(1 + (monthsAhead * 0.01), 1.05); // Max 5% variance

            let prediction = Math.round(
                baseValue * 
                (1 + weightedGrowthRate) ** monthsAhead * 
                marketAdjustment * 
                varianceFactor
            );

            // Sanity check - cap at 150% of historical average
            const historicalAvg = years.reduce((sum, year) => 
                sum + (this.yearlyPatterns[year][month] || 0), 0) / years.length;
            prediction = Math.min(prediction, historicalAvg * 1.5);

            predictions[month] = prediction;
        }

        return predictions;
    }

    renderChart() {
        const ctx = document.getElementById('seasonalTrendsChart');
        if (!ctx) {
            console.error('Chart canvas not found');
            return;
        }

        const loadingDiv = document.getElementById('seasonalTrendsLoading');
        const contentDiv = document.getElementById('seasonalTrendsContent');

        if (loadingDiv) loadingDiv.classList.add('hidden');
        if (contentDiv) contentDiv.classList.remove('hidden');

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const years = Object.keys(this.yearlyPatterns).sort();
        
        if (this.chart) {
            this.chart.destroy();
        }

        const datasets = years.map((year, index) => ({
            label: `Sales ${year}`,
            data: this.yearlyPatterns[year],
            borderColor: this.colorPalette[index % this.colorPalette.length],
            backgroundColor: 'transparent',// Reduced opacity to 0.05
            borderWidth: 2.5,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
        }));

        const predictions = this.predictRemainingMonths();
        if (predictions) {
            datasets.push({
                label: 'Predicted 2025',
                data: predictions,
                borderColor: this.colorPalette[2], // Purple for 2025
                backgroundColor: 'transparent',
                borderWidth: 2.5,
                borderDash: [5, 5], // Dashed line for predictions
                tension: 0.4,
                fill: false,
                pointRadius: 4,
                pointHoverRadius: 6
            });
        }

        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            color: '#4B5563' // text color
                        }
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales Trends by Year',
                        font: { size: 16, weight: 'bold' },
                        color: '#4B5563' // title color
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(226, 232, 240, 0.3)' // very light gray
                        },
                        ticks: {
                            color: '#4B5563' // axis labels color
                        }
                    },
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(226, 232, 240, 0.3)' // very light gray
                        },
                        ticks: {
                            color: '#4B5563' // axis labels color
                        }
                    }
                }
            }
        });
    }

    showInsights() {
        const trendsList = document.getElementById('trendsList');
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];

        let insightsHTML = '';
        Object.entries(this.yearlyPatterns).forEach(([year, data]) => {
            const validSales = data.filter(sale => sale !== null);
            if (validSales.length > 0) {
                const maxSale = Math.max(...validSales);
                const minSale = Math.min(...validSales);
                const maxMonth = months[data.indexOf(maxSale)];
                const minMonth = months[data.indexOf(minSale)];

                insightsHTML += `
                    <li class="mb-4">
                        <div class="font-semibold text-lg mb-2">${year}</div>
                        <div class="flex items-center text-green-600">
                            <i class="fas fa-arrow-up mr-2"></i>
                            Peak: ${maxMonth} (${maxSale} units)
                        </div>
                        <div class="flex items-center text-red-600">
                            <i class="fas fa-arrow-down mr-2"></i>
                            Low: ${minMonth} (${minSale} units)
                        </div>
                    </li>`;
            }
        });

        const predictions = this.predictRemainingMonths();
        if (predictions) {
            const currentMonth = new Date().getMonth();
            insightsHTML += `
                <div class="font-semibold mb-3">2025 Predictions:</div>
                <div class="text-sm text-gray-600 mb-2">
                    Based on historical data, seasonal patterns, and market trends
                </div>`;
            
            for (let i = currentMonth + 1; i < 12; i++) {
                if (predictions[i]) {
                    const confidence = 100 - (i - currentMonth) * 5; // Decreasing confidence
                    insightsHTML += `
                        <li class="flex items-center mb-2">
                            <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                            <span>${months[i]}: ${predictions[i]} units</span>
                            <span class="ml-2 text-sm text-gray-500">(${confidence}% confidence)</span>
                        </li>`;
                }
            }
        }

        trendsList.innerHTML = insightsHTML;
    }
}

// Initialize and export
const seasonalAnalyzer = new SeasonalTrendAnalyzer();
window.seasonalAnalyzer = seasonalAnalyzer; // Make it globally accessible
seasonalAnalyzer.initialize();

export { seasonalAnalyzer };