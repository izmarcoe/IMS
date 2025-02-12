<?php
session_start();
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header('Location: user_login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="./src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body class="bg-gradient-to-br from-green-800 to-green-950 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Reset Your Password</h2>

            <form id="resetPasswordForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <div class="relative">
                        <input type="password" id="newPassword" name="new_password" required minlength="8"
                            class="mt-1 block w-full px-3 py-2 pr-10 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent focus:invalid:ring-red-500 focus:invalid:border-red-500">
                        <button type="button" onclick="togglePassword('newPassword', 'togglePassword1')"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1">
                            <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword1"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" name="confirm_password" required minlength="8"
                            class="mt-1 block w-full px-3 py-2 pr-10 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent focus:invalid:ring-red-500 focus:invalid:border-red-500">
                        <button type="button" onclick="togglePassword('confirmPassword', 'togglePassword2')"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1">
                            <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword2"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" id="generatedCode" name="generated_code">

                <!-- QR Code Container -->
                <div id="qrCodeContainer" class="hidden text-center mt-6">
                    <h3 class="text-xl font-semibold mb-4">Your New QR Code</h3>
                    <p class="text-gray-600 mb-4">Please save this QR code - you'll need it to login</p>
                    <div class="m-4">
                        <img id="qrImg" class="mx-auto rounded-lg shadow-lg">
                    </div>
                    <p class="text-sm text-gray-600 mt-2">A PDF copy will be downloaded automatically</p>
                </div>

                <button type="submit"
                    class="w-full py-2 px-4 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200">
                    Reset Password
                </button>
            </form>
        </div>
    </div>

    <div id="passwordRequirements" class="hidden fixed top-1/2 w-72 p-6 bg-white border border-gray-200 rounded-lg shadow-lg transform -translate-y-1/2" style="left: 300px;">
        <h4 class="font-semibold text-lg text-gray-800 mb-4">Password Requirements</h4>
        <ul class="space-y-3">
            <li id="length" class="flex items-center text-sm text-gray-600">
                <i class="fas fa-times mr-3 text-red-500 w-5"></i>
                <span>Minimum 8 characters</span>
            </li>
            <li id="uppercase" class="flex items-center text-sm text-gray-600">
                <i class="fas fa-times mr-3 text-red-500 w-5"></i>
                <span>One uppercase letter</span>
            </li>
            <li id="lowercase" class="flex items-center text-sm text-gray-600">
                <i class="fas fa-times mr-3 text-red-500 w-5"></i>
                <span>One lowercase letter</span>
            </li>
            <li id="number" class="flex items-center text-sm text-gray-600">
                <i class="fas fa-times mr-3 text-red-500 w-5"></i>
                <span>One number</span>
            </li>
            <li id="special" class="flex items-center text-sm text-gray-600">
                <i class="fas fa-times mr-3 text-red-500 w-5"></i>
                <span>One special character</span>
            </li>
        </ul>
    </div>

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
    <script>
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

        function validatePassword(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            const missingReqs = [];
            if (!requirements.length) missingReqs.push('at least 8 characters');
            if (!requirements.uppercase) missingReqs.push('1 uppercase letter');
            if (!requirements.lowercase) missingReqs.push('1 lowercase letter');
            if (!requirements.number) missingReqs.push('1 number');
            if (!requirements.special) missingReqs.push('1 special character');

            return {
                isValid: missingReqs.length === 0,
                missing: missingReqs
            };
        }

        document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const password = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            // Password validation
            const validation = validatePassword(password);

            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords do not match',
                    confirmButtonColor: '#047857'
                });
                return;
            }

            if (!validation.isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Requirements Not Met',
                    html: 'Password must contain:<br>' + validation.missing.join('<br>'),
                    confirmButtonColor: '#047857'
                });
                return;
            }

            // Generate new QR code
            const randomCode = generateRandomCode(10);
            const secretKey = 'artificial intelligence';
            const encryptedCode = encryptData(randomCode, secretKey);
            document.getElementById('generatedCode').value = encryptedCode;

            // Generate QR Code and show loading
            Swal.fire({
                title: 'Generating New QR Code...',
                html: 'Please wait while we process your request.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(encryptedCode)}`;
            const qrImg = document.getElementById('qrImg');
            qrImg.src = apiUrl;

            // Wait for QR code to load
            await new Promise((resolve, reject) => {
                qrImg.onload = resolve;
                qrImg.onerror = reject;
            });

            // Generate PDF
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            try {
                doc.setFontSize(16);
                doc.text('Your New QR Code Login Credentials', 105, 20, {
                    align: 'center'
                });
                doc.setFontSize(12);
                doc.text('Please keep this QR code safe and private.', 20, 50);
                doc.addImage(qrImg, 'PNG', 65, 60, 80, 80);
                doc.setFontSize(10);
                doc.text('To login, use this QR code with the scanner on the login page.', 105, 160, {
                    align: 'center'
                });
                doc.save('New_QRCode.pdf');

                // Show QR code container
                document.getElementById('qrCodeContainer').classList.remove('hidden');

                // Submit form data to server
                formData.append('generated_code', encryptedCode);
                const response = await fetch('./endpoint/update_password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Reset Successful',
                        text: 'Your password and QR code have been updated. You can now login with either option.',
                        confirmButtonColor: '#047857'
                    }).then(() => {
                        window.location.href = 'user_login.php';
                    });
                } else {
                    throw new Error(data.message || 'Failed to update password');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred. Please try again.',
                    confirmButtonColor: '#047857'
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const submitButton = document.querySelector('button[type="submit"]');

            function validatePassword() {
                const password = newPassword.value;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                const isValid = Object.values(requirements).every(req => req === true);
                const passwordsMatch = password === confirmPassword.value;

                // Update password field styling
                if (!isValid) {
                    newPassword.style.borderColor = '#ef4444';
                    newPassword.style.boxShadow = '0 0 0 1px #ef4444';
                } else {
                    newPassword.style.borderColor = '#10b981';
                    newPassword.style.boxShadow = '0 0 0 1px #10b981';
                }

                // Update confirm password field styling - only show green if password is valid AND matches
                if (confirmPassword.value) {
                    if (!isValid || !passwordsMatch) {
                        confirmPassword.style.borderColor = '#ef4444';
                        confirmPassword.style.boxShadow = '0 0 0 1px #ef4444';
                    } else {
                        confirmPassword.style.borderColor = '#10b981';
                        confirmPassword.style.boxShadow = '0 0 0 1px #10b981';
                    }
                }

                // Update requirement icons
                Object.keys(requirements).forEach(req => {
                    const element = document.getElementById(req);
                    const icon = element.querySelector('i');

                    if (requirements[req]) {
                        icon.classList.remove('fa-times', 'text-red-500');
                        icon.classList.add('fa-check', 'text-green-500');
                        element.classList.add('text-green-600');
                    } else {
                        icon.classList.remove('fa-check', 'text-green-500');
                        icon.classList.add('fa-times', 'text-red-500');
                        element.classList.remove('text-green-600');
                    }
                });

                return isValid && passwordsMatch;
            }

            newPassword.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);

            // Show/hide password requirements
            newPassword.addEventListener('focus', function() {
                document.getElementById('passwordRequirements').classList.remove('hidden');
            });

            document.addEventListener('click', function(e) {
                const requirements = document.getElementById('passwordRequirements');
                if (!requirements.contains(e.target) && e.target !== newPassword) {
                    requirements.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>