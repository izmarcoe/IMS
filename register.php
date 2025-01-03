<?php
session_start();
include('./conn/conn.php');
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
    } elseif ($_SESSION['user_role'] == 'new_user') {
        header("Location: home.php");
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body class="flex items-center justify-center min-h-screen p-6 bg-gradient-to-br from-green-800 to-green-950">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Registration Form</h2>
                <p class="text-gray-500 mt-2">Fill in your personal details</p>
            </div>

            <form action="./endpoint/add-user.php" method="POST" class="space-y-6" id="registrationForm">
                <div class="hide-registration-inputs">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="fname" name="fname" required maxlength="25"
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lname" name="lname" required maxlength="25"
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="contactNumber" class="block text-sm font-medium text-gray-700">Contact Number</label>
                            <input type="text" id="contactNumber" name="contact_number" required maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" required
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <button type="button" class="ml-2" onclick="togglePassword('password', 'togglePassword1')">
                                    <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword1"></i>
                                </button>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <button type="button" class="ml-2" onclick="togglePassword('confirmPassword', 'togglePassword2')">
                                    <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword2"></i>
                                </button>
                            </div>
                            <input type="password" id="confirmPassword" name="confirm_password" required
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <input type="hidden" name="role" value="new_user">
                        <input type="hidden" id="generatedCode" name="generated_code">
                    </div>

                    <div class="qr-code-container hidden text-center">
                        <h3 class="text-xl font-semibold mb-4">Your QR Code is Ready!</h3>
                        <p class="text-gray-600 mb-4">Please save this QR code - you'll need it to login</p>
                        <div class="m-4" id="qrBox">
                            <img src="" id="qrImg" class="mx-auto rounded-lg shadow-lg">
                        </div>
                        <div class="mt-4 space-y-4">
                            <button type="submit"
                                class="w-full py-2 px-4 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors duration-200">
                                Complete Registration
                            </button>
                            <p class="text-sm text-gray-600">A PDF copy will be downloaded automatically</p>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-4 mt-4">
                        <button type="button" id="registerButton" onclick="generateQrCode()" disabled
                            class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                            Register and Generate QR Code
                        </button>

                        <div class="text-center">
                            <a href="./user_login.php" class="text-sm text-green-600 hover:text-green-700">
                                Already have an account? Login here
                            </a>
                        </div>
                    </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <script src="./JS/QR.js"></script>
    <script src="./JS/form-validation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerButton = document.getElementById('registerButton');
            const formInputs = document.querySelectorAll('#registrationForm input[required]');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const registrationInputs = document.querySelector('.hide-registration-inputs');
            const qrCodeContainer = document.querySelector('.qr-code-container');

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

            window.generateQrCode = function() {
                // Show loading overlay
                Swal.fire({
                    title: 'Generating QR Code...',
                    html: 'Please wait while we generate your QR code and prepare your PDF.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const fname = document.getElementById('fname').value;
                const lname = document.getElementById('lname').value;

                // Generate random code
                const text = generateRandomCode(10);
                const secretKey = 'artificial intelligence';
                const encryptedText = encryptData(text, secretKey);
                document.getElementById("generatedCode").value = encryptedText;

                // Generate QR Code
                const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(encryptedText)}`;
                const qrImg = document.getElementById('qrImg');

                // Hide registration inputs and show QR code
                registrationInputs.classList.add('hidden');
                qrCodeContainer.classList.remove('hidden');
                registerButton.classList.add('hidden');

                // Set QR image and create PDF
                qrImg.src = apiUrl;
                qrImg.onload = function() {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF();

                    try {
                        // Add user info to PDF
                        doc.setFontSize(16);
                        doc.text('Your QR Code Login Credentials', 105, 20, {
                            align: 'center'
                        });
                        doc.setFontSize(12);
                        doc.text(`Name: ${fname} ${lname}`, 20, 40);
                        doc.text('Please keep this QR code safe and private.', 20, 50);

                        // Add QR code to PDF
                        doc.addImage(qrImg, 'PNG', 65, 60, 80, 80);

                        // Add instructions
                        doc.setFontSize(10);
                        doc.text('To login, use this QR code with the scanner on the login page.', 105, 160, {
                            align: 'center'
                        });

                        // Save PDF
                        doc.save(`QRCode_${fname}${lname}.pdf`);

                        // Close loading overlay and show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'QR Code Generated!',
                            text: 'Your QR code has been generated and PDF has been downloaded.',
                            confirmButtonColor: '#047857',
                            showConfirmButton: true,
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Automatically submit the form after user acknowledges
                                document.getElementById('registrationForm').submit();
                            }
                        });
                    } catch (error) {
                        // Handle any errors during PDF generation
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'There was an error generating your QR code. Please try again.',
                            confirmButtonColor: '#047857'
                        });
                        console.error('PDF generation error:', error);
                    }
                };

                // Handle QR code loading error
                qrImg.onerror = function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to generate QR code. Please try again.',
                        confirmButtonColor: '#047857'
                    });
                };
            }

            function generateRandomCode(length) {
                const characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
                let randomString = "";
                for (let i = 0; i < length; i++) {
                    randomString += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                return randomString;
            }

            function encryptData(data, secretKey) {
                return CryptoJS.AES.encrypt(data, secretKey).toString();
            }
        });
    </script>
    <script>
        function togglePassword(inputId, toggleIconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(toggleIconId);

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
</body>

</html>