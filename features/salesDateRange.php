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

$query = $conn->prepare("SELECT * FROM sales WHERE sale_date BETWEEN :start_date AND :end_date");
$query->bindParam(':start_date', $startDate);
$query->bindParam(':end_date', $endDate);
$query->execute();
$sales = $query->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($sales);
?>