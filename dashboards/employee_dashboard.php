<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body class="d-flex">
    <!-- Sidebar -->
    <div class="bg-dark text-white vh-100 p-3" style="width: 250px;">
        <h2 class="text-center">Dashboard</h2>
        <a class="sidebar-link" href="#" onclick="setActive(this)">Dashboard</a>
        <a class="sidebar-link disabled" href="#" onclick="setActive(this)">User Management</a> <!-- SHOULD BE DISABLED FOR EMPLOYEES-->
        <a class="sidebar-link" href="#" onclick="setActive(this)">Categories</a>

        <!-- Products Collapse Dropdown -->
        <a class="sidebar-link" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
            Products
        </a>
        <div class="collapse" id="productsCollapse">
            <a class="sidebar-link" href="#" onclick="setActive(this)">Manage Products</a>
            <a class="sidebar-link" href="#" onclick="setActive(this)">Add Products</a>
        </div>

        <!-- Sales Collapse Dropdown -->
        <a class="sidebar-link" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
            Sales
        </a>
        <div class="collapse" id="salesCollapse">
            <a class="sidebar-link" href="#" onclick="setActive(this)">Manage Sales</a>
            <a class="sidebar-link" href="#" onclick="setActive(this)">Add Sales</a>
        </div>
        
        <a class="sidebar-link" href="#" onclick="setActive(this)">Sales Report</a>
    </div>



    <!-- Main Content -->
    <div class="flex-grow-1 p-4 position-relative">
        <div class="position-absolute top-0 end-0 m-3">
            <a class="btn btn-dark" href="../endpoint/logout.php">Logout</a>
        </div>
        <div class="container">
            <h1 class="text-center">Welcome to the Employee Dashboard</h1>
            <p>Hello, employee # <?php echo htmlspecialchars($user_id); ?>!</p>
            <p>Your role: <?php echo htmlspecialchars($user_role); ?></p>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!--JS-->
    <script src="../JS/employee_dashboard.js"></script>
</body>

</html>