<?php
session_start();
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qrCode = $_POST['qr-code'];

    // Hash the input QR code using SHA-256
    $hashedQrCode = hash('sha256', $qrCode);

    $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id`, `role` FROM `login_db` WHERE `generated_code` = :generated_code");
    $stmt->bindParam(':generated_code', $hashedQrCode);
    $stmt->execute();

    $accountExist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($accountExist) {
        $_SESSION['user_id'] = $accountExist['user_id'];
        $_SESSION['user_name'] = $accountExist['Fname'] . ' ' . $accountExist['Lname'];
        $_SESSION['user_role'] = $accountExist['role'];

        // Redirect based on role
        switch ($accountExist['role']) {
            case 'employee':
                $redirect_url = 'http://localhost/IMS/employee_dashboard.php';
                break;
            case 'manager':
                $redirect_url = 'http://localhost/IMS/manager_dashboard.php';
                break;
            case 'admin':
                $redirect_url = 'http://localhost/IMS/admin_dashboard.php';
                break;
            default:
                $redirect_url = 'http://localhost/IMS/home.php';
        }

        echo "
        <script>
            alert('Login Successful!');
            window.location.href = '$redirect_url';
        </script>
        "; 
    } else {
        error_log("QR Code account doesn't exist: " . $hashedQrCode);
        echo "
        <script>
            alert('QR Code account doesn\\'t exist!');
            window.location.href = 'http://localhost/IMS/';
        </script>
        "; 
    }
}
?>