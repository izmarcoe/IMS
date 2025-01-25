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

            <form action="./endpoint/add-user.php" method="POST" class="space-y-6" id="registrationForm" enctype="application/x-www-form-urlencoded">
                <div class="hide-registration-inputs">
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
                                <input type="password" id="password" name="password" required onpaste="return false"
                                    oncopy="return false"
                                    oncut="return false"
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
                                <input type="password" id="confirmPassword" name="confirm_password" required onpaste="return false"
                                    oncopy="return false"
                                    oncut="return false"
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

                    <div class="flex items-center space-x-2 mt-4">
                        <input type="checkbox" id="termsCheckbox" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                        <label for="termsCheckbox" class="text-sm text-gray-600">
                            I agree to the
                            <button type="button" onclick="showTermsModal()" class="text-green-600 hover:text-green-700 font-medium">
                                Terms and Conditions
                            </button>
                        </label>
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


    <div id="termsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Terms and Conditions</h3>
                <div class="mt-2 px-7 py-3 text-left">
                    <div class="text-sm text-gray-600 max-h-60 overflow-y-auto">
                        <p class="mb-4">
                            Welcome to the ZefMaven Inventory Management System. By using our system, you agree to comply with and be bound by the following terms and conditions. Please read them carefully before accessing or using our services.
                        </p>

                        <div class="space-y-4">
                            <section>
                                <h3 class="font-semibold">1. Acceptance of Terms</h3>
                                <p>By accessing or using the ZefMaven Inventory Management System, you agree to these Terms and Conditions. If you do not agree to these terms, you must not use the system.</p>
                            </section>

                            <section>
                                <h3 class="font-semibold">2. Use of the System</h3>
                                <p>The system is intended for managing and tracking inventory items for ZefMaven Computer Shop and Accessories. Users are required to register and authenticate via QR code to access the system.</p>
                                <p class="mt-2">You agree to:</p>
                                <ul class="list-disc pl-6 mt-1">
                                    <li>Use the system only for lawful purposes.</li>
                                    <li>Not engage in activities that could harm, disrupt, or interfere with the functioning of the system.</li>
                                </ul>
                            </section>

                            <section>
                                <h3 class="font-semibold">3. Registration and Authentication</h3>
                                <ul class="list-disc pl-6">
                                    <li>Users must complete the registration process, providing accurate information.</li>
                                    <li>The system uses QR code authentication to verify users and grant access to the inventory management features.</li>
                                    <li>You are responsible for maintaining the confidentiality of your login details and QR code credentials.</li>
                                </ul>
                            </section>

                            <section>
                                <h3 class="font-semibold">4. Privacy and Data Protection</h3>
                                <ul class="list-disc pl-6">
                                    <li>We value your privacy and are committed to protecting your personal information.</li>
                                    <li>Any personal information provided during the registration or use of the system will be handled according to our Privacy Policy.</li>
                                    <li>By using this system, you consent to the collection and use of your data as described in our Privacy Policy.</li>
                                </ul>
                            </section>
                            <section>
                                <h3 class="font-semibold">5. Predictive Analytics</h3>
                                <p>The system integrates predictive analytics for inventory tracking. While the system aims to provide accurate predictions, users acknowledge that the accuracy of predictions depends on various factors and may not always reflect real-time changes or future trends.</p>
                            </section>

                            <section>
                                <h3 class="font-semibold">6. System Availability</h3>
                                <p>The system may experience downtime or temporary unavailability due to maintenance, updates, or unforeseen technical issues. We will make reasonable efforts to ensure the system is available at all times but cannot guarantee continuous access.</p>
                                <p class="mt-2">Users will be notified in advance of any planned maintenance.</p>
                            </section>

                            <section>
                                <h3 class="font-semibold">7. User Obligations</h3>
                                <p>You agree to:</p>
                                <ul class="list-disc pl-6 mt-1">
                                    <li>Keep your account information up to date.</li>
                                    <li>Not share your access credentials with unauthorized persons.</li>
                                    <li>Not attempt to bypass system security or access areas that are not authorized.</li>
                                </ul>
                            </section>
                            <section>
                                <h3 class="font-semibold">8. Limitation of Liability</h3>
                                <ul class="list-disc pl-6">
                                    <li>ZefMaven Computer Shop and Accessories will not be held liable for any damages resulting from the use or inability to use the system.</li>
                                    <li>We are not responsible for any loss of data, disruption of business, or any indirect or consequential damages caused by system errors or malfunctions.</li>
                                </ul>
                            </section>

                            <section>
                                <h3 class="font-semibold">9. Intellectual Property</h3>
                                <p>The content and software used in the ZefMaven Inventory Management System are protected by intellectual property laws. Users agree not to copy, modify, or distribute any part of the system without explicit permission.</p>
                            </section>

                            <section>
                                <h3 class="font-semibold">10. Termination</h3>
                                <p>We reserve the right to suspend or terminate your access to the system if you violate any terms in this agreement. Upon termination, your access to the system will be revoked.</p>
                            </section>

                            <section>
                                <h3 class="font-semibold">11. Changes to the Terms and Conditions</h3>
                                <p>ZefMaven reserves the right to modify or update these Terms and Conditions at any time. Any changes will be posted on this page with an updated effective date. It is your responsibility to review these terms periodically.</p>
                            </section>
                        </div>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="closeTermsModal" class="px-4 py-2 bg-green-700 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckbox = document.getElementById('termsCheckbox');
            const registerButton = document.getElementById('registerButton');
            const modal = document.getElementById('termsModal');
            const closeButton = document.getElementById('closeTermsModal');

            // Toggle button state based on checkbox
            termsCheckbox.addEventListener('change', function() {
                registerButton.disabled = !this.checked;
            });

            // Show terms modal
            window.showTermsModal = function() {
                modal.classList.remove('hidden');
            }

            // Close modal on button click
            closeButton.addEventListener('click', function() {
                modal.classList.add('hidden');
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (!document.getElementById('termsCheckbox').checked) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Terms & Conditions',
                    text: 'Please accept the terms and conditions to continue',
                    confirmButtonColor: '#047857'
                });
            }
        });
    </script>

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

            const firstName = document.getElementById('firstName');
            const lastName = document.getElementById('lastName');

            // Add trim function for name fields
            function trimNameFields() {
                if (firstName) {
                    firstName.value = firstName.value.trim();
                }
                if (lastName) {
                    lastName.value = lastName.value.trim();
                }
            }

            // Add blur event listeners
            firstName.addEventListener('blur', trimNameFields);
            lastName.addEventListener('blur', trimNameFields);

            function validateForm() {
                let allFilled = true;
                formInputs.forEach(input => {
                    if (input.id === 'firstName' || input.id === 'lastName') {
                        if (!input.value.trim()) {
                            allFilled = false;
                        }
                    } else if (!input.value) {
                        allFilled = false;
                    }
                });

                const passwordsMatch = password.value === confirmPassword.value;
                const passwordValid = validatePassword();

                registerButton.disabled = !allFilled || !passwordsMatch || !emailIsValid || !passwordValid;
            }

            formInputs.forEach(input => {
                input.addEventListener('input', validateForm);
            });

            window.generateQrCode = async function() {
                // Check terms acceptance first
                if (!document.getElementById('termsCheckbox').checked) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Terms & Conditions Required',
                        text: 'Please agree to the terms and conditions first before proceeding.',
                        confirmButtonColor: '#047857'
                    });
                    return; // Stop execution if terms not accepted
                }

                // Validate email first
                const emailInput = document.getElementById('email');
                const email = emailInput.value.trim();
                const emailValidation = validateEmail(email);

                if (!emailValidation.isValid) {
                    // Build detailed error message
                    let errorMessage = 'Please fix the following email issues:\n';
                    if (!emailValidation.checks.hasAtSymbol) errorMessage += '- Email must contain @ symbol\n';
                    if (!emailValidation.checks.hasValidLength) errorMessage += '';
                    if (!emailValidation.checks.noConsecutiveDots) errorMessage += '- Email cannot contain consecutive dots\n';
                    if (!emailValidation.checks.noSpaces) errorMessage += '- Email cannot contain spaces\n';
                    if (!emailValidation.checks.validPattern) errorMessage += '- Invalid email format\n';

                    await Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email Address',
                        text: errorMessage,
                        confirmButtonColor: '#047857'
                    });
                    return;
                }

                // Check if email exists in database
                try {
                    const response = await fetch('./endpoint/check-email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `email=${encodeURIComponent(email)}`
                    });
                    const data = await response.json();

                    if (data.exists) {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Email Already Exists',
                            text: 'Please use a different email address',
                            confirmButtonColor: '#047857'
                        });
                        return;
                    }
                } catch (error) {
                    console.error('Error checking email:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not validate email address. Please try again.',
                        confirmButtonColor: '#047857'
                    });
                    return;
                }

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
                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF();

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerButton = document.getElementById('registerButton');
            const formInputs = document.querySelectorAll('#registrationForm input[required]');
            const termsCheckbox = document.getElementById('termsCheckbox');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');

            // Disable button initially
            registerButton.disabled = true;

            function validateForm() {
                // Get all required inputs
                const requiredInputs = document.querySelectorAll('input[required]');
                let isValid = true;

                // Check all required fields
                requiredInputs.forEach(input => {
                    if (input.id === 'firstName' || input.id === 'lastName') {
                        if (!input.value.trim()) {
                            isValid = false;
                        }
                    } else if (!input.value.trim()) {
                        isValid = false;
                    }
                });

                // Check password requirements
                const passwordValid = password.value.length >= 8 &&
                    /[A-Z]/.test(password.value) &&
                    /[a-z]/.test(password.value) &&
                    /[0-9]/.test(password.value) &&
                    /[^A-Za-z0-9]/.test(password.value);

                // Check passwords match
                const passwordsMatch = password.value === confirmPassword.value;

                // Check terms
                const termsAccepted = termsCheckbox.checked;

                // Enable button only if ALL conditions are met
                registerButton.disabled = !(isValid && passwordValid && passwordsMatch && termsAccepted);
            }

            // Add event listeners to all form elements
            formInputs.forEach(input => {
                input.addEventListener('input', validateForm);
            });

            termsCheckbox.addEventListener('change', validateForm);
            password.addEventListener('input', validateForm);
            confirmPassword.addEventListener('input', validateForm);

            // Replace existing submit handler
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                if (!validateForm()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Form Validation',
                        text: 'Please fill all required fields and accept the terms and conditions',
                        confirmButtonColor: '#047857'
                    });
                    return;
                }
                // If validation passes, proceed with QR generation
                generateQrCode();
            });

            // Initial validation
            validateForm();
        });
    </script>
    <script>
        // Email validation function
        function validateEmail(email) {
            // Basic email regex pattern
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

            // Additional checks
            const checks = {
                hasAtSymbol: email.includes('@'),
                hasValidLength: email.length >= 5 && email.length <= 254,
                noConsecutiveDots: !/\.{2,}/.test(email),
                noSpaces: !/\s/.test(email),
                validPattern: emailPattern.test(email),
                validLocalPart: email.split('@')[0]?.length <= 64,
                validDomainPart: email.split('@')[1]?.length <= 255
            };

            return {
                isValid: Object.values(checks).every(check => check === true),
                checks
            };
        }

        // Add this to your existing DOMContentLoaded event listener
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const registerButton = document.getElementById('registerButton');

            // Create error message element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-500 text-sm mt-1 hidden';
            emailInput.parentNode.appendChild(errorDiv);

            // Modify the generateQrCode function to check email validity first
            const originalGenerateQrCode = window.generateQrCode;
            window.generateQrCode = async function() {
                const email = emailInput.value.trim();
                const emailValidation = validateEmail(email);

                if (!emailValidation.isValid) {
                    // Build detailed error message
                    let errorMessage = 'Please fix the following email issues:\n';
                    if (!emailValidation.checks.hasAtSymbol) {
                        errorMessage += '- Email must contain @ symbol\n';
                    }
                    if (!emailValidation.checks.hasValidLength) {
                        errorMessage += '';
                    }
                    if (!emailValidation.checks.noConsecutiveDots) {
                        errorMessage += '- Email cannot contain consecutive dots\n';
                    }
                    if (!emailValidation.checks.noSpaces) {
                        errorMessage += '- Email cannot contain spaces\n';
                    }
                    if (!emailValidation.checks.validLocalPart) {
                        errorMessage += '';
                    }
                    if (!emailValidation.checks.validDomainPart) {
                        errorMessage += '';
                    }

                    await Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email Address',
                        text: errorMessage,
                        confirmButtonColor: '#047857'
                    });
                    return false;
                }

                // Proceed with original QR code generation if email is valid
                return originalGenerateQrCode.call(this);
            };

            emailInput.addEventListener('input', function() {
                const email = this.value.trim();

                if (email) {
                    const validation = validateEmail(email);

                    if (!validation.isValid) {
                        // Show specific error messages based on which checks failed
                        let errorMessage = 'Please enter a valid email address. ';
                        if (!validation.checks.hasAtSymbol) {
                            errorMessage += '';
                        }
                        if (!validation.checks.hasValidLength) {
                            errorMessage += '';
                        }
                        if (!validation.checks.noConsecutiveDots) {
                            errorMessage += '';
                        }
                        if (!validation.checks.noSpaces) {
                            errorMessage += 'Email cannot contain spaces. ';
                        }
                        if (!validation.checks.validLocalPart) {
                            errorMessage += ' ';
                        }
                        if (!validation.checks.validDomainPart) {
                            errorMessage += ' ';
                        }

                        // Show error styling
                        emailInput.style.borderColor = '#ef4444';
                        emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                        errorDiv.textContent = errorMessage;
                        errorDiv.classList.remove('hidden');
                        registerButton.disabled = true;

                    } else {
                        // If email format is valid, proceed with existing email check
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
                                    emailInput.style.borderColor = '#ef4444';
                                    emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                                    errorDiv.textContent = 'This email is already registered.';
                                    errorDiv.classList.remove('hidden');
                                    registerButton.disabled = true;
                                } else {
                                    emailInput.style.borderColor = '#10b981';
                                    emailInput.style.boxShadow = '';
                                    errorDiv.classList.add('hidden');
                                    validateForm(); // Re-run the main form validation
                                }
                            })
                    }
                } else {
                    // Reset styling for empty input
                    emailInput.style.borderColor = '';
                    emailInput.style.boxShadow = '';
                    errorDiv.classList.add('hidden');
                    validateForm();
                }
            });

            // Add blur event for additional validation
            emailInput.addEventListener('blur', function() {
                if (this.value.trim() && !validateEmail(this.value.trim()).isValid) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 1px #ef4444';
                }
            });
        });
    </script>
</body>

</html>