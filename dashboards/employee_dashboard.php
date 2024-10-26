<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// User ID from session
$user_id = $_SESSION['user_id'];

// Check if Fname and Lname are set in session; if not, fetch them from the database
if (!isset($_SESSION['Fname']) || !isset($_SESSION['Lname'])) {
    // Check if the connection variable is set
    if (isset($conn)) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT Fname, Lname FROM login_db WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Use bindParam for PDO
        $stmt->execute();

        // Fetch the user data
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Set Fname and Lname in the session
            $_SESSION['Fname'] = $user['Fname'];
            $_SESSION['Lname'] = $user['Lname'];
        } else {
            // Handle case where user data is not found (optional)
            echo "User data not found.";
            exit();
        }

        // Close the statement
        $stmt = null;
    } else {
        die("Database connection not established.");
    }
}

// Now Fname and Lname are guaranteed to be in the session
$fname = $_SESSION['Fname'];
$lname = $_SESSION['Lname'];
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
<body>
    <!-- Header -->
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>
    <!-- Content -->
    <main class="d-flex">
        <!-- Sidebar -->
        <div class="bg-dark text-white vh-100 p-3" style="width: 300px;">
            <h2 class="text-center fs-2 py-4">Inventory System</h2>
            <div>
                <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Dashboard</a>
                <a class="sidebar-link fs-5 py-3" href="#" style="pointer-events: none; color: gray;">User Management</a>
                <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Categories</a>

                <!-- Products Collapse Dropdown -->
                <a class="sidebar-link fs-5 py-3" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
                    Products
                </a>
                <div class="collapse" id="productsCollapse">
                    <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Manage Products</a>
                    <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Add Products</a>
                </div>

                <!-- Sales Collapse Dropdown -->
                <a class="sidebar-link fs-5 py-3" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
                    Sales
                </a>
                <div class="collapse" id="salesCollapse">
                    <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Manage Sales</a>
                    <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Add Sales</a>
                </div>

                <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Sales Report</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-3">
            <h2 class="text-center">Welcome, <?php echo htmlspecialchars($fname) . ' ' . htmlspecialchars($lname); ?>!</h2>
            <p class="text-center">This is the employee dashboard.</p>
        </div>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- JS -->
    <script src="../JS/employee_dashboard.js"></script>
    <script src="../JS/time.js"></script>
</body>
</html>
