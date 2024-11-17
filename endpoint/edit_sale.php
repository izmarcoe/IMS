<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in as an employee or an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

// Check if the id is provided to edit
if (!isset($_GET['id'])) {
    $_SESSION['notification'] = 'No sale ID provided.';
    header("Location: ../features/manage_sales.php");
    exit();
}

$id = $_GET['id']; // Use 'id' instead of 'sale_id'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $sale_date = $_POST['sale_date']; // Get sale_date from the form

    try {
        // Start transaction
        $conn->beginTransaction();

        // Fetch product details
        $stmt = $conn->prepare("SELECT product_name, quantity FROM products WHERE product_id = :product_id FOR UPDATE");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validation checks
        if (!$product) {
            throw new Exception("Product not found.");
        }
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }
        if ($price <= 0) {
            throw new Exception("Price must be greater than zero.");
        }

        // Calculate the difference in quantity
        $old_quantity = $_POST['old_quantity']; // Assuming you have old quantity stored in a hidden field
        $quantity_difference = $quantity - $old_quantity;

        // If the new quantity is greater than the old quantity, check stock
        if ($quantity_difference > 0 && $product['quantity'] < $quantity_difference) {
            throw new Exception("Insufficient stock. Available: " . $product['quantity']);
        }

        // Update product quantity (if necessary)
        $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity - :quantity_difference WHERE product_id = :product_id");

        // Bind the quantity difference for the update
        $update_stmt->bindParam(':quantity_difference', $quantity_difference);
        $update_stmt->bindParam(':product_id', $product_id);
        $update_stmt->execute();

        // Update the sales record
        $stmt = $conn->prepare("UPDATE sales SET 
            product_id = :product_id, 
            product_name = :product_name, 
            price = :price, 
            quantity = :quantity, 
            total_sales = :total_sales,
            sale_date = :sale_date
            WHERE id = :id
        ");

        $total_sales = $price * $quantity;

        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':product_name', $product['product_name']);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':total_sales', $total_sales);
        $stmt->bindParam(':sale_date', $sale_date); // Bind sale_date
        $stmt->bindParam(':id', $id); // Use 'id' instead of 'sale_id'

        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['notification'] = 'Sale updated successfully.';
        header("Location: ../features/manage_sales.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['notification'] = "Error: " . $e->getMessage();
        header("Location: ../endpoint/edit_sale.php?id=" . $id); // Use 'id' instead of 'sale_id'
        exit();
    }
}

// Fetch the sale record to edit
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = :id"); // Use 'id' instead of 'sale_id'
$stmt->bindParam(':id', $id);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch products for the dropdown
$stmt = $conn->prepare("SELECT product_id, product_name, quantity, price FROM products ORDER BY product_name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sale</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <!-- Header -->
    <header class="d-flex flex-row">
        <div class="d-flex justify-content text-center align-items-center text-white" style="background-color: #0F7505;">
            <div class="" style="width: 300px">
                <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
            </div>
        </div>


        <div class="d-flex align-items-center text-black p-3 flex-grow-1" style="background-color: gray;">
            <div class="d-flex justify-content-start flex-grow-1 text-white">
                <span class="px-4" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><img src="../icons/user.svg" alt="User Icon" style="width: 20px; height: 20px; margin-right: 5px;"></span>
                    user
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="../endpoint/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="d-flex">
        <?php include '../features/sidebar.php' ?>

        <div class="container mt-5">
            <h2>Edit Sale</h2>

            <?php if (isset($_SESSION['notification'])): ?>
                <div class="alert alert-info">
                    <?php
                    echo $_SESSION['notification'];
                    unset($_SESSION['notification']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../endpoint/edit_sale.php?id=<?php echo htmlspecialchars($id); ?>" id="saleForm">
                <input type="hidden" name="old_quantity" value="<?php echo htmlspecialchars($sale['quantity']); ?>">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($sale['product_id']); ?>">
                <div class="mb-3">
                    <label for="product" class="form-label">Product</label>
                    <select class="form-control" id="product" name="product_id" disabled>
                        <option value="">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                <?php if ($product['product_id'] == $sale['product_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($product['product_name']); ?>
                                (Stock: <?php echo htmlspecialchars($product['quantity']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($sale['price']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($sale['quantity']); ?>" required>
                    <small class="text-muted" id="stockInfo"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Amount</label>
                    <div id="totalAmount" class="form-control" readonly>
                        <?php echo number_format($sale['total_sales'], 2); ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sale_date" class="form-label">Sale Date</label>
                    <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo htmlspecialchars($sale['sale_date']); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Update Sale</button>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            $('#product').change(function() {
                const selected = $(this).find(':selected');
                const price = selected.data('price');
                const stock = selected.data('stock');

                $('#price').val(price);
                $('#stockInfo').text(`Available stock: ${stock}`);
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