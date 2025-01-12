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

$fname = $_SESSION['Fname'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Per Month</title>
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
                <h2 class="text-2xl font-bold mb-4">Sales Report for <?php echo htmlspecialchars("$month-$year"); ?></h2>
                <form method="GET" action="salespermonth.php" class="mb-6">
                    <div class="flex flex-wrap gap-4 max-w-2xl">
                        <select name="month" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select name="year" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">View Sales</button>
                    </div>
                </form>
                <div class="mb-4">
                    <button id="download-pdf" class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">Download as PDF</button>
                </div>
                <div class="bg-white rounded-lg shadow p-4 mb-4 grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <p class="text-gray-600">Total Number of Sales</p>
                        <p class="text-2xl font-bold"><?php echo number_format($totals['total_sales']); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600">Total Amount</p>
                        <p class="text-2xl font-bold">₱<?php echo number_format($totals['total_amount'], 2); ?></p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse bg-white shadow-sm rounded-lg text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-3 py-3 text-left">Sale ID</th>
                                <th class="px-3 py-3 text-left">Product</th>
                                <th class="px-3 py-3 text-left">Category</th>
                                <th class="px-3 py-3 text-left">Quantity</th>
                                <th class="px-3 py-3 text-left">Price</th>
                                <th class="px-3 py-3 text-left">Total Amount</th>
                                <th class="px-3 py-3 text-left">Sale Date</th>
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
                <div class="flex justify-center items-center mt-4 space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            First
                        </a>
                        <a href="?page=<?php echo $page - 1; ?>&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Previous
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
                        <a href="?page=<?php echo $page + 1; ?>&month=<?php echo htmlspecialchars($month); ?>&year=<?php echo htmlspecialchars($year); ?>"
                            class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                            Next
                        </a>
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
        document.getElementById('download-pdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Get the current month and year
            const currentDate = new Date();
            const month = currentDate.toLocaleString('default', {
                month: 'long'
            });
            const year = currentDate.getFullYear();

            // Add title
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, {
                align: 'center'
            });

            // Add month and year
            doc.setFontSize(16);
            doc.text(`Sales Report for ${month} ${year}`, doc.internal.pageSize.getWidth() / 2, 30, {
                align: 'center'
            });

            // Add table
            doc.autoTable({
                html: 'table',
                startY: 40
            });

            // Save the PDF
            doc.save(`Sales Report: ${month} ${year}.pdf`);
        });
    </script>

</html>