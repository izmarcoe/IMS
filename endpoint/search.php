<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%$search%";

// Sorting logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$orderBy = '';
switch ($sort) {
    case 'category_asc':
        $orderBy = 'pc.category_name ASC';
        break;
    case 'category_desc':
        $orderBy = 'pc.category_name DESC';
        break;
    case 'price_asc':
        $orderBy = 's.price ASC';
        break;
    case 'price_desc':
        $orderBy = 's.price DESC';
        break;
    case 'name_asc':
        $orderBy = 's.product_name ASC';
        break;
    case 'name_desc':
        $orderBy = 's.product_name DESC';
        break;
    case 'sales_asc':
        $orderBy = 'total_sales ASC';
        break;
    case 'sales_desc':
        $orderBy = 'total_sales DESC';
        break;
    default:
        $orderBy = 's.id DESC';
        break;
}

// Fetch sales data with limit and offset (with optional search filter)
$stmt = $conn->prepare("
    SELECT s.id, s.product_id, s.product_name, s.price, s.quantity, s.sale_date, (s.price * s.quantity) AS total_sales, pc.category_name
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE s.product_name LIKE :search
    ORDER BY $orderBy
");
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($sales);
?>