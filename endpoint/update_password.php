<?php
session_start();
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        if (!isset($_SESSION['reset_email'])) {
            throw new Exception('Invalid session');
        }

        $email = $_SESSION['reset_email'];
        $new_password = $_POST['new_password'];
        $generated_code = $_POST['generated_code'];
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update both password and QR code
        $stmt = $conn->prepare("UPDATE login_db SET password = :password, generated_code = :generated_code WHERE email = :email");
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':generated_code', $generated_code);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            // Clear session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            
            $response['success'] = true;
            $response['message'] = 'Password and QR code updated successfully';
        } else {
            throw new Exception('Failed to update password');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>