<?php
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qrCode = $_POST['qr-code'];

    $stmt = $conn->prepare("SELECT `generated_code`, `name`, `user_id` FROM `login_db` WHERE `generated_code` = :generated_code");
    $stmt->bindParam(':generated_code', $qrCode);
    $stmt->execute();

    $accountExist =  $stmt->fetch(PDO::FETCH_ASSOC);

    if ($accountExist) {
        session_start();
        $_SESSION['user_id'] = $accountExist['user_id'];

        $name = $accountExist['name'];
        $user_id = $accountExist['user_id'];

        echo "
        <script>
            alert('Login Successfully!');
            window.location.href = 'http://localhost/IMS/home.php';
        </script>
        "; 
    } else {
        echo "
        <script>
            alert('QR Code account doesn\'t exist!'); // Escaped single quote
            window.location.href = 'http://localhost/IMS/';
        </script>
        "; 
    }
}

?>
