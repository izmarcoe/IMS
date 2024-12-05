<?php
session_start();
include('./conn/conn.php');

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'employee') {
        header("Location: ./dashboards/employee_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] == 'new_user') {
        header("Location: ./home.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM `login_db` WHERE `email` = :email AND (`role` = 'employee' OR `role` = 'new_user')");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 'active') {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['Fname'] = $user['Fname'];
                $_SESSION['Lname'] = $user['Lname'];
                
                header("Location: " . ($user['role'] == 'employee' ? "./dashboards/employee_dashboard.php" : "../home.php"));
                exit();
            } else {
                $error = "Account is deactivated";
            }
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link rel="stylesheet" href="./src/output.css">
</head>
<body class="bg-gradient-to-br from-green-800 to-green-950 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Employee Login</h2>
                <img src="./icons/zefmaven.png" class="mx-auto w-32 h-32 my-4">
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- QR Code Scanner Video -->
            <div class="centered-video mb-4">
                <video id="interactive" class="viewport w-full rounded-lg"></video>
            </div>

            <!-- QR Code Detection Form -->
            <div class="qr-detected-container hidden">
                <form action="./endpoint/login.php" method="POST" class="text-center">
                    <h4 class="text-lg font-semibold mb-3">QR Code Detected!</h4>
                    <input type="hidden" id="detected-qr-code" name="qr-code">
                    <input type="hidden" name="login_type" value="employee">
                    <button type="submit" 
                            class="w-full py-2 px-4 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200">
                        Login
                    </button>
                </form>
            </div>

            <!-- Password Login Form -->
            <div id="passwordLoginForm" class="hidden">
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-200 hover:scale-105">
                        Sign in
                    </button>
                </form>
            </div>

            <!-- Login Toggle Options -->
            <div class="text-center mt-4 text-sm text-gray-600">
                <div>Login using</div>
                <div class="flex justify-center text-center space-x-4 mt-2">
                    <span id="qrCodeLoginLink"
                        class="text-green-600 hover:text-green-700 cursor-pointer hidden"
                        onclick="togglePasswordLogin(false)">
                        QR code
                    </span>
                    <span id="passwordLoginLink"
                        class="text-green-600 hover:text-green-700 cursor-pointer"
                        onclick="togglePasswordLogin(true)">
                        Password
                    </span>
                </div>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 mb-2">Don't have an account? 
                    <a href="./register.php" class="text-green-600 hover:text-green-700">Register here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="./JS/QR.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoElement = document.getElementById('interactive');
            const qrDetectedContainer = document.querySelector('.qr-detected-container');
            const qrInput = document.getElementById('detected-qr-code');
            const passwordForm = document.getElementById('passwordLoginForm');

            passwordForm.classList.add('hidden');
            
            let scanner = new Instascan.Scanner({ video: videoElement });
            
            scanner.addListener('scan', function(content) {
                console.log("QR Code detected:", content);
                qrInput.value = content;
                qrDetectedContainer.classList.remove('hidden');
            });

            Instascan.Camera.getCameras()
                .then(function(cameras) {
                    if (cameras.length > 0) {
                        scanner.start(cameras[0]);
                    } else {
                        console.error('No cameras found.');
                        togglePasswordLogin(true);
                    }
                })
                .catch(function(err) {
                    console.error('Error accessing cameras:', err);
                    togglePasswordLogin(true);
                });

            window.togglePasswordLogin = function(showPassword) {
                const qrScanner = document.querySelector('.centered-video');
                const qrLoginLink = document.getElementById('qrCodeLoginLink');
                const passwordLoginLink = document.getElementById('passwordLoginLink');

                if (showPassword) {
                    qrScanner.classList.add('hidden');
                    passwordForm.classList.remove('hidden');
                    qrLoginLink.classList.remove('hidden');
                    passwordLoginLink.classList.add('hidden');
                    if (scanner) scanner.stop();
                } else {
                    qrScanner.classList.remove('hidden');
                    passwordForm.classList.add('hidden');
                    qrLoginLink.classList.add('hidden');
                    passwordLoginLink.classList.remove('hidden');
                    if (scanner) scanner.start();
                }
            };
        });
    </script>
</body>
</html>