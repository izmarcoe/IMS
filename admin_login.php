<?php
session_start();
include('./conn/conn.php');

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'admin') {
    header("Location: ./dashboards/admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM `login_db` WHERE `email` = :email AND `role` = 'admin'");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 'active') {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['Fname'] = $user['Fname'];
                $_SESSION['Lname'] = $user['Lname'];
                header("Location: ./dashboards/admin_dashboard.php");
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
    <title>Admin Login</title>
    <link rel="stylesheet" href="./src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-green-800 to-green-950 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Admin Login</h2>
                <img src="./icons/zefmaven.png" class="mx-auto w-32 h-32 my-4">
            </div>

            <?php if (isset($error)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: '<?php echo $error; ?>',
                            confirmButtonColor: '#047857'
                        });
                    });
                </script>
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
                    <input type="hidden" name="login_type" value="admin">
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
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2" onclick="togglePassword()">
                                <i class="fas fa-eye" id="togglePassword"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <button type="button" onclick="openForgotPasswordModal()"
                            class="text-sm text-green-600 hover:text-green-800">
                            Forgot Password?
                        </button>
                    </div>

                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-200 hover:scale-105">
                        Sign in
                    </button>
                </form>
            </div>

            <!-- Login Toggle Options -->
            <div class="flex flex-col items-center justify-center mt-4">
                <div class="text-sm text-gray-600 mb-2">Login using</div>
                <div class="flex items-center justify-center space-x-6">
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
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="./JS/QR.js"></script>
    <script>
        function togglePasswordLogin(showPassword) {
            const qrScanner = document.querySelector('.centered-video');
            const passwordForm = document.getElementById('passwordLoginForm');
            const qrLoginLink = document.getElementById('qrCodeLoginLink');
            const passwordLoginLink = document.getElementById('passwordLoginLink');

            if (showPassword) {
                qrScanner.classList.add('hidden');
                passwordForm.classList.remove('hidden');
                qrLoginLink.classList.remove('hidden');
                passwordLoginLink.classList.add('hidden');
            } else {
                qrScanner.classList.remove('hidden');
                passwordForm.classList.add('hidden');
                qrLoginLink.classList.add('hidden');
                passwordLoginLink.classList.remove('hidden');
            }
        }
    </script>
    <script>
        // Initialize scanner when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const videoElement = document.getElementById('interactive');
            const qrDetectedContainer = document.querySelector('.qr-detected-container');
            const qrInput = document.getElementById('detected-qr-code');
            const passwordForm = document.getElementById('passwordLoginForm');

            // Hide password form initially
            passwordForm.classList.add('hidden');

            // Initialize scanner
            let scanner = new Instascan.Scanner({
                video: videoElement
            });

            // Handle successful scans
            scanner.addListener('scan', function(content) {
                console.log("QR Code detected:", content);
                qrInput.value = content;
                qrDetectedContainer.classList.remove('hidden');
                Swal.fire({
                    icon: 'success',
                    title: 'QR Code Detected!',
                    text: 'Processing login...',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                }).then(() => {
                    document.querySelector('.qr-detected-container form').submit();
                });
            });

            // Start camera
            Instascan.Camera.getCameras()
                .then(function(cameras) {
                    if (cameras.length > 0) {
                        scanner.start(cameras[0]);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Camera Not Found',
                            text: 'Switching to password login...',
                            confirmButtonColor: '#047857'
                        });
                        togglePasswordLogin(true);
                    }
                })
                .catch(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Camera Error',
                        text: 'Unable to access camera. Switching to password login...',
                        confirmButtonColor: '#047857'
                    });
                    togglePasswordLogin(true);
                });

            // Update toggle function to handle scanner
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
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Reset Password</h3>
                    <button onclick="closeForgotPasswordModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="forgotPasswordForm" action="./endpoint/reset_password.php" method="POST">
                    <input type="hidden" name="source" value="admin">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                        <input type="email" name="email" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeForgotPasswordModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Send Reset Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpVerificationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Enter OTP</h3>
                    <button onclick="closeOtpModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="otpVerificationForm" action="./endpoint/verify_otp.php" method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Enter OTP sent to your email</label>
                        <input type="text" name="otp" required maxlength="6" pattern="\d{6}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Verify OTP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add JavaScript for forgot password functionality -->
    <script>
        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
        }

        function closeOtpModal() {
            document.getElementById('otpVerificationModal').classList.add('hidden');
        }

        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('./endpoint/reset_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Sent!',
                            text: 'Please check your email for the OTP code',
                            confirmButtonColor: '#047857'
                        }).then(() => {
                            closeForgotPasswordModal();
                            document.getElementById('otpVerificationModal').classList.remove('hidden');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to send OTP',
                            confirmButtonColor: '#047857'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#047857'
                    });
                });
        });

        document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('./endpoint/verify_otp.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = './reset_password_form.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid OTP',
                            text: data.message,
                            confirmButtonColor: '#047857'
                        });
                    }
                });
        });
    </script>
</body>

</html>