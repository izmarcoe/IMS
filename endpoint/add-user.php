<?php 
include ('../conn/conn.php');

if (isset($_POST['fname'], $_POST['lname'], $_POST['contact_number'], $_POST['email'], $_POST['generated_code'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $contactNumber = $_POST['contact_number'];
    $email = $_POST['email'];
    $generatedCode = $_POST['generated_code'];

    try {
        // Check if a user with the same first name and last name already exists
        $stmt = $conn->prepare("SELECT `Fname`, `Lname` FROM `login_db` WHERE `Fname` = :fname AND `Lname` = :lname");
        $stmt->execute(['fname' => $fname, 'lname' => $lname]);

        $nameExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($nameExist)) {
            $conn->beginTransaction();  // Start a transaction to ensure atomicity

            // Insert new user record
            $insertStmt = $conn->prepare("INSERT INTO `login_db` (`Fname`, `Lname`, `contact_number`, `email`, `generated_code`) 
                                          VALUES (:fname, :lname, :contact_number, :email, :generated_code)");
            $insertStmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $insertStmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $insertStmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
            $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindParam(':generated_code', $generatedCode, PDO::PARAM_STR);

            $insertStmt->execute();

            $conn->commit();  // Commit the transaction

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
        $conn->rollBack();  // Rollback the transaction if an error occurs
        echo "Error: " . $e->getMessage();
    }
}

?>
