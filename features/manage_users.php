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

    <main class="flex flex-row">
        <div class="flex">
            <aside>
                <?php include '../features/sidebar.php'; ?>
            </aside>
            <div class="container mx-auto mt-4 z-30 px-8 max-w-7xl">
                <h2 class="mt-6 text-5xl font-bold">Manage Users</h2>
                <table class="mt-6 w-full border-collapse border-2 border-gray-500 min-w-[1024px]">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-100 px-6 py-3">User ID</th>
                            <th class="border border-gray-100 px-6 py-3">First Name</th>
                            <th class="border border-gray-100 px-6 py-3">Last Name</th>
                            <th class="border border-gray-100 px-6 py-3">Role</th>
                            <th class="border border-gray-100 px-6 py-3">Status</th>
                            <th class="border border-gray-100 px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo $user['status'] == 'deactivated' ? 'bg-gray-400' : ''; ?>">
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['Fname']); ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['Lname']); ?></td>
                                <td class="border border-gray-300 px-4 py-2">
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
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['status']); ?></td>
                                <td class="border border-gray-300 px-4 py-2">
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
                                                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>
                                                                    Admin
                                                                </option>
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