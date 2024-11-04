<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in and has the appropriate role to add sales
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category_name = $_POST['category_name']; // Get category_name from the form

    try {
        // Start transaction
        $conn->beginTransaction();

        // Fetch product details including current quantity and name
        $stmt = $conn->prepare("SELECT product_name, quantity, price FROM products WHERE product_id = :product_id FOR UPDATE");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validation checks
        if (!$product) {
            throw new Exception("Product not found.");
        }
        if ($product['quantity'] < $quantity) {
            throw new Exception("Insufficient stock. Available: " . $product['quantity']);
        }
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        if ($price <= 0) {
            throw new Exception("Price must be greater than zero.");
        }

        // Update product quantity
        $new_quantity = $product['quantity'] - $quantity;
        $update_stmt = $conn->prepare("UPDATE products SET quantity = :new_quantity WHERE product_id = :product_id");
        $update_stmt->bindParam(':new_quantity', $new_quantity);
        $update_stmt->bindParam(':product_id', $product_id);
        $update_stmt->execute();

        // Insert into sales table
        $stmt = $conn->prepare("
            INSERT INTO sales (
                product_id, 
                product_name,
                category_name,
                price, 
                quantity, 
                sale_date,
                total_sales
            ) VALUES (
                :product_id,
                :product_name,
                :category_name,
                :price,
                :quantity,
                NOW(),
                :total_sales
            )
        ");

        $total_sales = $price * $quantity;

        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':product_name', $product['product_name']);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':total_sales', $total_sales);

        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['notification'] = 'Sale added successfully.';
        header("Location: manage_sales.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['notification'] = "Error: " . $e->getMessage();
        header("Location: add_sales.php");
        exit();
    }
}

// Fetch products with current stock information
$stmt = $conn->prepare("
    SELECT p.product_id, p.product_name, p.category_id, p.quantity, p.price, pc.category_name 
    FROM products p 
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    ORDER BY p.product_name
");
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
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <!-- Header remains the same -->
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
                <div class="alert alert-info" id="notification">
                    <?php
                    echo $_SESSION['notification'];
                    unset($_SESSION['notification']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_sales.php" id="saleForm">
                <div class="mb-3">
                    <label for="product" class="form-label">Product</label>
                    <select class="form-control" id="product" name="product_id" required>
                        <option value="">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                data-category-name="<?php echo htmlspecialchars($product['category_name']); ?>"
                                data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                data-stock="<?php echo htmlspecialchars($product['quantity']); ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                                (Stock: <?php echo htmlspecialchars($product['quantity']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <input type="text" class="form-control" id="category" name="category_name" readonly>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                    <small class="text-muted" id="stockInfo"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Amount</label>
                    <div id="totalAmount" class="form-control" readonly>0.00</div>
                </div>

                <button type="submit" class="btn btn-primary">Add Sale</button>
            </form>
        </div>
    </main>
    
    <script src="../JS/notificationTimer.js"></script>

    <script>
        $(document).ready(function() {
            $('#product').change(function() {
                const selected = $(this).find(':selected');
                const price = selected.data('price');
                const stock = selected.data('stock');
                const categoryName = selected.data('category-name');

                $('#price').val(price);
                $('#stockInfo').text(`Available stock: ${stock}`);
                $('#category').val(categoryName);
                updateTotal();
            });

            $('#price, #quantity').on('input', updateTotal);

            function updateTotal() {
                const price = parseFloat($('#price').val()) || 0;
                const quantity = parseInt($('#quantity').val()) || 0;
                const total = (price * quantity).toFixed(2);
                $('#totalAmount').text(total);
            }

            // Form validation
            $('#saleForm').submit(function(e) {
                const selected = $('#product').find(':selected');
                const stock = selected.data('stock');
                const quantity = parseInt($('#quantity').val());
                const price = parseFloat($('#price').val());

                if (quantity > stock) {
                    e.preventDefault();
                    alert('Quantity exceeds available stock!');
                    return false;
                }

                if (quantity <= 0) {
                    e.preventDefault();
                    alert('Quantity must be greater than zero!');
                    return false;
                }

                if (price <= 0) {
                    e.preventDefault();
                    alert('Price must be greater than zero!');
                    return false;
                }
            });
        });
    </script>

</body>

</html>