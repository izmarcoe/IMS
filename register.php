<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'employee') {
        header("Location: dashboards/employee_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] == 'admin') {
        header("Location: dashboards/admin_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="./src/output.css">
    <style>
        body {
            background: linear-gradient(323deg, #0F7505 25.5%, #0C2809 79.98%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Registration Form</h2>
                <p class="text-gray-500 mt-2">Fill in your personal details</p>
            </div>

            <form action="./endpoint/add-user.php" method="POST" class="space-y-6" id="registrationForm">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" id="fname" name="fname" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" id="lname" name="lname" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="contactNumber" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="tel" id="contactNumber" name="contact_number" required maxlength="11"
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <input type="hidden" name="role" value="new_user">
                <input type="hidden" id="generatedCode" name="generated_code">

                <div class="qr-code-container text-center hidden">
                    <h3 class="text-xl font-semibold mb-4">Take a Picture of your QR Code and Login!</h3>
                    <div class="m-4" id="qrBox">
                        <img src="" id="qrImg" class="mx-auto">
                    </div>
                </div>

                <div class="flex flex-col space-y-4">
                    <button type="button" id="registerButton" onclick="generateQrCode()" disabled
                        class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        Register and Generate QR Code
                    </button>
                    
                    <div class="text-center">
                        <a href="./employee_login.php" class="text-sm text-green-600 hover:text-green-700">
                            Already have an account? Login here
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <script src="./JS/QR.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerButton = document.getElementById('registerButton');
            const formInputs = document.querySelectorAll('#registrationForm input[required]');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');

            function validateForm() {
                let allFilled = true;
                formInputs.forEach(input => {
                    if (!input.value) allFilled = false;
                });

                const passwordsMatch = password.value === confirmPassword.value;
                registerButton.disabled = !allFilled || !passwordsMatch;
            }

            formInputs.forEach(input => {
                input.addEventListener('input', validateForm);
            });
        });
    </script>
</body>
</html>