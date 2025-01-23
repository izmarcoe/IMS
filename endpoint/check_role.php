<?php
session_start(); // Add this at the very top
include('../conn/conn.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT role, status FROM login_db WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $currentRole = $result['role'];
        $currentStatus = $result['status'];
        $storedRole = $_SESSION['user_role'];
        
        // Debug logging
        error_log("Current Role: " . $currentRole);
        error_log("Stored Role: " . $storedRole);
        error_log("Status: " . $currentStatus);
        
        echo json_encode([
            'success' => true,
            'current_role' => $currentRole,
            'stored_role' => $storedRole,
            'status' => $currentStatus,
            'needs_update' => ($currentRole !== $storedRole || $currentStatus === 'deactivated')
        ]);
    } else {
        error_log("User not found in database: " . $_SESSION['user_id']);
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}