<?php
session_start(); // Start the session
include('../conn/conn.php');

// User ID from session
$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['Fname']) || !isset($_SESSION['Lname'])) {
    // Check if the connection variable is set
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT Fname, Lname, role FROM login_db WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Use bindParam for PDO
        $stmt->execute();

        // Fetch the user data
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Set Fname and Lname in the session
            $_SESSION['Fname'] = $user['Fname'];
            $_SESSION['Lname'] = $user['Lname'];
            $_SESSION['user_role'] = $user['role'];
        } else {
            // Handle case where user data is not found (optional)
            echo "User data not found.";
            exit();
        }

        // Close the statement
        $stmt = null;
    } else {
        die("Database connection not established.");
    }
}

$fname = $_SESSION['Fname'];
$notification = ""; // Variable to store notification messages

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate and update the password
    if ($new_password === $confirm_password) {
        // Assume $user_id is the ID of the logged-in user
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $query = "SELECT password FROM login_db WHERE user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($current_password, $row['password'])) {
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE login_db SET password = :new_password WHERE user_id = :user_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':new_password', $new_password_hashed, PDO::PARAM_STR);
                $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $update_stmt->execute();
                $notification = '<div class="alert alert-success" role="alert">Password updated successfully.</div>';
            } else {
                $notification = '<div class="alert alert-danger" role="alert">Current password is incorrect.</div>';
            }
        } else {
            $notification = '<div class="alert alert-danger" role="alert">User is not logged in.</div>';
        }
    } else {
        $notification = '<div class="alert alert-danger" role="alert">New password and confirm password do not match.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link rel="stylesheet" href="../src/output.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>
    <!-- Header -->
    <?php include '../features/header.php' ?>
    <main class="flex min-h-screen bg-gray-50">
        <?php include '../features/sidebar.php' ?>
        <div class="flex-1 p-8 lg:p-12">
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">User Settings</h2>
                <?php echo $notification ? "<div class='mb-6 p-4 rounded-lg bg-blue-50 text-blue-600'>{$notification}</div>" : ''; ?>

                <form method="POST" class="space-y-4">
                    <!-- Current Password -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">
                            Current Password
                        </label>
                        <div class="relative">
                            <input type="password"
                                id="current_password"
                                name="current_password"
                                class="w-full px-4 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                required>
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="toggle_current_password"></i>
                            </span>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">
                            New Password
                        </label>
                        <div class="relative">
                            <input type="password"
                                id="new_password"
                                name="new_password"
                                class="w-full px-4 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                required>
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="toggle_new_password"></i>
                            </span>
                        </div>
                    </div>
                    <!-- Password Requirements Section -->
                    <div class="mt-2 text-sm text-gray-500 space-y-1" id="password-requirements">
                        <p class="font-medium mb-1">Password must contain:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 requirement-icon" data-req="length"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 requirement-icon" data-req="uppercase"></i>
                                <span>1 uppercase letter</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 requirement-icon" data-req="lowercase"></i>
                                <span>1 lowercase letter</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 requirement-icon" data-req="number"></i>
                                <span>1 number</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 requirement-icon" data-req="special"></i>
                                <span>1 special character</span>
                            </div>
                        </div>
                    </div>
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <input type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="w-full px-4 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                required>
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                                <i class="fas fa-eye text-gray-500 hover:text-gray-700" id="toggle_confirm_password"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="mt-12 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>
    <script>
        // Toggle password visibility for all password fields
        ['current_password', 'new_password', 'confirm_password'].forEach(id => {
            const toggleBtn = document.getElementById(`toggle_${id}`);
            const input = document.getElementById(id);

            toggleBtn.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        // ...existing password validation code...
        document.getElementById('new_password').addEventListener('input', function(e) {
            const password = e.target.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*]/.test(password)
            };

            Object.keys(requirements).forEach(req => {
                const icon = document.querySelector(`.requirement-icon[data-req="${req}"]`);
                if (requirements[req]) {
                    icon.classList.remove('text-gray-300');
                    icon.classList.add('text-green-500');
                } else {
                    icon.classList.remove('text-green-500');
                    icon.classList.add('text-gray-300');
                }
            });
        });
    </script>
</body>

</html>