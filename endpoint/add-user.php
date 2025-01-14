<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include('../conn/conn.php');
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
if (isset($_POST['first_name'], $_POST['last_name'], $_POST['contact_number'], $_POST['email'], $_POST['generated_code'], $_POST['password'], $_POST['confirm_password'])) {
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $contactNumber = $_POST['contact_number'];
    $email = $_POST['email'];
    $generatedCode = $_POST['generated_code'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Passwords do not match!',
                confirmButtonColor: '#047857'
            }).then((result) => {
                window.location.href = '../register.php';
            });
        </script>";
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM login_db WHERE email = ?");
        $stmt->execute([$email]);
        
        if($stmt->fetchColumn() > 0) {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Email Already Exists',
                    text: 'Please use a different email address',
                    confirmButtonColor: '#047857'
                }).then(() => {
                    window.location.href = '../register.php';
                });
            </script>";
            exit();
        }

        $conn->beginTransaction();

        $insertStmt = $conn->prepare("INSERT INTO login_db (Fname, Lname, contact_number, email, generated_code, password, role, status) 
        VALUES (:fname, :lname, :contact_number, :email, :generated_code, :password, 'new_user', 'active')");
        
        $insertStmt->bindParam(':fname', $fname, PDO::PARAM_STR);
        $insertStmt->bindParam(':lname', $lname, PDO::PARAM_STR);
        $insertStmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
        $insertStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $insertStmt->bindParam(':generated_code', $generatedCode, PDO::PARAM_STR);
        $insertStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

        if ($insertStmt->execute()) {
            $conn->commit();
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    text: 'You can now login with your credentials.',
                    confirmButtonColor: '#047857'
                }).then((result) => {
                    window.location.href = '../user_login.php';
                });
            </script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>
            console.error('", addslashes($e->getMessage()), "');
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: 'An error occurred. Please try again.',
                confirmButtonColor: '#047857'
            }).then((result) => {
                window.location.href = '../register.php';
            });
        </script>";
    }
} else {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Invalid Request',
            text: 'Please fill all required fields.',
            confirmButtonColor: '#047857'
        }).then((result) => {
            window.location.href = '../register.php';
        });
    </script>";
}
?>
</body>
</html>