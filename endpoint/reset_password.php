<?php
session_start();
require('../conn/conn.php');
require('../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $source = $_POST['source'] ?? '';
    
    try {
        // Different SQL query based on source
        if ($source === 'admin') {
            $stmt = $conn->prepare("SELECT email FROM login_db WHERE email = ? AND role = 'admin' AND status = 'active'");
        } else {
            $stmt = $conn->prepare("SELECT email FROM login_db WHERE email = ? AND (role = 'new_user' OR role = 'employee') AND status = 'active'");
        }
        
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Store OTP in database
            $stmt = $conn->prepare("UPDATE login_db SET reset_otp = ?, reset_otp_expiry = ? WHERE email = ?");
            $stmt->execute([$otp, $expiry, $email]);
            
            // Store email in session for OTP verification
            $_SESSION['reset_email'] = $email;
            
            // Send email with OTP
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'zefmaven@gmail.com';
            $mail->Password = 'pwhx lrbm nfmm rcvd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            // Recipients
            $mail->setFrom('zefmaven@gmail.com', 'Zefmaven computer parts and accessories');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body = "Your OTP for password reset is: <b>{$otp}</b><br>This code will expire in 15 minutes.";
            
            $mail->send();
            
            $response['success'] = true;
            $response['message'] = 'OTP sent successfully';
        } else {
            $response['message'] = 'Email not found or account inactive';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>