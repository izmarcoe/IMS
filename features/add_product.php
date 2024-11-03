<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST['product_name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    if (empty($product_name) || empty($category_id) || empty($price) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Check if product with the same name and price already exists
            $stmt = $conn->prepare("SELECT product_id, quantity FROM products WHERE product_name = :product_name AND price = :price LIMIT 1");
            $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->execute();
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingProduct) {
                // Update quantity if the same product name and price exists
                $newQuantity = $existingProduct['quantity'] + $quantity;
                $stmt = $conn->prepare("UPDATE products SET quantity = :new_quantity WHERE product_id = :product_id");
                $stmt->bindParam(':new_quantity', $newQuantity, PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $existingProduct['product_id'], PDO::PARAM_INT);
                $stmt->execute();
                $success = "Product quantity updated successfully!";
            } else {
                // Insert as new product if name and price combination is new
                $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, price, quantity) VALUES (:product_name, :category_id, :price, :quantity)");
                $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->execute();
                $success = "New product added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}


// Determine the active page
$current_page = basename($_SERVER['PHP_SELF']); // Get the current script name
// Define active class based on the current page
$active_add_product = ($current_page == 'add-product.php') ? 'active' : '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    
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
        <?php include '../features/sidebar.php' ?>

        <!--ADD-->
        <div class="container mt-5">
            <h2>Add New Product</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php
                        // Fetch categories
                        $stmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($categories as $category) {
                            echo '<option value="' . htmlspecialchars($category['id']) . '">'
                                . htmlspecialchars($category['category_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </main>
    <script src="../JS/time.js"></script>
    <script>
        //to handle the dropdown dynamically with AJAX instead of server-side rendering
        function loadCategories() {
            fetch('../features/category.php', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(categories => {
                    const dropdown = document.getElementById('category');
                    dropdown.innerHTML = '<option value="">Select a category</option>';

                    categories.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.category_name;
                        dropdown.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading categories:', error));
        }

        // Load categories when the page loads
        document.addEventListener('DOMContentLoaded', loadCategories);
    </script>
</body>

</html>