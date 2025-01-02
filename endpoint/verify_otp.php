<?php
session_start();
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']); // Remove any whitespace
    $email = $_SESSION['reset_email'];
    
    try {
        // First, log the values for debugging
        error_log("Verifying OTP - Email: $email, OTP: $otp");
        
        // Get current server time
        $currentTime = date('Y-m-d H:i:s');
        
        // Fetch OTP details
        $stmt = $conn->prepare("SELECT reset_otp, reset_otp_expiry FROM login_db WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            error_log("Database values - Stored OTP: {$result['reset_otp']}, Expiry: {$result['reset_otp_expiry']}, Current Time: $currentTime");
            
            // Compare OTP and check expiry
            if ($result['reset_otp'] === $otp && strtotime($result['reset_otp_expiry']) > strtotime($currentTime)) {
                $_SESSION['otp_verified'] = true;
                echo json_encode(['success' => true]);
            } else {
                if ($result['reset_otp'] !== $otp) {
                    error_log("OTP mismatch - Input: $otp, Stored: {$result['reset_otp']}");
                    echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
                } else {
                    error_log("OTP expired - Expiry: {$result['reset_otp_expiry']}, Current: $currentTime");
                    echo json_encode(['success' => false, 'message' => 'OTP has expired']);
                }
            }
        } else {
            error_log("No record found for email: $email");
            echo json_encode(['success' => false, 'message' => 'Invalid email or OTP']);
        }
    } catch (Exception $e) {
        error_log("Error in verify_otp.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error verifying OTP']);
    }
}
?>