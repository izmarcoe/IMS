<!--THIS IS FOR LOGIN.PHP-->
<!DOCTYPE html>
<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
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
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Passwords do not match!',
                confirmButtonColor: '#047857'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'http://localhost/IMS/register.php';
                }
            });
        </script>
        ";
            exit();
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if a user with the same first name and last name already exists
            $stmt = $conn->prepare("SELECT `email` FROM `login_db` WHERE `email` = :email");
            $stmt->execute(['email' => $email]);

            $emailExists = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($emailExists)) {
                $conn->beginTransaction();

                // Insert new user record with hashed password and default role set to 'none'
                $insertStmt = $conn->prepare("INSERT INTO `login_db` (`Fname`, `Lname`, `contact_number`, `email`, `generated_code`, `password`, `role`, `status`) 
           VALUES (:fname, :lname, :contact_number, :email, :generated_code, :password, 'new_user', 'active')");
                $insertStmt->bindParam(':fname', $fname, PDO::PARAM_STR);
                $insertStmt->bindParam(':lname', $lname, PDO::PARAM_STR);
                $insertStmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
                $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $insertStmt->bindParam(':generated_code', $generatedCode, PDO::PARAM_STR);
                $insertStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

                if ($insertStmt->execute()) {
                    $conn->commit();
                    echo "
                    <script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Registration Successful!',
                            text: 'You can now login with your credentials.',
                            confirmButtonColor: '#047857'
                        }).then((result) => {
                            window.location.href = '../user_login.php';
                        });
                    </script>
                    ";
                    exit();
                }
            } else {
                // User with the same name already exists
                echo "
            <script>
                alert('User with the same name already exists!');
                window.location.href = 'http://localhost/IMS/register.php';
            </script>
            ";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            echo "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: 'An error occurred. Please try again.',
                    confirmButtonColor: '#047857'
                }).then((result) => {
                    window.location.href = 'http://localhost/IMS/register.php';
                });
            </script>";
            error_log($e->getMessage());
        }
    }
    ?>
</body>
</html>