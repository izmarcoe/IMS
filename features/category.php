<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

// Handle category deletion
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];

        $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = :id");
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: category.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch all categories with pagination
$stmt = $conn->prepare("SELECT * FROM product_categories ORDER BY category_name ASC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of categories for pagination
$total_stmt = $conn->prepare("SELECT COUNT(*) FROM product_categories");
$total_stmt->execute();
$total_categories = $total_stmt->fetchColumn();
$total_pages = ceil($total_categories / $limit);

// If AJAX request, return categories as JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $stmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($categories);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories</title>
    <link href="../src/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#DADBDF] h-screen overflow-hidden">
    <!-- Header -->
    <header class="flex flex-row sticky top-0 z-50">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

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
                    <span>user</span>
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
    <main class="flex">
        <aside>
            <?php include('../features/sidebar.php'); ?>
        </aside>

        <!-- Table Container -->
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl md:text-2xl font-semibold text-gray-800">Categories</h2>
                <button onclick="openAddModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 md:px-4 md:py-2 rounded-lg flex items-center text-sm md:text-base">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Category
                </button>
            </div>

            <!-- Table with responsive container -->
            <div class="overflow-x-auto relative">
                <div class="w-full"> <!-- Removed max-height and overflow -->
                    <table class="w-full divide-y divide-gray-200 table-auto">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/3">
                                    Category Name
                                </th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider w-1/2">
                                    Description
                                </th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs md:text-sm font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                                <tr id="category-<?php echo $category['id']; ?>" class="hover:bg-gray-50">
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm md:text-base text-gray-900"><?php echo htmlspecialchars($category['category_name']); ?></div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4">
                                        <div class="text-sm md:text-base text-gray-900 break-words"><?php echo htmlspecialchars($category['description']); ?></div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm md:text-base">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                                Edit
                                            </button>
                                            <button onclick="openDeleteModal(<?php echo $category['id']; ?>)"
                                                class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center items-center mt-4 space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=1" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        First
                    </a>
                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        Previous
                    </a>
                <?php endif; ?>

                <?php
                // Calculate the range of page numbers to display
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?php echo $i; ?>"
                        class="px-3 py-2 <?php echo $i == $page ? 'bg-green-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded-md">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        Next
                    </a>
                    <a href="?page=<?php echo $total_pages; ?>" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        Last
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white z-50">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add New Category</h3>
                    <form id="addCategoryForm" action="../endpoint/process_category.php" method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Category Name</label>
                            <input type="text" name="category_name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                            <textarea name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500"></textarea>
                        </div>
                        <input type="hidden" name="action" value="add">
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeAddModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white z-50">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit Category</h3>
                    <form id="editCategoryForm" action="../endpoint/process_category.php" method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Category Name</label>
                            <input type="text" id="edit_category_name" name="category_name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                            <textarea id="edit_description" name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-green-500"></textarea>
                        </div>
                        <input type="hidden" name="action" value="edit">
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeEditModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white z-50">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Delete Category</h3>
                <p class="mb-4 text-sm text-gray-500">Are you sure you want to delete this category? This action cannot be undone.</p>
                <div class="flex justify-end gap-2">
                    <button onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button onclick="confirmDelete()"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>

    </main>
    <script src="../JS/time.js"></script>
    <script src="../JS/category_modal.js"></script>
</body>

</html>