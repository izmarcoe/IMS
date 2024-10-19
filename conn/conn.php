<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "IMS"; // Database name

try {
    // Correct DSN (Data Source Name) for MySQL: use 'mysql' instead of 'mysqli'
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
