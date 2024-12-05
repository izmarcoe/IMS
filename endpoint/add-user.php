<!--THIS IS FOR LOGIN.PHP-->

<?php
include('../conn/conn.php');

if (isset($_POST['fname'], $_POST['lname'], $_POST['contact_number'], $_POST['email'], $_POST['generated_code'], $_POST['password'], $_POST['confirm_password'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $contactNumber = $_POST['contact_number'];
    $email = $_POST['email'];
    $generatedCode = $_POST['generated_code'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match!',
                confirmButtonColor: '#047857'
            }).then(function() {
                window.location.href = '../register.php';
            });
        </script>";
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if a user with the same first name and last name already exists
        $stmt = $conn->prepare("SELECT `Fname`, `Lname` FROM `login_db` WHERE `Fname` = :fname AND `Lname` = :lname");
        $stmt->execute(['fname' => $fname, 'lname' => $lname]);

        $nameExist = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($nameExist)) {
            $conn->beginTransaction();  // Start a transaction to ensure atomicity

            // Insert new user record with hashed password and default role set to 'new_user' and status set to 'deactivated'
            $insertStmt = $conn->prepare("INSERT INTO `login_db` (`Fname`, `Lname`, `contact_number`, `email`, `generated_code`, `password`, `role`, `status`) 
           VALUES (:fname, :lname, :contact_number, :email, :generated_code, :password, 'new_user', 'deactivated')");
            $insertStmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $insertStmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $insertStmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
            $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindParam(':generated_code', $generatedCode, PDO::PARAM_STR);
            $insertStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

            $insertStmt->execute();

            $conn->commit();  // Commit the transaction

            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Registered Successfully!',
                    text: 'Your account is pending activation. Please contact administrator.',
                    confirmButtonColor: '#047857'
                }).then(function() {
                    window.location.href = '../user_login.php';
                });
            </script>";
        } else {
            // User with the same name already exists
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: 'User with the same name already exists!',
                    confirmButtonColor: '#047857'
                }).then(function() {
                    window.location.href = '../register.php';
                });
            </script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();  // Rollback the transaction if an error occurs
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: 'An error occurred. Please try again.',
                confirmButtonColor: '#047857'
            }).then(function() {
                window.location.href = '../register.php';
            });
        </script>";
        error_log($e->getMessage());
    }
}
?>