<?php
session_start(); // Start the session
include('../conn/conn.php');

$notification = ""; // Variable to store notification messages

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate and update the password
    if ($new_password === $confirm_password) {
        // Assume $user_id is the ID of the logged-in user
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $query = "SELECT password FROM login_db WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($current_password, $row['password'])) {
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE login_db SET password = :new_password WHERE user_id = :user_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':new_password', $new_password_hashed, PDO::PARAM_STR);
                $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $update_stmt->execute();
                $notification = '<div class="alert alert-success" role="alert">Password updated successfully.</div>';
            } else {
                $notification = '<div class="alert alert-danger" role="alert">Current password is incorrect.</div>';
            }
        } else {
            $notification = '<div class="alert alert-danger" role="alert">User is not logged in.</div>';
        }
    } else {
        $notification = '<div class="alert alert-danger" role="alert">New password and confirm password do not match.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <title>User Settings</title>
</head>
<body>
     <!-- Header -->
     <header class="d-flex flex-row">
        <div class="d-flex justify-content text-center align-items-center text-white" style="background-color: #0F7505;">
            <div class="" style="width: 300px">
                <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
            </div>
        </div>


        <div class="d-flex align-items-center text-black p-3 flex-grow-1" style="background-color: gray;">
            <div class="d-flex justify-content-start flex-grow-1 text-white">
                <span class="px-4" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" style="width: 20px; height: 20px; margin-right: 5px;"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="d-flex">
        <?php include '../features/sidebar.php' ?>
        <div class="flex-grow-1 p-5 container z-3">
            <h2>User Settings</h2>
            <?php echo $notification; // Display notification ?>
            <form class="py-3" action="../features/user_settings.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Update Password</button>
            </form>
        </div>
    </main>

    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>
</body>
</html>