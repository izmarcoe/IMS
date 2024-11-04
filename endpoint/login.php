<?php
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null; // Using null coalescing operator for safety
    $password = $_POST['password'] ?? null; // Password for login
    $qrCode = $_POST['qr-code'] ?? null; // Using null coalescing operator for safety

    // Check if using QR code or email/password
    if (!empty($qrCode)) {
        // Handle QR code login
        $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id`, `role` FROM `login_db` WHERE `generated_code` = :generated_code");
        $stmt->bindParam(':generated_code', $qrCode);
        $stmt->execute();

        $accountExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($accountExist) {
            session_start();
            $_SESSION['user_id'] = $accountExist['user_id'];
            $_SESSION['user_role'] = $accountExist['role'];
            $_SESSION['qr-code'] = $qrCode;
    
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
            // Optionally hide the error for QR code
            echo "
            <script>
                alert('QR Code account doesn\'t exist!'); // You may remove this if you want to hide the message
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    } else {
        // Handle email/password login
        $stmt = $conn->prepare("SELECT `user_id`, `Fname`, `Lname`, `role`, `password` FROM `login_db` WHERE `email` = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['role'];
    
            // Check the user role for redirection
            if ($_SESSION['user_role'] == 'employee') {
                echo "
                <script>
                    alert('Login Successfully!');
                    window.location.href = 'http://localhost/IMS/dashboards/employee_dashboard.php';
                </script>
                ";
            } 
            // Check the user role for redirection
            else if ($_SESSION['user_role'] == 'admin') {
                echo "
                <script>
                    alert('Login Successfully!');
                    window.location.href = 'http://localhost/IMS/dashboards/admin_dashboard.php';
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
            // Hide the error message
            echo "
            <script>
                // alert('Invalid email or password!'); // Comment this out to hide
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    }
}
?>
