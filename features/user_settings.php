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
     <header class="flex flex-row sticky">
        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <!-- User dropdown component -->
            <div class="relative"
                x-data="{ isOpen: false }"
                @keydown.escape.stop="isOpen = false"
                @click.away="isOpen = false">

                <button class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    @click="isOpen = !isOpen"
                    type="button"
                    id="user-menu-button"
                    :aria-expanded="isOpen"
                    aria-haspopup="true">
                    <img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-2">
                    <span><?php echo htmlspecialchars($fname); ?></span>
                    <svg class="w-4 h-4 ml-2 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right">

                    <ul class="bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg"
                                href="../features/user_settings.php"
                                role="menuitem">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                        </li>
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg"
                                href="../endpoint/logout.php"
                                role="menuitem">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <main class="flex min-h-screen bg-gray-50">
        <?php include '../features/sidebar.php' ?>
        <div class="flex-1 p-8 lg:p-12">
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">User Settings</h2>
                <?php echo $notification ? "<div class='mb-6 p-4 rounded-lg bg-blue-50 text-blue-600'>{$notification}</div>" : ''; ?>

                <form class="space-y-6" action="../features/user_settings.php" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                id="current_password"
                                name="current_password"
                                required>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                id="new_password"
                                name="new_password"
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                required>
                            <p class="mt-2 text-sm text-gray-500">Password must contain at least 8 characters, including uppercase, lowercase, and numbers</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                id="confirm_password"
                                name="confirm_password"
                                required>
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
</body>

</html>