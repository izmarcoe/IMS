<?php
session_start();
include '../conn/conn.php';

// Check if the user is logged in and has the appropriate role to manage users
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/");
    exit();
}

// 1. Modify SQL query with pagination and exclude admins
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total count excluding admins
$totalQuery = $conn->prepare("SELECT COUNT(*) FROM login_db WHERE role != 'admin'");
$totalQuery->execute();
$totalItems = $totalQuery->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Modified main query
$stmt = $conn->prepare("
    SELECT user_id, Fname, Lname, email, contact_number, role, status 
    FROM login_db 
    WHERE role != 'admin'
    ORDER BY user_id 
    LIMIT :offset, :limit
");
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $archiveStmt = $conn->prepare("UPDATE login_db SET status = 'deactivated' WHERE user_id = ?");
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

$fname = $_SESSION['Fname'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body style="background-color: #DADBDF;">
    <!-- Header -->
    <?php include '../features/header.php' ?>

    <main class="flex flex-row">
        <div class="flex">

            <aside>
                <?php include '../features/sidebar.php'; ?>
            </aside>

            <div class="container mx-auto mt-4 z-30 px-8 max-w-7xl">
                <h2 class="mt-6 text-xl md:text-2xl font-semibold mb-6">Manage Users</h2>
                <table class="w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[10%]">
                                User ID
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                First name
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                Last name
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[20%]">
                                Role
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[15%]">
                                Status
                            </th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-[25%]">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo $user['status'] == 'deactivated' ? 'bg-gray-400' : ''; ?>">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[10%]">
                                    <div class="text-sm md:text-base text-gray-900"><?php echo htmlspecialchars($user['user_id']); ?></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[15%]">
                                    <div class="text-sm md:text-base text-gray-900"><?php echo htmlspecialchars($user['Fname']); ?></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[15%]">
                                    <div class="text-sm md:text-base text-gray-900"><?php echo htmlspecialchars($user['Lname']); ?></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[20%]">
                                    <?php
                                    switch ($user['role']) {
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
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[15%]">
                                    <div class="text-sm md:text-base text-gray-900"><?php echo htmlspecialchars($user['status']); ?></div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap w-[25%]">
                                    <?php if ($user['status'] == 'active'): ?>
                                        <button type="button" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600" onclick="openModal('editRoleModal<?php echo $user['user_id']; ?>')">
                                            Edit Role
                                        </button>

                                        <button type="button"
                                            onclick="updateUserStatus(<?php echo $user['user_id']; ?>, 'deactivate')"
                                            class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600 ml-2">
                                            Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            onclick="updateUserStatus(<?php echo $user['user_id']; ?>, 'activate')"
                                            class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($user['status'] == 'active'): ?>
                                <div id="editRoleModal<?php echo $user['user_id']; ?>" class="hidden fixed inset-0 z-50">
                                    <!-- Modal Backdrop -->
                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                                    <!-- Modal Content -->
                                    <div class="fixed inset-0 z-10 overflow-y-auto">
                                        <div class="flex min-h-full items-center justify-center p-4 text-center">
                                            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                                                <!-- Modal Header -->
                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                    <div class="flex items-start justify-between">
                                                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                                                            Edit Role for <?php echo htmlspecialchars($user['Fname'] . ' ' . $user['Lname']); ?>
                                                        </h3>
                                                        <button onclick="closeModal('editRoleModal<?php echo $user['user_id']; ?>')"
                                                            class="ml-auto bg-white rounded-md text-gray-400 hover:text-gray-500">
                                                            <span class="sr-only">Close</span>
                                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Modal Body -->
                                                <div class="bg-white px-4 pb-4 sm:p-6 sm:pb-4">
                                                    <form method="POST">
                                                        <input type="hidden" name="user_id"
                                                            value="<?php echo htmlspecialchars($user['user_id']); ?>">

                                                        <div class="mb-4">
                                                            <label for="role<?php echo $user['user_id']; ?>"
                                                                class="block text-sm font-medium text-gray-700 mb-2">
                                                                Select Role
                                                            </label>
                                                            <select id="role<?php echo $user['user_id']; ?>"
                                                                name="role"
                                                                class="mt-1 block w-full rounded-md border-gray-300 py-2 px-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500">
                                                                <option value="employee" <?php echo ($user['role'] == 'employee') ? 'selected' : ''; ?>>
                                                                    Employee
                                                                </option>
                                                                <option value="new_user" <?php echo ($user['role'] == 'new_user') ? 'selected' : ''; ?>>
                                                                    New User
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Modal Footer -->
                                                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                            <button type="submit"
                                                                name="update_role"
                                                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                                                Save Changes
                                                            </button>
                                                            <button type="button"
                                                                onclick="closeModal('editRoleModal<?php echo $user['user_id']; ?>')"
                                                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
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
    <script src="../JS/manage_users_modal.js"></script>
    <script>
        function updateUserStatus(userId, action) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);

            fetch('../endpoint/update_user_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find and update the user's row
                        const row = document.querySelector(`tr:has(button[onclick*="${userId}"])`);
                        if (row) {
                            // Update row background
                            if (data.status === 'deactivated') {
                                row.classList.add('bg-gray-400');
                            } else {
                                row.classList.remove('bg-gray-400');
                            }

                            // Update status cell
                            const statusCell = row.querySelector('td:nth-child(5)');
                            if (statusCell) {
                                statusCell.textContent = data.status;
                            }

                            // Update action buttons
                            const actionCell = row.querySelector('td:last-child');
                            if (actionCell) {
                                if (data.status === 'deactivated') {
                                    actionCell.innerHTML = `
                                <button type="button" 
                                    onclick="updateUserStatus(${userId}, 'activate')" 
                                    class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                    Activate
                                </button>`;
                                } else {
                                    actionCell.innerHTML = `
                                <button type="button" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600" 
                                    onclick="openModal('editRoleModal${userId}')">
                                    Edit Role
                                </button>
                                <button type="button" 
                                    onclick="updateUserStatus(${userId}, 'deactivate')" 
                                    class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600 ml-2">
                                    Deactivate
                                </button>`;
                                }
                            }
                        }
                    } else {
                        alert('Error updating user status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating user status');
                });
        }
    </script>
</body>

</html>