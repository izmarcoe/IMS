<?php 
session_start();
include ('../conn/conn.php');

// Check if the user is logged in and has the correct role
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'employee') { // Use 'user_role' consistently
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role']; // Consistent session variable for the role
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title> <!-- Change title for other dashboards -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Welcome to the Employee Dashboard</h1> <!-- Change for other dashboards -->
        <p>Hello, <?php echo htmlspecialchars($user_id); ?>!</p>
        <p>Your role: <?php echo htmlspecialchars($user_role); ?></p> <!-- This should now display the correct role -->
        
        <!-- Add role-specific content here -->

        <a class="btn btn-dark" href="../endpoint/logout.php">Logout</a>
    </div>
</body>
</html>

<?php
} else {
    // Redirect to login page if not logged in or wrong role
    header("Location: http://localhost/IMS/");
    exit();
}
?>