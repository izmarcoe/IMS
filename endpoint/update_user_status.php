<?php
session_start();
include '../conn/conn.php';

// Check if request is AJAX and user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

if(isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['action'] === 'deactivate' ? 'deactivated' : 'active';
    
    try {
        $stmt = $conn->prepare("UPDATE login_db SET status = ? WHERE user_id = ?");
        $result = $stmt->execute([$status, $user_id]);
        
        if($result) {
            echo json_encode(['success' => true, 'status' => $status]);
        } else {
            echo json_encode(['error' => 'Update failed']);
        }
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}