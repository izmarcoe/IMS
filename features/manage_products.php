<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage products
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Fetch products from the database
$stmt = $conn->prepare("SELECT * FROM products");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
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
            <h2>Manage Products</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr data-product-id="<?php echo $product['product_id']; ?>">
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo htmlspecialchars($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="openEditModal(<?php echo $product['product_id']; ?>)">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?php echo $product['product_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <input type="hidden" id="edit_product_id" name="id">
                            <div class="mb-3">
                                <label for="edit_product_name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="edit_category" name="category">
                            </div>
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="edit_quantity" name="quantity" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Delete Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this product?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let currentEditId;
                let currentDeleteId;

                window.openEditModal = function(id) {
                    currentEditId = id;

                    // Fetch product details
                    fetch(`../endpoint/get_product.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }

                            document.getElementById('edit_product_id').value = data.product_id;
                            document.getElementById('edit_product_name').value = data.product_name;
                            document.getElementById('edit_category').value = data.category;
                            document.getElementById('edit_price').value = data.price;
                            document.getElementById('edit_quantity').value = data.quantity;

                            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                            editModal.show();
                        })
                        .catch(error => {
                            console.error('Error fetching product details:', error);
                        });
                };

                document.getElementById('editForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    const formData = new FormData(this); // Collect form data

                    // Add product_id to formData
                    formData.append('product_id', currentEditId); // This ensures the ID is sent for updating

                    // Send an AJAX request to update the product
                    fetch('../endpoint/edit_product.php', {
                            method: 'POST',
                            body: formData,
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message); // Display success/error message
                            if (data.success) {
                                location.reload(); // Reload to show updated product list
                            }
                        })
                        .catch(error => {
                            console.error('Error updating product:', error);
                            alert('There was an error updating the product. Please try again.'); // Display error message
                        });
                });


                window.openDeleteModal = function(id) {
                    currentDeleteId = id;
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                };

                document.getElementById('confirmDelete').addEventListener('click', function() {
                    fetch(`../endpoint/delete_product.php?delete_id=${currentDeleteId}`)
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            location.reload(); // Reload to refresh the product list
                        });
                });
            });
        </script>
    </main>
</body>

</html>