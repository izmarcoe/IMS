<?php
session_start();
include ('./conn/conn.php');

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch the user's name from the database
    $stmt = $conn->prepare("SELECT `name` FROM `login_db` WHERE `user_id` = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $user_name = $row['name'];
    }

    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System with QR Code Scanner</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="CSS/home.css">
</head>
<body>
    
    <div class="main">

        <div class="container text-center">
            <h1 class="text-center">Welcome <br> <?php echo $user_name; ?>!</h1>
            <h2 class="text-center">Wait for the special admin to give you a role.</h2>
            <a class="btn btn-dark" href="./endpoint/logout.php">Logout</a>
        </div>

    </div>

</body>
</html>

    <?php
    
} else {
    header("http://localhost/IMS/");
}
?>