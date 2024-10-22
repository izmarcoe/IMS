<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login System with QR Code Scanner</title>
    <link rel="stylesheet" href="CSS/home.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    
    <div class="main">

        <!-- Login Area -->

        <div class="login-container">

                <div class="login-form" id="loginForm">
                <h2 class="text-center">Welcome Back!</h2>
                <p class="text-center">Login through QR code scanner.</p>

                <video id="interactive" class="viewport" width="415"></div>
                
                <div class="qr-detected-container" style="display: none;">
                    <form action="./endpoint/login.php" method="POST">
                        <h4 class="text-center">QR Code Detected!</h4>
                        <input type="hidden" id="detected-qr-code" name="qr-code">
                        <button type="submit" class="btn btn-dark form-control">Login</button>
                    </form>
                </div>
                <p class="mt-3">No Account? Register <span class="switch-form-link" onclick="showRegistrationForm()">Here.</span></p>
            </div>
        </div>



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
                                <input type="number" class="form-control" id="contactNumber" name="contact_number" maxlength="11" required>
                            </div>
                            <div class="col-7">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
<!-- instascan Js -->
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

<script>
    const loginCon = document.querySelector('.login-container');
    const registrationCon = document.querySelector('.registration-container');
    const registrationForm = document.querySelector('.registration-form');
    const qrCodeContainer = document.querySelector('.qr-code-container');
    let scanner;

    registrationCon.style.display = "none";
    qrCodeContainer.style.display = "none";

    function showRegistrationForm() {
        registrationCon.style.display = "";
        loginCon.style.display = "none";
        scanner.stop();
    }

    function startScanner() {
        scanner = new Instascan.Scanner({ video: document.getElementById('interactive') });

        scanner.addListener('scan', function (content) {
            $("#detected-qr-code").val(content);
            scanner.stop();
            document.querySelector(".qr-detected-container").style.display = '';
            document.querySelector(".viewport").style.display = 'none';
        });

        Instascan.Camera.getCameras()
            .then(function (cameras) {
                if (cameras.length > 0) {
                    scanner.start(cameras[0]);
                } else {
                    console.error('No cameras found.');
                    alert('No cameras found.');
                }
            })
            .catch(function (err) {
                console.error('Camera access error:', err);
                alert('Camera access error: ' + err);
            });
    }

    function generateRandomCode(length) {
        const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        let randomString = '';

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            randomString += characters.charAt(randomIndex);
        }

        return randomString;
    }

    function generateQrCode() {
        const registrationInputs = document.querySelector('.hide-registration-inputs');
        const h2 = document.querySelector('.registration-form > h2');
        const p = document.querySelector('.registration-form > p');
        const inputs = document.querySelectorAll('.registration input');
        const qrImg = document.getElementById('qrImg');
        const qrBox = document.getElementById('qrBox');

        registrationInputs.style.display = 'none';

        let text = generateRandomCode(10);
        $("#generatedCode").val(text);

        if (text === "") {
            alert("Please enter text to generate a QR code.");
            return;
        } else {
            const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(text)}`;

            // Generating image
            qrImg.src = apiUrl;
            qrBox.setAttribute("id", "qrBoxGenerated");
            qrCodeContainer.style.display = "";
            registrationCon.style.display = "";
            h2.style.display = "none";
            p.style.display = "none";
        }
    }

    // Ensure the scanner starts after the page loads
    document.addEventListener('DOMContentLoaded', startScanner);
</script>


</body>
</html>
