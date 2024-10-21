<?php
session_start();
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qrCode = $_POST['qr-code'];

     // Hash the input QR code using SHA-256
    $hashedQrCode = hash('sha256', $qrCode);

    $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id` FROM `login_db` WHERE `generated_code` = :generated_code");
    $stmt->bindParam(':generated_code', $hashedQrCode);
    $stmt->execute();

    $accountExist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($accountExist) {

        $_SESSION['user_id'] = $accountExist['user_id'];

        // Retrieve first name and last name
        $name = $accountExist['Fname'] . ' ' . $accountExist['Lname']; // Combine first and last name

        echo "
        <script>
            alert('Login Successfully!');
            window.location.href = 'http://localhost/IMS/home.php';
        </script>
        "; 
    } else {
        // Debugging: Log the failure to find the account
        error_log("QR Code account doesn't exist: " . $hashedQrCode); // Log the failure
        echo "
        <script>
            alert('QR Code account doesn\'t exist!'); // Escaped single quote
            window.location.href = 'http://localhost/IMS/';
        </script>
        "; 
    }
}
?>
