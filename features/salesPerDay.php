<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'employee')) {
    header("Location: http://localhost/IMS/");
    exit();
}

$date = $_GET['date'] ?? date('Y-m-d'); // Default to today's date if not provided

// Pagination settings
$limit = 15; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of sales for the given date
$totalQuery = $conn->prepare("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = :date");
$totalQuery->bindParam(':date', $date);
$totalQuery->execute();
$totalSales = $totalQuery->fetchColumn();

// Fetch sales data for the given date with pagination
$query = $conn->prepare("SELECT * FROM sales WHERE DATE(sale_date) = :date LIMIT :limit OFFSET :offset");
$query->bindParam(':date', $date);
$query->bindParam(':limit', $limit, PDO::PARAM_INT);
$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$sales = $query->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalQuery = $conn->prepare("
    SELECT COUNT(*) as total_sales,
           SUM(quantity * price) as total_amount 
    FROM sales 
    WHERE DATE(sale_date) = :date
");
$totalQuery->bindParam(':date', $date);
$totalQuery->execute();
$totals = $totalQuery->fetch(PDO::FETCH_ASSOC);

// Add this query after existing queries
$pdfQuery = $conn->prepare("
    SELECT s.*, p.product_name, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE DATE(sale_date) = :date
    ORDER BY sale_date
");
$pdfQuery->bindParam(':date', $date);
$pdfQuery->execute();
$allSales = $pdfQuery->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages
$totalPages = ceil($totalSales / $limit);

$fname = $_SESSION['Fname'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Per Day</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-200">
    <!-- Header -->
    <?php include '../features/header.php' ?>

    <main class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-green-800">
            <?php include '../features/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 p-4 sm:p-8 w-full">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6">Sales Report for <?php echo htmlspecialchars($date); ?></h2>

                <!-- Date Selection Form -->
                <form method="GET" action="salesperday.php" class="mb-4 sm:mb-6 space-y-2 sm:space-y-4">
                <div class="flex flex-col sm:flex-row gap-2 sm:space-x-4 items-start sm:items-center">
                        <input type="date"
                            name="date"
                            id="sale_date"
                            value="<?php echo htmlspecialchars($date); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <button type="submit"
                            class="w-full sm:w-auto bg-green-600 text-white px-3 sm:px-4 py-2 text-sm rounded-lg hover:bg-green-700 transition duration-200">
                            View Sales
                        </button>
                    </div>
                    <button id="download-pdf"
                        type="button"
                        class="w-full sm:w-auto bg-red-600 text-white px-3 sm:px-4 py-2 text-sm rounded-lg hover:bg-red-700 transition duration-200">
                        Download as PDF
                    </button>
                </form>

                <!-- Stats Grid -->
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-sm sm:text-base">Total Number of Sales</p>
                        <p class="text-xl sm:text-2xl font-bold"><?php echo number_format($totals['total_sales']); ?></p>
                    </div>
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-sm sm:text-base">Total Amount</p>
                        <p class="text-xl sm:text-2xl font-bold">₱<?php echo number_format($totals['total_amount'], 2); ?></p>
                    </div>
                </div>

                <!-- Sales Table with Scroll -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No sales found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['id']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['price']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Responsive Pagination -->
                <div class="flex justify-center mt-4 sm:mt-6">
                    <nav class="flex flex-wrap gap-1 justify-center" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $page - 1; ?>"
                                class="px-2 sm:px-3 py-1 sm:py-2 bg-gray-200 rounded-md hover:bg-gray-300 text-xs sm:text-sm font-medium">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $i; ?>"
                                class="px-2 sm:px-3 py-1 sm:py-2 rounded-md text-xs sm:text-sm font-medium 
                                <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $page + 1; ?>"
                                class="px-2 sm:px-3 py-1 sm:py-2 bg-gray-200 rounded-md hover:bg-gray-300 text-xs sm:text-sm font-medium">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </main>

    <script src="../JS/time.js"></script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Get the date from PHP
            const reportDate = '<?php echo $date; ?>';
            const dateObj = new Date(reportDate);
            const day = dateObj.toLocaleString('default', {
                day: '2-digit'
            });
            const month = dateObj.toLocaleString('default', {
                month: 'long'
            });
            const year = dateObj.getFullYear();

            // Add title
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            // Add date
            doc.setFontSize(16);
            doc.text(`Sales Report for ${month} ${day}, ${year}`, doc.internal.pageSize.getWidth() / 2, 30, {
                align: 'center'
            });

            // Add table
            doc.autoTable({
                html: 'table',
                startY: 40
            });

            // Save the PDF
            doc.save(`Sales Report: ${month} ${day}, ${year}.pdf`);
        });
    </script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Get date
            const currentDate = new Date('<?php echo $date; ?>');
            const formattedDate = currentDate.toLocaleString('default', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });

            // PDF Header
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            doc.setFontSize(14);
            doc.text(`Sales Report for ${formattedDate}`,
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
                Number(sale.price).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }),
                (Number(sale.quantity) * Number(sale.price)).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }),
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
                headStyles: {
                    fillColor: [76, 175, 80]
                },
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
            doc.text(`Total Amount: <?php echo str_replace('+', '', number_format($totals['total_amount'], 2)); ?>`, 14, finalY + 30);

            // Save PDF
            doc.save(`Sales_Report_${formattedDate}.pdf`);
        });
    </script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const saleDateInput = document.getElementById('sale_date');
            if (saleDateInput) {
                saleDateInput.setAttribute('max', today);
                saleDateInput.setAttribute('min', '2023-01-01');
            }
        });
        </script>
</body>

</html>