<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'employee') {
        header("Location: dashboards/employee_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] == 'admin') {
        header("Location: dashboards/admin_dashboard.php");
        exit();
    } else {
        header("Location: home.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link rel="stylesheet" href="CSS/index.css">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            border-radius: 50px;
            background: var(--shade-bg, linear-gradient(323deg, #0F7505 25.5%, #0C2809 79.98%));
            background-size: cover;
            min-height: 100vh;
            margin: 0;
        }

        .row {
            background: transparent;
        }

        .btn:hover {
            /*button hover effect*/
            background-color: #8BED14;
            color: white;
            border: 2px solid #0F7505;
            transform: scale(1.1);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="main">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm border-style">
                        <div class="card-body p-4">
                            <div class="login-container">
                                <div class="login-form" id="loginForm">
                                    <h2 class="text-center fs-1 font-login">LOGIN</h2>
                                    <img src="icons/zefmaven.png" class="centered-image mx-auto d-block">

                                    <div class="centered-video">
                                        <video id="interactive" class="viewport" width="415"></video>
                                    </div>

                                    <div class="qr-detected-container" style="display: none;">
                                        <form action="endpoint/login.php" method="POST">
                                            <h4 class="text-center">QR Code Detected!</h4>
                                            <input type="hidden" id="detected-qr-code" name="qr-code">
                                            <button type="submit" class="btn btn-dark my-3" style="border-radius: 10px; background: var(--login-button-shade, linear-gradient(90deg, #0F7505 0%, #8BED14 100%));">Login</button>
                                        </form>
                                    </div>

                                    <div class="password-login-container mt-4" id="passwordLoginForm" style="display: none;">
                                        <form action="./endpoint/login.php" method="POST" class="text-center">
                                            <div class="form-group row justify-content-center">
                                                <div class="col-6 text-start py-2">
                                                    <label for="email">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email" style="border-radius: 10px;" required>
                                                </div>
                                            </div>
                                            <div class="form-group row justify-content-center">
                                                <div class="col-6 text-start py-2">
                                                    <label for="password">Password</label>
                                                    <input type="password" class="form-control" id="password" name="password" style="border-radius: 10px;" required>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-dark my-3" style="border-radius: 25px; background: var(--login-button-shade, linear-gradient(90deg, #0F7505 0%, #8BED14 100%));">Login</button>
                                        </form>
                                    </div>
                                    <div class="text-center smallerfont-text">
                                        <div> or Login using</div>
                                        <div class="d-flex text-center justify-content-center">
                                            <div class="text-center smallerfont-text">
                                                <span class="switch-form-link" id="qrCodeLoginLink" onclick="togglePasswordLogin(false);" style="text-decoration: underline; display:none; color: blue;">
                                                    QR code
                                                </span>
                                            </div>
                                            <div class="text-center" id="passwordLoginLink">
                                                <span class="switch-form-link" onclick="togglePasswordLogin(true);" style="text-decoration: underline; color: blue;">
                                                    Password
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 smallerfont-text">No Account? Register <span class="switch-form-link" onclick="showRegistrationForm()">Here.</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="JS/togglePasswordLogin.js"> </script>

    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="registration-container">
                        <div class="registration-form" id="registrationForm">
                            <h2 class="text-center">Registration Form</h2>
                            <p class="text-center">Fill in your personal details.</p>
                            <form action="./endpoint/add-user.php" method="POST" id="registrationForm">
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
                                            <input type="number" class="form-control" id="contactNumber" name="contact_number" maxlength="11" required>
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
                                    <p>Already have a QR code account? Login <span class="switch-form-link" onclick="location.href='index.php'">Here.</span></p>
                                    <button type="button" class="btn btn-dark login-register form-control" id="registerButton" onclick="generateQrCode()" disabled>Register and Generate QR Code</button>
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
            </div>
        </div>
    </div>

    <script src="bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <script src="./JS/QR.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const registerButton = document.getElementById('registerButton');
            const formInputs = document.querySelectorAll('#registrationForm input[required]');

            formInputs.forEach(input => {
                input.addEventListener('input', () => {
                    let allFilled = true;
                    formInputs.forEach(input => {
                        if (!input.value) {
                            allFilled = false;
                        }
                    });
                    registerButton.disabled = !allFilled;
                });
            });
        });
    </script>
</body>

</html>