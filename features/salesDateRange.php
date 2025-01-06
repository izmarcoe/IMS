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
    <!-- Header -->
    <?php include '../features/header.php' ?>
    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php'; ?>
        </aside>
        <div class="w-full">
            <div class="container max-w-7xl mx-auto mt-8 px-4">
                <h2 class="text-2xl font-bold mb-4">Sales Report from <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?></h2>
                <form method="GET" action="salesDateRange.php" class="mb-6">
                    <div class="flex flex-wrap gap-4 max-w-2xl">
                        <input type="date" id="start_date" name="start_date" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($startDate); ?>" max="<?php echo date('Y-m-d'); ?>">
                        <input type="date" id="end_date" name="end_date" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($endDate); ?>" max="<?php echo date('Y-m-d'); ?>" min="<?php echo htmlspecialchars($startDate); ?>">
                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">View Sales</button>
                    </div>
                    <div class="my-4">
                        <button id="download-pdf" class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">Download as PDF</button>
                    </div>
                </form>
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

                <!-- Pagination controls -->
                <div class="flex justify-center items-center mt-4 space-x-2">
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
    <script src="../JS/time.js"></script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Get date range from PHP
            const startDate = '<?php echo $startDate; ?>';
            const endDate = '<?php echo $endDate; ?>';
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(endDate);
            const startDay = startDateObj.toLocaleString('default', {
                day: '2-digit'
            });
            const startMonth = startDateObj.toLocaleString('default', {
                month: 'long'
            });
            const startYear = startDateObj.getFullYear();
            const endDay = endDateObj.toLocaleString('default', {
                day: '2-digit'
            });
            const endMonth = endDateObj.toLocaleString('default', {
                month: 'long'
            });
            const endYear = endDateObj.getFullYear();

            // Add title
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            // Add date range
            doc.setFontSize(14);
            doc.text(`Sales Report from ${startMonth} ${startDay}, ${startYear} to ${endMonth} ${endDay}, ${endYear}`, doc.internal.pageSize.getWidth() / 2, 30, {
                align: 'center'
            });

            // Add table
            doc.autoTable({
                html: 'table',
                startY: 40
            });

            // Save the PDF
            doc.save(`Sales Report: ${startMonth} ${startDay}, ${startYear} to ${endMonth} ${endDay}, ${endYear}.pdf`);
        });
    </script>
</body>

</html>