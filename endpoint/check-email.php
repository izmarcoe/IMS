<?php
header('Content-Type: application/json');
include('../conn/conn.php');

if(isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM login_db WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['exists' => $count > 0]);
    } catch(PDOException $e) {
        echo json_encode(['error' => true]);
    }
}
?>