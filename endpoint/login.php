<?php
include ('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null; // Using null coalescing operator for safety
    $password = $_POST['password'] ?? null; // Password for login
    $qrCode = $_POST['qr-code'] ?? null; // Using null coalescing operator for safety

    // Check if using QR code or email/password
    if (!empty($qrCode)) {
        // Handle QR code login
        $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id`, `role`, `status` FROM `login_db` WHERE `generated_code` = :generated_code");
        $stmt->bindParam(':generated_code', $qrCode);
        $stmt->execute();

        $accountExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($accountExist) {
            if ($accountExist['status'] == 'active') {
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
                } else if ($_SESSION['user_role'] == 'admin') {
                    echo "
                    <script>
                        alert('Login Successfully!');
                        window.location.href = 'http://localhost/IMS/dashboards/admin_dashboard.php';
                    </script>
                    ";
                } else if ($_SESSION['user_role'] == 'new_user') {
                    echo "
                    <script>
                        alert('Login Successfully!');
                        window.location.href = 'http://localhost/IMS/home.php';
                    </script>
                    ";
                }
            } else {
                // Handle the case when the account is inactive
                echo "
                <script>
                    alert('Account deactivated. Contact your admin.');
                    window.location.href = 'http://localhost/IMS/';
                </script>
                ";
            }
        } else {
            // Handle the case when the QR code account doesn't exist
            echo "
            <script>
                alert('Invalid QR code!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    } else {
        // Handle email/password login
        $stmt = $conn->prepare("SELECT `user_id`, `Fname`, `Lname`, `role`, `password`, `status` FROM `login_db` WHERE `email` = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 'active') {
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
                } else if ($_SESSION['user_role'] == 'admin') {
                    echo "
                    <script>
                        alert('Login Successfully!');
                        window.location.href = 'http://localhost/IMS/dashboards/admin_dashboard.php';
                    </script>
                    ";
                } else if ($_SESSION['user_role'] == 'new_user') {
                    echo "
                    <script>
                        alert('Login Successfully!');
                        window.location.href = 'http://localhost/IMS/home.php';
                    </script>
                    ";
                }
            } else {
                // Handle the case when the account is inactive
                echo "
                <script>
                    alert('Account deactivated. Please contact your admin.');
                    window.location.href = 'http://localhost/IMS/';
                </script>
                ";
            }
        } else {
            // Handle the case when the email/password is invalid
            echo "
            <script>
                alert('Invalid email or password!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    }
}
?>