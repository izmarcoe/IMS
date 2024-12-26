//Top performing and underperforming products

async function fetchData(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Fetched data:', data); // Debug log
        return data;
    } catch (error) {
        console.error('Error fetching data:', error);
        return [];
    }
}

async function getData() {
    const salesData = await fetchData('../features-AI/get_sales_data.php');
    return { salesData };
}

function analyzeSalesPerformance(salesData) {
    // Aggregate sales data by product
    const aggregatedSales = salesData.reduce((acc, sale) => {
        if (!acc[sale.product_id]) {
            acc[sale.product_id] = { product_name: sale.product_name, total_quantity: 0 };
        }
        acc[sale.product_id].total_quantity += sale.quantity;
        return acc;
    }, {});

    // Convert aggregated sales data to array format
    const salesArray = Object.keys(aggregatedSales).map(product_id => ({
        product_id,
        product_name: aggregatedSales[product_id].product_name,
        total_quantity: aggregatedSales[product_id].total_quantity
    }));

    // Sort products by total quantity sold
    salesArray.sort((a, b) => b.total_quantity - a.total_quantity);

    // Identify top-performing and underperforming products
    const topPerforming = salesArray.slice(0, 5);
    const underPerforming = salesArray.slice(-5);

    return { topPerforming, underPerforming };
}

function displaySalesPerformance(topPerforming, underPerforming) {
    const topTableBody = document.getElementById('topPerformingTableBody');
    const underTableBody = document.getElementById('underPerformingTableBody');

    topTableBody.innerHTML = '';
    underTableBody.innerHTML = '';

    topPerforming.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2 text-center">${item.product_id}</td>
            <td class="px-4 py-2 text-center">${item.product_name}</td>
            <td class="px-4 py-2 text-center">${item.total_quantity}</td>
        `;
        topTableBody.appendChild(row);
    });

    underPerforming.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2 text-center">${item.product_id}</td>
            <td class="px-4 py-2 text-center">${item.product_name}</td>
            <td class="px-4 py-2 text-center">${item.total_quantity}</td>
        `;
        underTableBody.appendChild(row);
    });
}

async function main() {
    try {
        console.log('Starting main function'); // Debug log
        const { salesData } = await getData();
        
        if (!salesData || salesData.length === 0) {
            console.error('No sales data received');
            return;
        }

        console.log('Sales data:', salesData); // Debug log
        
        const { topPerforming, underPerforming } = analyzeSalesPerformance(salesData);
        
        console.log('Top performing:', topPerforming); // Debug log
        console.log('Under performing:', underPerforming); // Debug log
        
        displaySalesPerformance(topPerforming, underPerforming);
    } catch (error) {
        console.error('Error in main function:', error);
    }
}

// Call main function when document is ready
document.addEventListener('DOMContentLoaded', main);