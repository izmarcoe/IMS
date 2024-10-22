<?php
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $qrCode = $_POST['qr-code'];

    $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id`, `role` FROM `login_db` WHERE `generated_code` = :generated_code");
    $stmt->bindParam(':generated_code', $qrCode);
    $stmt->execute();

    $accountExist =  $stmt->fetch(PDO::FETCH_ASSOC);

    if ($accountExist) {
        session_start();
        $_SESSION['user_id'] = $accountExist['user_id'];
        
        // Concatenate Fname and Lname to form the full name
        $name = $accountExist['Fname'] . ' ' . $accountExist['Lname'];
        $_SESSION['user_role'] = $accountExist['role']; // Ensure this matches your database column name
    
        // Check the user role for redirection
        if ($_SESSION['user_role'] == 'employee') {
            echo "
            <script>
                alert('Login Successfully!');
                window.location.href = 'http://localhost/IMS/dashboards/employee_dashboard.php';
            </script>
            ";
        } else {
            echo "
            <script>
                alert('Login Successfully!');
                window.location.href = 'http://localhost/IMS/home.php';
            </script>
            ";
        }
    
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