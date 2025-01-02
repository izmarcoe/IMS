<?php
session_start();
include('../conn/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['otp_verified'])) {
    $new_password = $_POST['new_password'];
    $email = $_SESSION['reset_email'];
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE login_db SET password = ?, reset_otp = NULL, reset_otp_expiry = NULL WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        
        unset($_SESSION['otp_verified']);
        unset($_SESSION['reset_email']);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>