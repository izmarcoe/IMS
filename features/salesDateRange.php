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
$limit = 15; // Number of entries per page
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
</head>

<body class="bg-gray-200">

   <!-- Header -->
   <header class="flex flex-row">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <!-- User dropdown component -->
            <div class="relative"
                x-data="{ isOpen: false }"
                @keydown.escape.stop="isOpen = false"
                @click.away="isOpen = false">

                <button class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    @click="isOpen = !isOpen"
                    type="button"
                    id="user-menu-button"
                    :aria-expanded="isOpen"
                    aria-haspopup="true">
                    <img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-2">
                    <span>user</span>
                    <svg class="w-4 h-4 ml-2 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right">

                    <ul class="bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg"
                                href="../features/user_settings.php"
                                role="menuitem">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                        </li>
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg"
                                href="../endpoint/logout.php"
                                role="menuitem">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

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
                <nav class="flex justify-center mt-6">
                    <ul class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition-colors">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li>
                                <a href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $i; ?>" class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50'; ?> border rounded-lg transition-colors"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li>
                                <a href="?start_date=<?php echo htmlspecialchars($startDate); ?>&end_date=<?php echo htmlspecialchars($endDate); ?>&page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 transition-colors">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
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