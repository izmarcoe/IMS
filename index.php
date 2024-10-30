<?php
// Modified index.php - Add this at the very top
session_start();

// Add these headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'employee') {
        header("Location: dashboards/employee_dashboard.php");
        exit();
    } else {
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
    <title>Login System with QR Code Scanner</title>
    <link rel="stylesheet" href="CSS/home.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
    
    <div class="main">

        <!-- Login Area -->
        <div class="login-container">
            <div class="login-form" id="loginForm">
                <h2 class="text-center">Welcome Back!</h2>
                <!-- Link to toggle password login -->
                <div class="mt-3 text-center">
                    <span class="switch-form-link" onclick="togglePasswordLogin()">Login through QR code</span>
                </div>

                <!-- QR Code Scanner Section -->
                <video id="interactive" class="viewport" width="415"></video>

                <div class="qr-detected-container" style="display: none;">
                    <form action="./endpoint/login.php" method="POST">
                        <h4 class="text-center">QR Code Detected!</h4>
                        <input type="hidden" id="detected-qr-code" name="qr-code">
                        <button type="submit" class="btn btn-dark form-control">Login</button>
                    </form>
                </div>

                <!-- Link to toggle password login -->
                <div class="mt-3 text-center">
                    <span class="switch-form-link" onclick="togglePasswordLogin()">Login using Password</span>
                </div>

                <!-- New Password Login Section (initially hidden) -->
                <div class="password-login-container mt-4" id="passwordLoginForm" style="display: none;">
                    <form action="./endpoint/login.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-dark form-control">Login</button>
                    </form>
                </div>

                <p class="mt-3">No Account? Register <span class="switch-form-link" onclick="showRegistrationForm()">Here.</span></p>
            </div>
        </div>
        <script src="JS/togglePasswordLogin.js"> </script>


        <!-- Registration Area -->
        <div class="registration-container">
            <div class="registration-form" id="registrationForm">
                <h2 class="text-center">Registration Form</h2>
                <p class="text-center">Fill in your personal details.</p>
                <form action="./endpoint/add-user.php" method="POST">
                    <div class="hide-registration-inputs">
                        <div class="form-group registration row">
                            <div class="col-6">
                                <label for="fname">First Name:</label>
                                <input type="text" class="form-control" id="fname" name="fname" required>
                            </div>
                            <div class="col-6">
                                <label for="lname">Last Name:</label>
                                <input type="text" class="form-control" id="lname" name="lname" required>
                            </div>
                        </div>
                        <div class="form-group registration row">
                            <div class="col-5">
                                <label for="contactNumber">Contact Number:</label>
                                <input type="number" class="form-control" id="contactNumber" name="contact_number" maxlength="15" required>
                            </div>
                            <div class="col-7">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group registration row">
                            <div class="col-6">
                                <label for="password">Password:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-6">
                                <label for="confirmPassword">Confirm Password:</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            </div>
                        </div>
                        <p>Already have a QR code account? Login <span class="switch-form-link" onclick="location.reload()">Here.</span></p>
                        <button type="button" class="btn btn-dark login-register form-control" onclick="generateQrCode()">Register and Generate QR Code</button>
                    </div>

                    <div class="qr-code-container text-center" style="display: none;">
                        <h3>Take a Picture of your QR Code and Login!</h3>
                        <input type="hidden" id="generatedCode" name="generated_code">
                        <div class="m-4" id="qrBox">
                            <img src="" id="qrImg">
                        </div>
                        <button type="submit" class="btn btn-dark">Back to Login Form.</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

<!-- Bootstrap Js -->   
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<!-- instascan Js -->
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

<script src="./JS/QR.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>

</body>
</html>
