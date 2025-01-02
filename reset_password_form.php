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
</head>

<body class="bg-gradient-to-br from-green-800 to-green-950 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Reset Your Password</h2>

            <form id="resetPasswordForm" action="./endpoint/update_password.php" method="POST" class="space-y-6">
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

                <button type="submit"
                    class="w-full py-2 px-4 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200">
                    Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
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

        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const password = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            // Password validation
            const validation = validatePassword(password);

            // Password match check
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

            // Form submission
            fetch('./endpoint/update_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Reset Successful',
                            text: 'You can now login with your new password',
                            confirmButtonColor: '#047857'
                        }).then(() => {
                            window.location.href = 'user_login.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#047857'
                        });
                    }
                });
        });
    </script>
</body>

</html>