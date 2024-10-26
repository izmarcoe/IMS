<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // Validate fields (basic example)
    if (empty($product_name) || empty($price) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, quantity) VALUES (:product_name, :category, :price, :quantity)");
        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            $error = "Error adding product. Please try again.";
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

        <form method="POST" action="add_product.php">
            <div class="mb-3">
                <label for="product_name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="product_name" name="product_name" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" name="category">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</body>
</html>
