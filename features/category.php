<?php
session_start();
include('../conn/conn.php');

// Check for user authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Handle category deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = :id");
    $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Fetch all categories
$stmt = $conn->prepare("SELECT * FROM product_categories ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


// If AJAX request, return categories as JSON
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $stmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
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
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>

    <!-- Content -->
    <main class="d-flex">
        <!-- Sidebar -->
        <?php include '../features/sidebar.php'; ?>

        <div class="container mt-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2>Product Categories</h2>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        Add New Category
                    </button>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['id']); ?></td>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../endpoint/process_category.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <input type="hidden" name="action" value="add">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../endpoint/process_category.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="edit_id" name="id">
                            <div class="mb-3">
                                <label for="edit_category_name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            <input type="hidden" name="action" value="edit">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this category?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/time.js"></script>
    <script>
        // Initialize modals
        const editCategoryModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        const deleteCategoryModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        let categoryToDelete = null;

        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_category_name').value = category.category_name;
            document.getElementById('edit_description').value = category.description;
            editCategoryModal.show();
        }

        function deleteCategory(id) {
            categoryToDelete = id;
            deleteCategoryModal.show();
        }

        // Handle delete confirmation
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (categoryToDelete) {
                window.location.href = `../features/categories.php?delete_id=${categoryToDelete}`;
            }
        });
    </script>
</body>
</html>