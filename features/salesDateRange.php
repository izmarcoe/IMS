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

$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to the first day of the current month if not provided
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default to today's date if not provided

// Pagination settings
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of sales for the given date range
$totalQuery = $conn->prepare("SELECT COUNT(*) FROM sales WHERE sale_date BETWEEN :start_date AND :end_date");
$totalQuery->bindParam(':start_date', $startDate);
$totalQuery->bindParam(':end_date', $endDate);
$totalQuery->execute();
$totalSales = $totalQuery->fetchColumn();

// Fetch sales data for the given date range with pagination
$query = $conn->prepare("
      SELECT s.*, p.product_name, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id  /* Changed from p.id to p.product_id */
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE s.sale_date BETWEEN :start_date AND :end_date 
    LIMIT :limit OFFSET :offset
");
$query->bindParam(':start_date', $startDate);
$query->bindParam(':end_date', $endDate);
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
    WHERE sale_date BETWEEN :start_date AND :end_date
");
$totalQuery->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);
$totals = $totalQuery->fetch(PDO::FETCH_ASSOC);

// Fetch ALL sales data for PDF (without pagination)
$pdfQuery = $conn->prepare("
    SELECT s.*, p.product_name, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE s.sale_date BETWEEN :start_date AND :end_date 
    ORDER BY s.sale_date
");
$pdfQuery->bindParam(':start_date', $startDate);
$pdfQuery->bindParam(':end_date', $endDate);
$pdfQuery->execute();
$allSales = $pdfQuery->fetchAll(PDO::FETCH_ASSOC);

$fname = $_SESSION['Fname'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Date Range</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-200">
    <?php include '../features/header.php' ?>
    <main class="flex min-h-screen">
        <!-- Sidebar - Hidden on mobile -->
        <aside class=" w-64 bg-green-800">
            <?php include '../features/sidebar.php'; ?>
        </aside>

        <!-- Main Content - Full width on mobile -->
        <div class="flex-1 p-2 sm:p-8 w-full">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-xl sm:text-md font-bold mb-2">Sales Report from
                    <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?></h2>

                <!-- Responsive Form -->
                <form method="GET" action="salesDateRange.php" class="mb-2 sm:mb-6 space-y-2 sm:space-y-4">
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <input type="date" id="start_date" name="start_date"
                            class="w-full sm:w-auto px-3 py-2 text-sm border rounded-lg"
                            value="<?php echo htmlspecialchars($startDate); ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                        <input type="date" id="end_date" name="end_date"
                            class="w-full sm:w-auto px-3 py-2 text-sm border rounded-lg"
                            value="<?php echo htmlspecialchars($endDate); ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                        <button type="submit"
                            class="w-full sm:w-auto bg-green-600 text-white px-4 py-2 text-sm rounded-lg hover:bg-green-700">
                            View Sales
                        </button>
                    </div>
                    <button id="download-pdf" type="button"
                        class="w-full sm:w-auto bg-red-600 text-white px-4 py-2 text-sm rounded-lg hover:bg-red-700">
                        Download as PDF
                    </button>
                </form>

                <!-- Stats Grid - Compact for mobile -->
                <div class="bg-white rounded-lg shadow sm:p-4 mb-2 grid grid-cols-2 gap-2 sm:gap-4 mx-auto">
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-xs sm:text-sm">Total Number of Sales</p>
                        <p class="text-lg sm:text-xl font-bold"><?php echo number_format($totals['total_sales']); ?></p>
                    </div>
                    <div class="text-center p-2">
                        <p class="text-gray-600 text-xs sm:text-sm">Total Amount</p>
                        <p class="text-lg sm:text-xl font-bold">₱<?php echo number_format($totals['total_amount'], 2); ?></p>
                    </div>
                </div>

                <!-- Responsive Table Container -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse bg-white shadow-sm rounded-lg text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Sale ID
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Product
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Category
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Quantity
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Price
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Total Amount
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left">
                                        <div class="flex items-center gap-1">
                                            Sales Date
                                        </div>
                                    </th>
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

                <!-- Responsive Pagination -->
                <div class="flex flex-wrap justify-center gap-1 mt-4">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            First
                        </a>
                        <a href="?page=<?php echo $page - 1; ?>&start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>"
                            class="px-3 py-2 <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Next
                        </a>
                        <a href="?page=<?php echo $totalPages; ?>&start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Last
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('start_date').addEventListener('change', function() {
            var startDate = this.value;
            document.getElementById('end_date').setAttribute('min', startDate);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const minDate = '2023-01-01';

            if (startDateInput) {
                startDateInput.setAttribute('max', today);
                startDateInput.setAttribute('min', minDate);
            }

            if (endDateInput) {
                endDateInput.setAttribute('max', today);
                endDateInput.setAttribute('min', minDate);
            }
        });
    </script>
    <script src="../JS/time.js"></script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Get date range
            const startDate = '<?php echo $startDate; ?>';
            const endDate = '<?php echo $endDate; ?>';
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(endDate);

            // Format dates
            const formatDate = (date) => {
                return date.toLocaleString('default', {
                    month: 'long',
                    day: '2-digit',
                    year: 'numeric'
                });
            };

            // PDF Header
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            doc.setFontSize(14);
            doc.text(`Sales Report from ${formatDate(startDateObj)} to ${formatDate(endDateObj)}`,
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
                `${parseFloat(sale.price).toFixed(2)}`,
                `${(sale.quantity * sale.price).toFixed(2)}`,
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
                    // Footer on each page
                    doc.setFontSize(10);
                    doc.text(`Page ${data.pageNumber}`, data.settings.margin.left,
                        doc.internal.pageSize.height - 10);
                }
            });

            // Add summary after table
            const finalY = doc.lastAutoTable.finalY || 40;
            doc.setFontSize(12);
            doc.text(`Total Number of Sales: <?php echo $totals['total_sales']; ?>`, 14, finalY + 10);
            doc.text(`Total Amount: <?php echo number_format($totals['total_amount'], 2); ?>`, 14, finalY + 20);

            // Save PDF
            doc.save(`Sales_Report_${startDate}_to_${endDate}.pdf`);
        });
    </script>
</body>

</html>