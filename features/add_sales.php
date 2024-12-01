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
    $category_name = $_POST['category_name'];
    $sales_date = $_POST['sale_date']; // Get sales_date from the form

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
                :sale_date,
                :total_sales
            )
        ");

        $total_sales = $price * $quantity;

        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':product_name', $product['product_name']);
        $stmt->bindParam(':category_name', $category_name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':sale_date', $sales_date);
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
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.quantity,
        pc.category_name
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id 
    WHERE p.quantity > 0
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
    <link rel="stylesheet" href="../CSS/dashboard.css">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="../bootstrap/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body style="background-color: #DADBDF;">
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
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <div class="container mt-3 p-4 mx-auto">
                <h2 class="text-2xl font-bold mb-4">Add New Sale</h2>

                <?php if (isset($_SESSION['notification'])): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" id="notification">
                        <?php
                        echo $_SESSION['notification'];
                        unset($_SESSION['notification']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_sales.php" id="saleForm" class="space-y-6">
                    <div class="mb-4">
                        <label for="product" class="block text-gray-700 text-sm font-bold mb-2">Product</label>
                        <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            id="product" 
                            name="product_id" 
                            required>
                            <option value="" disabled selected>Select a product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                    data-category-name="<?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>"
                                    data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                    data-stock="<?php echo htmlspecialchars($product['quantity']); ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                    (Stock: <?php echo htmlspecialchars($product['quantity']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                        <input type="text"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100"
                            id="category"
                            readonly>
                    </div>

                    <input type="hidden" id="category_name" name="category_name">

                    <div class="mb-4">
                        <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                        <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100"
                            id="price"
                            name="price"
                            readonly>
                    </div>

                    <div class="mb-4">
                        <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity</label>
                        <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="quantity"
                            name="quantity"
                            min="1"
                            required>
                        <small id="stockInfo" class="text-gray-500"></small>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Total Amount</label>
                        <div id="totalAmount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100">
                            0.00
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="sale_date" class="block text-gray-700 text-sm font-bold mb-2">Sale Date</label>
                        <input type="date"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="sale_date"
                            name="sale_date"
                            value="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Add Sale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="../JS/add_salesValidation.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product');
    const categoryInput = document.getElementById('category');
    const categoryNameInput = document.getElementById('category_name');
    const priceInput = document.getElementById('price');
    const quantityInput = document.getElementById('quantity');
    const totalAmountDiv = document.getElementById('totalAmount');
    const stockInfo = document.getElementById('stockInfo');

    productSelect.addEventListener('change', function() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const categoryName = selectedOption.getAttribute('data-category-name') || 'No Category';
        const price = selectedOption.getAttribute('data-price');
        const stock = selectedOption.getAttribute('data-stock');

        categoryInput.value = categoryName;
        categoryNameInput.value = categoryName;
        priceInput.value = price;
        stockInfo.textContent = `Available stock: ${stock}`;
        updateTotalAmount();
    });

    quantityInput.addEventListener('input', updateTotalAmount);

    function updateTotalAmount() {
        const price = parseFloat(priceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const totalAmount = price * quantity;
        totalAmountDiv.textContent = totalAmount.toFixed(2);
    }
});
    </script>
</body>

</html>