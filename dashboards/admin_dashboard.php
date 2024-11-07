<?php
session_start();
include('../conn/conn.php'); // Ensure this points to the correct path of your conn.php
require '../endpoint/adminAuth.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
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
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
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
        <?php include '../features/sidebar.php' ?>

        <!-- Main Content -->
        <div class="flex-grow-1 p-3">
            <h2 class="text-center">Welcome, <?php echo htmlspecialchars($fname) . ' ' . htmlspecialchars($lname); ?>!</h2>
            <p class="text-center">This is the Admin dashboard.</p>
        </div>
    </main>

    <!-- Bootstrap Bundle with Popper -->    <!-- JS -->
    <script src="../JS/employee_dashboard.js"></script>
    <script src="../JS/employeeAuth.js"></script>
    <script src="../JS/time.js"></script>
    <script src="../JS/preventBack.js"></script>
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous" defer></script>

</body>

</html>