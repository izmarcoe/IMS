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
$stmt = $conn->prepare("SELECT * FROM product_categories ORDER BY category_name LIMIT :limit OFFSET :offset");
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
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../src/output.css" rel="stylesheet">
</head>
<body> 
          <!-- Header -->
    <header class="flex flex-row">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold">Admin Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="flex justify-end text-white mx-8">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-1"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../features/user_settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>
    <main class="flex">
        <aside>
            <?php include('../features/sidebar.php'); ?>
        </aside>

        <!-- Table Container -->
        <div class="p-4 md:p-8 bg-white rounded-lg shadow-md w-full max-w-[95vw] mx-auto">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl md:text-2xl font-semibold text-gray-800">Categories</h2>
                <button onclick="openAddModal()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 md:px-4 md:py-2 rounded-lg flex items-center text-sm md:text-base">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add New Category
                </button>
            </div>

            <!-- Table with responsive container -->
            <div class="overflow-x-auto relative">
                <div class="max-h-[70vh] overflow-y-auto">
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
                            <tr class="hover:bg-gray-50">
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
        </div>

        <!-- Add Category Modal -->
        <div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white z-50">
                <div class="mt-3">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Add New Category</h3>
                    <form action="../endpoint/process_category.php" method="POST">
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
                    <form action="../endpoint/process_category.php" method="POST">
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
    <script>
    let categoryToDelete = null;

    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(category) {
        document.getElementById('edit_id').value = category.id;
        document.getElementById('edit_category_name').value = category.category_name;
        document.getElementById('edit_description').value = category.description;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function openDeleteModal(id) {
        categoryToDelete = id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        categoryToDelete = null;
    }

    function confirmDelete() {
        if (categoryToDelete) {
            window.location.href = `category.php?delete_id=${categoryToDelete}`;
        }
    }
    </script>
</body>

</html>