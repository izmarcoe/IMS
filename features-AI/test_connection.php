<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../conn/conn.php';
header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Test query
    $test_query = "SELECT 1";
    $stmt = $conn->query($test_query);
    
    if ($stmt === false) {
        throw new Exception('Query failed: ' . implode(' ', $conn->errorInfo()));
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful'
    ]);

} catch (Exception $e) {
    error_log('Connection Test Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}