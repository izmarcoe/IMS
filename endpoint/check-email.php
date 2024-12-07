<!--for form validation-->
<?php
header('Content-Type: application/json');

// Database connection
require_once '../conn/conn.php';

$email = $_POST['email'] ?? '';
$exists = false;

if ($email) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM login_db WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $exists = $count > 0;
    } catch (PDOException $e) {
        // Log error and return false to be safe
        error_log("Email check error: " . $e->getMessage());
        $exists = false;
    }
}

echo json_encode(['exists' => $exists]);