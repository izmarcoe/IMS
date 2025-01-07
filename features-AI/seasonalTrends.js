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

        trendsList.innerHTML = insightsHTML;
    }
}

// Initialize and export
const seasonalAnalyzer = new SeasonalTrendAnalyzer();
window.seasonalAnalyzer = seasonalAnalyzer; // Make it globally accessible
seasonalAnalyzer.initialize();

export { seasonalAnalyzer };