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

// Calculate total pages
$totalPages = ceil($totalSales / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Per Day</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
    <style>
        .date-input {
            max-width: 300px;
        }
    </style>
</head>

<body style="background-color: #DADBDF;">

     <!-- Header -->
     <header class="flex flex-row sticky top-0 z-50">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold">Admin Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="flex justify-end text-white mx-8">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-1"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main>
        <div class="d-flex">
            <aside>
                <?php include '../features/sidebar.php'; ?>
            </aside>
            <div class="container mt-5">
                <h2>Sales Report for <?php echo htmlspecialchars($date); ?></h2>
                <form method="GET" action="salesperday.php" class="mb-4">
                    <div class="input-group date-input">
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        <button type="submit" class="btn btn-primary">View Sales</button>
                    </div>
                    <div class="my-3">
                        <button id="download-pdf" class="btn btn-danger">Download as PDF</button>
                    </div>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Sale Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No sales found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['price']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Pagination controls -->
                <nav>
                    <ul class="pagination d-flex justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?date=<?php echo htmlspecialchars($date); ?>&page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
    <script src="../JS/time.js"></script>
    <script>
        document.getElementById('download-pdf').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Get the date from PHP
            const reportDate = '<?php echo $date; ?>';
            const dateObj = new Date(reportDate);
            const day = dateObj.toLocaleString('default', { day: '2-digit' });
            const month = dateObj.toLocaleString('default', { month: 'long' });
            const year = dateObj.getFullYear();

            // Add title
            doc.setFontSize(20);
            doc.text('ZEFMAVEN COMPUTER PARTS AND ACCESSORIES', doc.internal.pageSize.getWidth() / 2, 20, { align: 'center' });

            // Add date
            doc.setFontSize(16);
            doc.text(`Sales Report for ${month} ${day}, ${year}`, doc.internal.pageSize.getWidth() / 2, 30, { align: 'center' });

            // Add table
            doc.autoTable({ html: 'table', startY: 40 });

            // Save the PDF
            doc.save('Daily_Sales_Report.pdf');
        });
    </script>
</body>

</html>