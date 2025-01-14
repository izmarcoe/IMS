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
    <script>
        function handleInput(inputId) {
            const input = document.getElementById(inputId);
            const asterisk = document.getElementById(`${inputId}Asterisk`);
            asterisk.style.display = input.value ? 'none' : 'inline';
        }
    </script>
</head>

<body class="flex items-center justify-center min-h-screen p-6 bg-gradient-to-br from-green-800 to-green-950">

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

    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Registration Form</h2>
                <p class="text-gray-500 mt-2">Fill in your personal details</p>
            </div>

            <form action="./endpoint/add-user.php" method="POST" class="space-y-6" id="registrationForm" enctype="application/x-www-form-urlencoded">                <div class="hide-registration-inputs">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700">
                                First Name<span id="firstNameAsterisk" class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="text" id="firstName" name="first_name" required maxlength="25" pattern="[a-zA-Z]+" oninput="handleInput('firstName')" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700">
                                Last Name<span id="lastNameAsterisk" class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="text" id="lastName" name="last_name" required maxlength="25" pattern="[a-zA-Z]+" oninput="handleInput('lastName')" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="contactNumber" class="block text-sm font-medium text-gray-700">
                                Contact Number<span id="contactNumberAsterisk" class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="tel"
                                id="contactNumber"
                                name="contact_number"
                                required
                                maxlength="11"
                                pattern="[0-9]+"
                                placeholder="09XXXXXXXXX"
                                inputmode="numeric"
                                onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                oninput="handleInput('contactNumber'); this.value = this.value.replace(/[^0-9]/g, '')"
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email<span id="emailAsterisk" class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                oninput="handleInput('email')"
                                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password<span id="passwordAsterisk" class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative group">
                                <input type="password" id="password" name="password" required
                                    oninput="handleInput('password')"
                                    class="mt-1 block w-full px-3 py-2 pr-10 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent focus:invalid:ring-red-500 focus:invalid:border-red-500">
                                <button type="button" onclick="togglePassword('password', 'togglePassword1')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1">
                                    <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword1"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirmPassword" name="confirm_password" required
                                    class="mt-1 block w-full px-3 py-2 pr-10 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent focus:invalid:ring-red-500 focus:invalid:border-red-500">
                                <button type="button" onclick="togglePassword('confirmPassword', 'togglePassword2')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1">
                                    <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="togglePassword2"></i>
                                </button>
                            </div>
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
                const passwordValid = validatePassword();

                registerButton.disabled = !allFilled || !passwordsMatch || !emailIsValid || !passwordValid;
            }

            formInputs.forEach(input => {
                input.addEventListener('input', validateForm);
            });

            window.generateQrCode = async function() {
                const fname = document.getElementById('firstName').value; // Changed from fname to firstName
                const lname = document.getElementById('lastName').value; // Changed from lname to lastName
                
                // Show loading overlay
                Swal.fire({
                    title: 'Generating QR Code...',
                    html: 'Please wait while we generate your QR code and prepare your PDF.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    // Generate random code
                    const text = generateRandomCode(10);
                    const secretKey = 'artificial intelligence';
                    const encryptedText = encryptData(text, secretKey);
                    document.getElementById("generatedCode").value = encryptedText;

                    // Generate QR Code
                    const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(encryptedText)}`;
                    const qrImg = document.getElementById('qrImg');
                    
                    // Hide registration inputs and show QR code
                    document.querySelector('.hide-registration-inputs').classList.add('hidden');
                    document.querySelector('.qr-code-container').classList.remove('hidden');
                    document.getElementById('registerButton').classList.add('hidden');

                    // Wait for QR image to load
                    await new Promise((resolve, reject) => {
                        qrImg.onload = resolve;
                        qrImg.onerror = reject;
                        qrImg.src = apiUrl;
                    });

                    // Generate PDF
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();

                    // Add user info to PDF
                    doc.setFontSize(16);
                    doc.text('Your QR Code Login Credentials', 105, 20, {align: 'center'});
                    doc.setFontSize(12);
                    doc.text(`Name: ${fname} ${lname}`, 20, 40);
                    doc.text('Please keep this QR code safe and private.', 20, 50);
                    
                    // Add QR code to PDF
                    doc.addImage(qrImg, 'PNG', 65, 60, 80, 80);
                    
                    // Add instructions
                    doc.setFontSize(10);
                    doc.text('To login, use this QR code with the scanner on the login page.', 105, 160, {align: 'center'});
                    
                    // Save PDF
                    doc.save(`QRCode_${fname}${lname}.pdf`);

                    // Close loading overlay and show success message
                    await Swal.fire({
                        icon: 'success',
                        title: 'QR Code Generated!',
                        text: 'Your QR code has been generated and PDF has been downloaded.',
                        confirmButtonColor: '#047857',
                        showConfirmButton: true,
                        allowOutsideClick: false
                    });

                    // Submit the form
                    document.getElementById('registrationForm').submit();

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'There was an error generating your QR code. Please try again.',
                        confirmButtonColor: '#047857'
                    });
                }
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

            let hasInteractedWithPassword = false;

            function validatePassword() {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirmPassword');
                const requirements = {
                    length: password.value.length >= 8,
                    uppercase: /[A-Z]/.test(password.value),
                    lowercase: /[a-z]/.test(password.value),
                    number: /[0-9]/.test(password.value),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password.value)
                };

                // Only show validation styling if user has interacted with password
                if (!hasInteractedWithPassword) {
                    password.style.borderColor = '#D1D5DB'; // gray-300
                    password.style.boxShadow = 'none';
                    return true;
                }

                const isValid = Object.values(requirements).every(req => req === true);
                const passwordsMatch = password.value === confirmPassword.value;

                // Update password field styling only if user has interacted
                if (!isValid && hasInteractedWithPassword) {
                    password.style.borderColor = '#ef4444';
                    password.style.boxShadow = '0 0 0 1px #ef4444';
                } else if (isValid && hasInteractedWithPassword) {
                    password.style.borderColor = '#10b981'; // Green for valid
                    password.style.boxShadow = '0 0 0 1px #10b981';
                } else {
                    password.style.borderColor = '#D1D5DB';
                    password.style.boxShadow = 'none';
                }

                // Update confirm password field styling
                if (!passwordsMatch && confirmPassword.value) {
                    confirmPassword.style.borderColor = '#ef4444'; // red-500
                    confirmPassword.style.boxShadow = '0 0 0 1px #ef4444';
                } else if (passwordsMatch && confirmPassword.value) {
                    confirmPassword.style.borderColor = '#10b981';
                    confirmPassword.style.boxShadow = '0 0 0 1px #10b981';
                } else {
                    confirmPassword.style.borderColor = '';
                    confirmPassword.style.boxShadow = '';
                }

                return isValid && passwordsMatch;
            }

            password.addEventListener('input', function(e) {
                // Prevent spaces
                if (e.target.value.includes(' ')) {
                    e.target.value = e.target.value.replace(/\s/g, '');
                }

                validatePassword();
                const password = this.value;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

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
            });

            // Add blur event listener
            password.addEventListener('blur', function() {
                if (!this.value) {
                    hasInteractedWithPassword = false;
                    // Reset to default state if empty
                    this.style.borderColor = '#D1D5DB'; // gray-300
                    this.style.boxShadow = 'none';
                } else if (!validatePassword()) {
                    // Keep red if invalid
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 1px #ef4444';
                } else {
                    // Reset to default if valid
                    this.style.borderColor = '#D1D5DB';
                    this.style.boxShadow = 'none';
                }
            });

            // Add focus event for better UX
            password.addEventListener('focus', function() {
                hasInteractedWithPassword = true;
                validatePassword();
                if (this.value && !validatePassword()) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 1px #ef4444';
                }
            });

            confirmPassword.addEventListener('input', validatePassword);

            const emailInput = document.getElementById('email');
            let emailIsValid = true;

            emailInput.addEventListener('input', function() {
                const email = this.value;
                if (email) {
                    fetch('./endpoint/check-email.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `email=${encodeURIComponent(email)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                emailIsValid = false;
                                emailInput.style.borderColor = '#ef4444';
                                emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                                registerButton.disabled = true;

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Email Already Exists',
                                    text: 'Please use a different email address',
                                    confirmButtonColor: '#047857'
                                });
                            } else {
                                emailIsValid = true;
                                emailInput.style.borderColor = '#10b981';
                                emailInput.style.boxShadow = '';
                                validateForm();
                            }
                        });
                }
            });
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

        document.getElementById('password').addEventListener('focus', function() {
            document.getElementById('passwordRequirements').classList.remove('hidden');
        });

        // Optional: Hide requirements when clicking outside
        document.addEventListener('click', function(e) {
            const requirements = document.getElementById('passwordRequirements');
            const passwordInput = document.getElementById('password');
            if (!requirements.contains(e.target) && e.target !== passwordInput) {
                requirements.classList.add('hidden');
            }
        });
    </script>
</body>

</html>