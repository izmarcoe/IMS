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
</head>

<body class="bg-gradient-to-br from-green-800 to-green-950 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Reset Your Password</h2>

            <form id="resetPasswordForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="new_password" required minlength="8"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
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
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            try {
                doc.setFontSize(16);
                doc.text('Your New QR Code Login Credentials', 105, 20, { align: 'center' });
                doc.setFontSize(12);
                doc.text('Please keep this QR code safe and private.', 20, 50);
                doc.addImage(qrImg, 'PNG', 65, 60, 80, 80);
                doc.setFontSize(10);
                doc.text('To login, use this QR code with the scanner on the login page.', 105, 160, { align: 'center' });
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
</body>
</html>