<?php
// Modified employee_dashboard.php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Strengthen session check
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
    <title>Employee Dashboard</title> <!-- Change title for other dashboards -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="../JS/employeeAuth.js"></script>
</head>
<body class="d-flex">
    <div class="bg-dark text-white vh-100 p-3" style="width: 250px;">
        <h2 class="text-center">Dashboard</h2>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Categories</a>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Manage Products</a>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Add Products</a>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Manage Sales</a>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Add Sales</a>
        <a class="text-white d-block py-2 px-3 text-decoration-none" href="#">Sales Report</a>
    </div>
    <div class="flex-grow-1 p-4 position-relative">
        <div class="position-absolute top-0 end-0 m-3">
            <a class="btn btn-dark" href="../endpoint/logout.php">Logout</a>
        </div>
        <div class="container">
            <h1 class="text-center">Welcome to the Employee Dashboard</h1> <!-- Change for other dashboards -->
            <p>Hello, employee # <?php echo htmlspecialchars($user_id); ?>!</p>
            <p>Your role: <?php echo htmlspecialchars($user_role); ?></p> <!-- This should now display the correct role -->
            
            <!-- Add role-specific content here -->
        </div>
    </div>
    <script>
        // Add this to your dashboard pages
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
