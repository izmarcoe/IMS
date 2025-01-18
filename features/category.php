<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

// Pagination setup
$limit = 8;
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

$fname = $_SESSION['Fname'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories</title>
    <link href="../src/output.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-[#DADBDF] h-screen overflow-hidden" data-user-role="<?php echo $_SESSION['user_role']; ?>">
    <?php include '../features/header.php' ?>
    <main class="flex">
        <aside>
            <?php include('../features/sidebar.php'); ?>
        </aside>

        <!-- Table Container -->
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Categories</h2>
                <div class="flex items-center space-x-4">
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="archive-categories-table.php"
                            class="text-blue-500 hover:text-blue-700 flex items-center text-xs sm:text-base transition-all duration-200">
                            <i class="fas fa-archive mr-1 sm:mr-2 text-xs sm:text-base"></i>
                            <span>View Archived Categories</span>
                        </a>
                    <?php endif; ?>
                    <button onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        Add New Category
                    </button>
                </div>
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
                                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                <button onclick="openArchiveModal(<?php echo $category['id']; ?>)"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                                    Archive
                                                </button>
                                            <?php endif; ?>
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

    </main>
    <script src="../JS/time.js"></script>
    <script src="../JS/category_modal.js"></script>
    <script>
        function openArchiveModal(categoryId) {
            Swal.fire({
                title: 'Archive Category?',
                text: 'This will archive the category and all its products. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../endpoint/archive-category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `category_id=${categoryId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Archived!',
                                    text: `Category and ${data.productsArchived} products have been archived.`,
                                    icon: 'success',
                                    confirmButtonColor: '#10B981'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.error);
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error!',
                                text: error.message,
                                icon: 'error',
                                confirmButtonColor: '#EF4444'
                            });
                        });
                }
            });
        }

        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../endpoint/process_category.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const categoryId = formData.get('id');
                        const categoryName = formData.get('category_name');
                        const description = formData.get('description');

                        // Update row content in real-time
                        const row = document.getElementById(`category-${categoryId}`);
                        if (row) {
                            const isAdmin = document.body.getAttribute('data-user-role') === 'admin';
                            row.innerHTML = `
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                        <div class="text-sm md:text-base text-gray-900">${categoryName}</div>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <div class="text-sm md:text-base text-gray-900 break-words">${description}</div>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm md:text-base">
                        <div class="flex space-x-2">
                            <button onclick="openEditModal({id: ${categoryId}, category_name: '${categoryName}', description: '${description}'})" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                Edit
                            </button>
                            ${isAdmin ? `
                                <button onclick="openArchiveModal(${categoryId})"
                                    class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-1 rounded-md text-sm">
                                    Archive
                                </button>
                            ` : ''}
                        </div>
                    </td>
                `;
                        }

                        closeEditModal();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Category updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Failed to update category');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message || 'Something went wrong',
                        confirmButtonColor: '#3085d6'
                    });
                });
        });
        document.getElementById('edit_description').addEventListener('input', function() {
            const maxLength = 50;
            const currentLength = this.value.length;

            if (currentLength > maxLength) {
                this.value = this.value.substring(0, maxLength);
            }
        });
    </script>
</body>

</html>