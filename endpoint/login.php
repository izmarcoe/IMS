<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
include('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null; // Using null coalescing operator for safety
    $password = $_POST['password'] ?? null; // Password for login
    $qrCode = $_POST['qr-code'] ?? null; // Using null coalescing operator for safety

    // Check if using QR code or email/password
    if (!empty($qrCode)) {
        if (isset($_POST['login_type']) && $_POST['login_type'] === 'admin') {
            // Verify admin QR code
            $stmt = $conn->prepare("SELECT * FROM login_db WHERE generated_code = :code AND role = 'admin'");
            $stmt->bindParam(':code', $qrCode);
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
                            window.location.href = 'http://localhost/IMS/dashboards/employee_dashboard.php';
                        </script>
                        ";
                    } else if ($_SESSION['user_role'] == 'admin') {
                        echo "
                        <script>
                            window.location.href = 'http://localhost/IMS/dashboards/admin_dashboard.php';
                        </script>
                        ";
                    } else if ($_SESSION['user_role'] == 'new_user') {
                        echo "
                        <script>
                            window.location.href = 'http://localhost/IMS/home.php';
                        </script>
                        ";
                    }
                } else {
                    // Inactive account - redirect to admin login
                    echo "
                    <script>
                         document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Account deactivated!',
                                    text: 'Please contact your admin.',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(function() {
                                    window.location.href = 'http://localhost/IMS/admin_login.php';
                                });
                            });
                    </script>
                    ";
                    exit();
                }
            } else {
                // Invalid QR code - redirect to admin login
                echo "
                <script>
                   document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid QR Code',
                                text: 'This QR code is not valid',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(function() {
                                window.location.href = 'http://localhost/IMS/admin_login.php';
                            });
                        });
                </script>
                ";
                exit();
            }
        } else if (isset($_POST['login_type']) && $_POST['login_type'] === 'employee') {
            // Handle QR code login
            $stmt = $conn->prepare("SELECT `generated_code`, `Fname`, `Lname`, `user_id`, `role`, `status` FROM `login_db` WHERE `generated_code` = :generated_code");
            $stmt->bindParam(':generated_code', $qrCode);
            $stmt->execute();

            $accountExist = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($accountExist) {
                // Check if user has appropriate role for employee login page
                if (!in_array($accountExist['role'], ['employee', 'new_user'])) {
                    echo "
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid QR Code',
                                text: 'This QR code is not valid',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(function() {
                                window.location.href = 'http://localhost/IMS/user_login.php';
                            });
                        });
                    </script>
                    ";
                    exit();
                }

                if ($accountExist['status'] == 'active') {
                    session_start();
                    $_SESSION['user_id'] = $accountExist['user_id'];
                    $_SESSION['user_role'] = $accountExist['role'];
                    $_SESSION['qr-code'] = $qrCode;

                    // Redirect based on role
                    if ($accountExist['role'] == 'employee') {
                        echo "
                        <script>
                            window.location.href = 'http://localhost/IMS/dashboards/employee_dashboard.php';
                        </script>
                        ";
                    } else {
                        echo "
                        <script>
                            window.location.href = 'http://localhost/IMS/home.php';
                        </script>
                        ";
                    }
                } else {
                    echo "
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Account deactivated!',
                                text: 'Please contact your admin.',
                                showConfirmButton: false,
                                timer: 2000
                            }).then(function() {
                                window.location.href = 'http://localhost/IMS/user_login.php';
                            });
                        });
                    </script>
                    ";
                    exit();
                }
            } else {
                echo "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid QR Code',
                            text: 'QR code not recognized',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(function() {
                            window.location.href = 'http://localhost/IMS/user_login.php';
                        });
                    });
                </script>
                ";
                exit();
            }
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
                        alert('Welcome!');
                        window.location.href = 'http://localhost/IMS/home.php';
                    </script>
                    ";
                }
            } else {
                // Handle the case when the account is inactive
                echo "
                <script>
                    alert('Account deactivated. Please contact your admin.');
                    window.location.href = 'http://localhost/IMS/user_login.php';
                </script>
                ";
            }
        } else {
            // Handle the case when the email/password is invalid
            // Determine login page source
            $isAdminLogin = strpos($_SERVER['HTTP_REFERER'], 'admin_login.php') !== false;
            $redirectUrl = $isAdminLogin ? 'admin_login.php' : 'user_login.php';

            echo "
            <script>
                alert('Invalid credentials!');
                window.location.href = 'http://localhost/IMS/$redirectUrl';
            </script>
            ";
            exit();
        }
    }
}