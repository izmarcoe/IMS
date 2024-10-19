<?php 
include ('../conn/conn.php');

if (isset($_POST['name'], $_POST['contact_number'], $_POST['email'], $_POST['generated_code'])) {
    $name = $_POST['name'];
    $contactNumber = $_POST['contact_number'];
    $email = $_POST['email'];
    $generatedCode = $_POST['generated_code'];
    
    try {
        $stmt = $conn->prepare("SELECT `name` FROM `login_db` WHERE `name` = :name");
        $stmt->execute(['name' => $name]);

        $nameExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($nameExist)) {
            $conn->beginTransaction();  // Start transaction

            $insertStmt = $conn->prepare("INSERT INTO `login_db` (`name`, `contact_number`, `email`, `generated_code`) 
                                          VALUES (:name, :contact_number, :email, :generated_code)");
            $insertStmt->bindParam('name', $name, PDO::PARAM_STR);
            $insertStmt->bindParam('contact_number', $contactNumber, PDO::PARAM_STR);
            $insertStmt->bindParam('email', $email, PDO::PARAM_STR);
            $insertStmt->bindParam('generated_code', $generatedCode, PDO::PARAM_STR);

            $insertStmt->execute();

            $conn->commit();  // Commit the transaction

            echo "
            <script>
                alert('Registered Successfully!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        } else {
            echo "
            <script>
                alert('Account Already Exist!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    } catch (PDOException $e) {
        // Check if a transaction is active before rolling it back
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "Error: " . $e->getMessage();
    }
}
?>
