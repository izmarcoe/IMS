<?php
session_start();
include '../conn/conn.php';

// Check if the user is logged in and has the appropriate role to manage users
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/");
    exit();
}

// Fetch USERS excluding the current live session account
$UserStmt = $conn->prepare("SELECT user_id, Fname, Lname, role, status FROM login_db WHERE user_id != ? ORDER BY user_id");
$UserStmt->execute([$_SESSION['user_id']]);
$users = $UserStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle role update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    $updateStmt = $conn->prepare("UPDATE login_db SET role = ? WHERE user_id = ?");
    $updateStmt->execute([$new_role, $user_id]);
    header("Location: ../features/manage_users.php");
    exit();
}

// Handle user archive
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inactive_user'])) {
    $user_id = $_POST['user_id'];
    $archiveStmt = $conn->prepare("UPDATE login_db SET status = 'inactive' WHERE user_id = ?");
    $archiveStmt->execute([$user_id]);
    header("Location: ../features/manage_users.php");
    exit();
}

// Handle user activation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activate_user'])) {
    $user_id = $_POST['user_id'];
    $activateStmt = $conn->prepare("UPDATE login_db SET status = 'active' WHERE user_id = ?");
    $activateStmt->execute([$user_id]);
    header("Location: ../features/manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>

<body style="background-color: #DADBDF;">
     <!-- Header -->
     <header class="d-flex flex-row">
        <div class="d-flex justify-content text-center align-items-center text-white" style="background-color: #0F7505;">
            <div class="" style="width: 300px">
                <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
            </div>
        </div>


        <div class="d-flex align-items-center text-black p-3 flex-grow-1" style="background-color: gray;">
            <div class="d-flex justify-content-start flex-grow-1 text-white">
                <span class="px-4" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" style="width: 20px; height: 20px; margin-right: 5px;"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main>
        <div class="d-flex">
            <aside>
                <?php include '../features/sidebar.php'; ?>
            </aside>
            <div class="container mt-4 z-3">
                <h2 class="mb-4">Manage Users</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo $user['status'] == 'inactive' ? 'table-secondary' : ''; ?>">
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['Fname']); ?></td>
                                <td><?php echo htmlspecialchars($user['Lname']); ?></td>
                                <td>
                                    <?php
                                    switch ($user['role']) {
                                        case 'admin':
                                            echo 'Admin';
                                            break;
                                        case 'employee':
                                            echo 'Employee';
                                            break;
                                        case 'new_user':
                                            echo 'New User';
                                            break;
                                        default:
                                            echo 'New User';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['status']); ?></td>
                                <td>
                                    <?php if ($user['status'] == 'active'): ?>
                                        <!-- Edit Button to trigger modal -->
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $user['user_id']; ?>">
                                            Edit
                                        </button>

                                        <!-- Archive Button -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                            <button type="submit" name="inactive_user" class="btn btn-warning btn-sm">Archive</button>
                                        </form>
                                    <?php else: ?>
                                        <!-- Activate Button -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                            <button type="submit" name="activate_user" class="btn btn-success btn-sm">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($user['status'] == 'active'): ?>
                                <!-- Modal for Editing Role -->
                                <div class="modal fade" id="editRoleModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="editRoleModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true" data-bs-backdrop="false">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editRoleModalLabel<?php echo $user['user_id']; ?>">Edit Role for <?php echo htmlspecialchars($user['Fname']) . ' ' . htmlspecialchars($user['Lname']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                                    <div class="mb-3">
                                                        <label for="role" class="form-label">Role</label>
                                                        <select name="role" class="form-select">
                                                            <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                            <option value="employee" <?php if ($user['role'] == 'employee') echo 'selected'; ?>>Employee</option>
                                                            <option value="new_user" <?php if ($user['role'] == 'new_user') echo 'selected'; ?>>New User</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="update_role" class="btn btn-primary">Save changes</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../JS/time.js"></script>
</body>

</html>