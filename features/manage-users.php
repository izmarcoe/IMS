<?php
session_start();
include '../conn/conn.php';

// Check if the user is logged in and has the appropriate role to manage users
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/");
    exit();
}

// Fetch USERS
$UserStmt = $conn->prepare("SELECT user_id, Fname, Lname, role FROM login_db WHERE status = 'active' ORDER BY user_id");
$UserStmt->execute();
$users = $UserStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $updateStmt = $conn->prepare("UPDATE login_db SET role = ? WHERE user_id = ?");
    $updateStmt->execute([$new_role, $user_id]);
    header("Location: ../features/manage-users.php");
    exit();
}

// Handle user archive
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_user'])) {
    $user_id = $_POST['user_id'];
    $archiveStmt = $conn->prepare("UPDATE login_db SET status = 'archived' WHERE user_id = ?");
    $archiveStmt->execute([$user_id]);
    header("Location: ../features/manage-users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>

<body>
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>
    <main>
        <div class="d-flex">
            <?php include '../features/sidebar.php'; ?>

            <div class="container mt-4 z-3">
                <h2>Manage Users</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['Fname']); ?></td>
                                <td><?php echo htmlspecialchars($user['Lname']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                        <select name="role" class="form-select d-inline w-auto">
                                            <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                            <option value="user" <?php if ($user['role'] == 'employee') echo 'selected'; ?>>Employee</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-primary btn-sm">Edit</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                        <button type="submit" name="archive_user" class="btn btn-warning btn-sm">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../JS/time.js"></script>
</body>

</html>