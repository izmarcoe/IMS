<?php 
include ('../conn/conn.php');

if (isset($_POST['fname'], $_POST['lname'], $_POST['contact_number'], $_POST['email'], $_POST['generated_code'], $_POST['role'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $contactNumber = $_POST['contact_number'];
    $email = $_POST['email'];
    $generatedCode = $_POST['generated_code'];
    $role = $_POST['role'];

    try {
        // Check if a user with the same first name and last name already exists
        $stmt = $conn->prepare("SELECT `Fname`, `Lname` FROM `login_db` WHERE `Fname` = :fname AND `Lname` = :lname");
        $stmt->execute(['fname' => $fname, 'lname' => $lname]);

        $nameExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($nameExist)) {
            $conn->beginTransaction();

            // Hash the generated QR code
            $hashedCode = hash('sha256', $generatedCode);

            // Insert new user record with role
            $insertStmt = $conn->prepare("INSERT INTO `login_db` (`Fname`, `Lname`, `contact_number`, `email`, `generated_code`, `role`) 
                                          VALUES (:fname, :lname, :contact_number, :email, :generated_code, :role)");
            $insertStmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $insertStmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $insertStmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
            $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindParam(':generated_code', $hashedCode, PDO::PARAM_STR);
            $insertStmt->bindParam(':role', $role, PDO::PARAM_STR);

            $insertStmt->execute();

            $conn->commit();

            echo "
            <script>
                alert('Registered Successfully!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        } else {
            // User with the same name already exists
            echo "
            <script>
                alert('User with the same name already exists!');
                window.location.href = 'http://localhost/IMS/';
            </script>
            ";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>