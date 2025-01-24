<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

$month = $_GET['month'] ?? date('m'); // Default to current month if not provided
$year = $_GET['year'] ?? date('Y'); // Default to current year if not provided

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of sales for the given month and year
$totalQuery = $conn->prepare(" SELECT COUNT(*) FROM sales WHERE MONTH(sale_date) = :month AND YEAR(sale_date) = :year");
$totalQuery->bindParam(':month', $month);
$totalQuery->bindParam(':year', $year);
$totalQuery->execute();
$totalSales = $totalQuery->fetchColumn();

// Update the query to include category information
$query = $conn->prepare("
    SELECT s.*, p.product_name, p.price, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE MONTH(s.sale_date) = :month AND YEAR(s.sale_date) = :year 
    LIMIT :limit OFFSET :offset
");
$query->bindParam(':month', $month);
$query->bindParam(':year', $year);
$query->bindParam(':limit', $limit, PDO::PARAM_INT);
$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$sales = $query->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages
$totalPages = ceil($totalSales / $limit);

// Calculate which page numbers to show
$startPage = max(1, min($page - 2, $totalPages - 4));
$endPage = min($totalPages, $startPage + 4);

// Calculate totals
$totalQuery = $conn->prepare("
    SELECT COUNT(*) as total_sales,
           SUM(quantity * price) as total_amount 
    FROM sales 
    WHERE MONTH(sale_date) = :month 
    AND YEAR(sale_date) = :year
");
$totalQuery->bindParam(':month', $month);
$totalQuery->bindParam(':year', $year);
$totalQuery->execute();
$totals = $totalQuery->fetch(PDO::FETCH_ASSOC);

// Add this query after existing queries
$pdfQuery = $conn->prepare("
    SELECT s.*, p.product_name, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE MONTH(sale_date) = :month 
    AND YEAR(sale_date) = :year
    ORDER BY sale_date
");
$pdfQuery->bindParam(':month', $month);
$pdfQuery->bindParam(':year', $year);
$pdfQuery->execute();
$allSales = $pdfQuery->fetchAll(PDO::FETCH_ASSOC);

$fname = $_SESSION['Fname'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Per Month</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/roleMonitor.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body class="bg-gray-200">
    <!-- Header -->
    <?php include '../features/header.php' ?>

    <main class="flex min-h-screen">
        <!-- Sidebar - Hidden on mobile -->
        <aside class=" w-64 bg-green-800">
            <?php include '../features/sidebar.php'; ?>
        </aside>

        <!-- Main Content - Full width on mobile -->
        <div class="flex-1 p-4 sm:p-8 w-full">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-xl sm:text-2xl font-bold mb-2 sm:mb-3">Sales Report for <?php echo htmlspecialchars($month); ?> <?php echo htmlspecialchars($year); ?></h2>

                <!-- Month/Year Selection Form -->
                <form method="GET" class="mb-4 sm:mb-6 space-y-2 sm:space-y-4">
                    <div class="flex flex-col sm:flex-row gap-2 sm:space-x-4 items-start sm:items-center">
                        <!-- Month Select -->
                        <select name="month" class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        
                        <!-- Year Select -->
                        <select name="year" id="year_select" class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg">
                            <?php for ($y = 2023; $y <= date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="w-full sm:w-auto bg-green-600 text-white px-3 sm:px-4 py-2 text-sm rounded-lg hover:bg-green-700">
                            View Sales
                        </button>
                    </div>
                    
                    <button id="download-pdf" type="button" class="w-full sm:w-auto bg-red-600 text-white px-3 sm:px-4 py-2 text-sm rounded-lg hover:bg-red-700">
                        Download as PDF
                    </button>
                </form>

                <!-- Stats Grid -->
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 mb-4 grid grid-cols-2 gap-2 sm:gap-4">
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-xs sm:text-sm">Total Number of Sales</p>
                        <p class="text-lg sm:text-xl font-bold"><?php echo number_format($totals['total_sales']); ?></p>
                    </div>
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-xs sm:text-sm">Total Amount</p>
                        <p class="text-lg sm:text-xl font-bold">₱<?php echo number_format($totals['total_amount'], 2); ?></p>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="7" class="px-3 py-2 text-center text-gray-500">No sales found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $sale):
                                        $totalAmount = $sale['quantity'] * $sale['price'];
                                    ?>
                                        <tr class="border-t hover:bg-gray-50">
                                            <td class="px-3 py-4"><?php echo htmlspecialchars($sale['id']); ?></td>
                                            <td class="px-3 py-4"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                            <td class="px-3 py-4"><?php echo htmlspecialchars($sale['category_name']); ?></td>
                                            <td class="px-3 py-4"><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                            <td class="px-3 py-4"><?php echo number_format($sale['price'], 2); ?></td>
                                            <td class="px-3 py-4 font-semibold"><?php echo number_format($totalAmount, 2); ?></td>
                                            <td class="px-3 py-4"><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Update pagination section -->
                <div class="flex justify-center items-center mt-4 space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            First
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $totalPages; ?>&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Last
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="../JS/time.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearSelect = document.getElementById('year_select');
            const currentYear = new Date().getFullYear();
            const minYear = 2023;

            // Clear existing options
            yearSelect.innerHTML = '';

            // Populate year select options
            for (let year = currentYear; year >= minYear; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }

            // Set selected year
            const selectedYear = <?php echo json_encode($year); ?>;
            yearSelect.value = selectedYear;
        });
    </script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Get month and year
            const currentDate = new Date('<?php echo "$year-$month-01"; ?>');
            const monthName = currentDate.toLocaleString('default', { month: 'long' });
            const year = currentDate.getFullYear();

            // PDF Header
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            doc.setFontSize(14);
            doc.text(`Sales Report for ${monthName} ${year}`, 
                doc.internal.pageSize.getWidth() / 2, 30, {
                align: 'center'
            });

            doc.text('Note: all AMOUNTS and PRICES are in PHP', doc.internal.pageSize.getWidth() / 2, 35, {
                align: 'center'
            });

            // Table Data
            const tableData = <?php echo json_encode($allSales); ?>;
            const tableRows = tableData.map(sale => [
                sale.id,
                sale.product_name,
                sale.category_name,
                sale.quantity,
                Number(sale.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}),
                (Number(sale.quantity) * Number(sale.price)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}),
                sale.sale_date
            ]);

            // Table Headers
            const headers = [
                ['Sale ID', 'Product', 'Category', 'Quantity', 'Price', 'Total Amount', 'Sale Date']
            ];

            // Generate Table
            doc.autoTable({
                head: headers,
                body: tableRows,
                startY: 40,
                theme: 'grid',
                headStyles: { fillColor: [76, 175, 80] },
                didDrawPage: function(data) {
                    doc.setFontSize(10);
                    doc.text(`Page ${data.pageNumber}`, data.settings.margin.left, 
                        doc.internal.pageSize.height - 10);
                }
            });

            // Add summary after table
            const finalY = doc.lastAutoTable.finalY || 40;
            doc.setFontSize(12);
            doc.text(`Total Number of Sales: <?php echo $totals['total_sales']; ?>`, 14, finalY + 20);
            doc.text(`Total Amount: <?php echo str_replace('+', '', number_format($totals['total_amount'], 2)); ?>`, 14, finalY +30);

            // Save PDF
            doc.save(`Sales_Report_${monthName}_${year}.pdf`);
        });
    </script>

</html>