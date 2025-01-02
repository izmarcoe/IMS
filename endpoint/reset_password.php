<?php
session_start();
include('../conn/conn.php');
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT email FROM login_db WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Store OTP in database
            $stmt = $conn->prepare("UPDATE login_db SET reset_otp = ?, reset_otp_expiry = ? WHERE email = ?");
            $stmt->execute([$otp, $expiry, $email]);
            
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'marcobarraquio091504@gmail.com'; //  Gmail address
            $mail->Password = 'iftu cfpk swld mrwi'; // Replace with App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            // Recipients
            $mail->setFrom('marcobarraquio091504@gmail.com', 'ZEF Maven');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body = "Your OTP for password reset is: <b>{$otp}</b><br>This code will expire in 15 minutes.";
            
            $mail->send();
            $_SESSION['reset_email'] = $email;
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email not found in our records']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Mail Error: {$mail->ErrorInfo}"]);
    }
}
?>