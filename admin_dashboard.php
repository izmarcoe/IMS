<?php
session_start();
include ('./conn/conn.php');

// Check if the user is logged in and has the 'admin' role
if (isset($_SESSION['admin_id']) && $_SESSION['user_role'] == 'admin') { // Change 'user_id' to 'admin_id'
    $admin_id = $_SESSION['admin_id'];
    $user_name = $_SESSION['admin_name'];
    $user_role = $_SESSION['user_role']; // This will be 'admin'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="CSS/dashboard.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Welcome to the Admin Dashboard</h1>
        <p>Hello, <?php echo htmlspecialchars($user_name); ?>!</p>
        <p>Your role: <?php echo htmlspecialchars($user_role); ?></p>

        <!-- User Management Section -->
        <h2>User Management</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all users from the database
                $stmt = $conn->prepare("SELECT `user_id`, `Fname`, `Lname`, `user_role` FROM `login_db`");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Display each user in a table row
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['Fname']) . " " . htmlspecialchars($user['Lname']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['user_role']) . "</td>";
                    echo "<td>
                            <form action='./endpoint/update-role.php' method='POST'>
                                <input type='hidden' name='user_id' value='" . htmlspecialchars($user['user_id']) . "'>
                                <select name='new_role' class='form-control'>
                                    <option value='none' ".($user['user_role'] == 'none' ? 'selected' : '').">None</option>
                                    <option value='employee' ".($user['user_role'] == 'employee' ? 'selected' : '').">Employee</option>
                                    <option value='admin' ".($user['user_role'] == 'admin' ? 'selected' : '').">Admin</option>
                                </select>
                                <button type='submit' class='btn btn-primary mt-2'>Update Role</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Logout Button -->
        <a class="btn btn-dark" href="../endpoint/logout.php">Logout</a>
    </div>
</body>
</html>

<?php
} else {
    // Redirect to login page if not logged in or wrong role
    header("Location: http://localhost/IMS/");
    exit();
}
?>
