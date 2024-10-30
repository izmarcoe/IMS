<?php
session_start();

include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to add sales
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $product_id = $_POST['product_id'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $sale_date = date('Y-m-d H:i:s'); // Get the current date and time

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO sales (product_id, category_id, price, quantity, sale_date) 
                             VALUES (:product_id, :category_id, :price, :quantity, :sale_date)");

    // Bind parameters
    $stmt->bindParam(':product_id', $product_id);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':sale_date', $sale_date);

    // Execute the statement and set the session variable based on the result
    if ($stmt->execute()) {
        $_SESSION['notification'] = "Sale added successfully!";
    } else {
        $_SESSION['notification'] = "Failed to add sale.";
    }

    // Redirect to the same page to show the notification
    header("Location: add_sales.php");
    exit();
}

// Fetch categories for the dropdown
$stmt = $conn->prepare("SELECT id, category_name FROM product_categories ORDER BY category_name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products for the dropdown
$stmt = $conn->prepare("SELECT product_id, product_name, category_id FROM products ORDER BY product_name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sales</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
</head>

<body>
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>

    <main class="d-flex">
        <?php include '../features/sidebar.php' ?>

        <div class="container mt-5">
            <h2>Add New Sale</h2>

            <?php if (isset($_SESSION['notification'])): ?>
                <div class="alert alert-info">
                    <?php
                    echo $_SESSION['notification'];
                    unset($_SESSION['notification']); // Clear the notification after displaying it
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_sales.php">
                <div class="mb-3">
                    <label for="product" class="form-label">Product</label>
                    <select class="form-control" id="product" name="product_id" required>
                        <option value="">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                    data-category-id="<?php echo htmlspecialchars($product['category_id']); ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
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
                <button type="submit" class="btn btn-primary">Add Sale</button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/time.js"></script>
    <script>
        $(document).ready(function () {
            $('#product').change(function () {
                // Get the selected product's category ID
                var categoryId = $(this).find(':selected').data('category-id');
                // Set the category dropdown to the corresponding category
                $('#category').val(categoryId);
            });
        });
    </script>
</body>

</html>
