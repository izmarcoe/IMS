async function fetchData(url) {
    const response = await fetch('../features-AI/get_inventory_data.php');
    return response.json();
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
            <td class="px-4 py-2">${item.product_id}</td>
            <td class="px-4 py-2">${item.product_name}</td>
            <td class="px-4 py-2">${item.total_quantity}</td>
        `;
        topTableBody.appendChild(row);
    });

    underPerforming.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2">${item.product_id}</td>
            <td class="px-4 py-2">${item.product_name}</td>
            <td class="px-4 py-2">${item.total_quantity}</td>
        `;
        underTableBody.appendChild(row);
    });
}

async function main() {
    const { salesData } = await getData();
    const { topPerforming, underPerforming } = analyzeSalesPerformance(salesData);
    displaySalesPerformance(topPerforming, underPerforming);
}

main();