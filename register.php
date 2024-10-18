<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    
    <!-- Registration Area -->
    <div class="registration-container">
            <div class="registration-form" id="registrationForm">
                <h2 class="text-center">Registration Form</h2>
                <p class="text-center">Fill in your personal details.</p>
                <form action="./endpoint/add-user.php" method="POST">
                    <div class="hide-registration-inputs">
                        <div class="form-group registration">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>
                        <div class="form-group registration row">
                            <div class="col-5">
                                <label for="contactNumber">Contact Number:</label>
                                <input type="number" class="form-control" id="contactNumber" name="contact_number" maxlength="11">
                            </div>
                            <div class="col-7">
                                <label for="email">Email:</label>
                                <input type="text" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="form-group registration row">
                            <div class="col-5">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-7">
                                <label for="ConfirmPassword">Confirm Password:</label>
                                <input type="password" class="form-control" id="ConfirmPassword" name="ConfirmPassword">
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

        <!-- Bootstrap Js -->   
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>