<?php
session_start();
include('../conn/conn.php');

// Check if the user is logged in and has the appropriate role to add sales
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $products = $_POST['products'] ?? [];
    $categories = $_POST['category_names'] ?? [];
    $prices = $_POST['prices'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $sales_date = $_POST['sale_date']; // Get sales_date from the form

    try {
        // Start transaction
        $conn->beginTransaction();

        // Loop through each sale
        for ($i = 0; $i < count($products); $i++) {
            $product_id = $products[$i];
            $category = $categories[$i];
            $price = $prices[$i];
            $quantity = $quantities[$i];

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
            $stmt->bindParam(':category_name', $category);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':sale_date', $sales_date);
            $stmt->bindParam(':total_sales', $total_sales);

            $stmt->execute();
        }

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
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body style="background-color: #DADBDF;">
    <!-- Header -->
    <!-- Header -->
    <header class="flex flex-row sticky top-0 z-50">
        <div class="flex justify-center items-center text-white bg-green-800" style="width: 300px;">
            <img class="m-1" style="width: 120px; height:120px;" src="../icons/zefmaven.png">
        </div>

        <div class="flex items-center text-black p-3 flex-grow bg-gray-600">
            <div class="ml-6 flex flex-start text-white">
                <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
            </div>
            <div class="flex justify-end flex-grow text-white">
                <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            <!-- User dropdown component -->
            <div class="relative"
                x-data="{ isOpen: false }"
                @keydown.escape.stop="isOpen = false"
                @click.away="isOpen = false">

                <button class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    @click="isOpen = !isOpen"
                    type="button"
                    id="user-menu-button"
                    :aria-expanded="isOpen"
                    aria-haspopup="true">
                    <img src="../icons/user.svg" alt="User Icon" class="w-5 h-5 mr-2">
                    <span>user</span>
                    <svg class="w-4 h-4 ml-2 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right">

                    <ul class="bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg"
                                href="../features/user_settings.php"
                                role="menuitem">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                        </li>
                        <li>
                            <a class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg"
                                href="../endpoint/logout.php"
                                role="menuitem">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="flex">
        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <div class="p-2 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto flex-col">
            <div class="container mt-2 p-2 mx-auto">
                <h2 class="text-4xl font-bold mb-2">Add New Sale</h2>

                <?php if (isset($_SESSION['notification'])): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" id="notification">
                        <?php
                        echo $_SESSION['notification'];
                        unset($_SESSION['notification']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_sales.php" id="saleForm" class="p-4">
                    <div class="mb-6">
                        <label class="block text-2xl font-medium text-gray-700 mb-2">Sale Date</label>
                        <input type="date"
                            name="sale_date"
                            id="sale_date"
                            class="mt-2 w-60 h-12 text-lg px-4 py-2 rounded-lg border border-gray-300 
               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
               transition duration-150 ease-in-out"
                            max="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>
                    <div class="max-h-[70vh]">
                        <div id="salesContainer" class="space-y-2">
                            <div class="sale-row flex flex-wrap space-x-2 items-center bg-white p-2 rounded-lg shadow-sm">
                                <select name="products[]" class="w-40 h-8 text-sm rounded border" required>
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

                                <input type="text" name="categories[]" readonly
                                    class="w-32 h-8 text-sm bg-gray-100 rounded border">

                                <input type="hidden" name="category_names[]">

                                <input type="number" name="prices[]" readonly
                                    class="w-24 h-8 text-sm bg-gray-100 rounded border">

                                <input type="number" name="quantities[]" min="1" required
                                    class="w-24 h-8 text-sm rounded border">

                                <div class="text-sm">
                                    Total: ₱<span class="row-total">0.00</span>
                                </div>

                                <div class="stock-info text-sm text-gray-600 w-32"></div>

                                <div class="flex">
                                    <button type="button" class="remove-row text-red-500 hover:text-red-700 p-1"> Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Update the button and add counter HTML -->
                        <div class="flex items-center space-x-2">
                            <button type="button" id="addSaleRow"
                                class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Add Another Sale
                            </button>
                            <span id="rowCounter" class="mt-2 text-sm text-gray-600">
                                (8 slots remaining)
                            </span>
                        </div>

                        <div class="mt-4 text-xl font-bold">
                            Grand Total: ₱<span id="grandTotal">0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="mt-4 px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script src="../JS/add_salesValidation.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script src="../JS/time.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const salesContainer = document.getElementById('salesContainer');
            const addButton = document.getElementById('addSaleRow');
            const form = document.getElementById('saleForm');
            const MAX_ROWS = 8;

            // Set current date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('sale_date').value = today;

            function updateRowTotal(row) {
                const price = parseFloat(row.querySelector('[name="prices[]"]').value) || 0;
                const quantity = parseInt(row.querySelector('[name="quantities[]"]').value) || 0;
                const totalSpan = row.querySelector('.row-total');
                totalSpan.textContent = (price * quantity).toFixed(2);
                updateGrandTotal();
            }

            function updateGrandTotal() {
                const totals = [...document.querySelectorAll('.row-total')]
                    .map(span => parseFloat(span.textContent) || 0);
                const grandTotal = totals.reduce((sum, current) => sum + current, 0);
                document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
            }

            async function checkStock(productId, quantity) {
                try {
                    const response = await fetch('../endpoint/check_stock.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}&quantity=${quantity}`
                    });

                    return await response.json();
                } catch (error) {
                    console.error('Error checking stock:', error);
                    return {
                        success: false,
                        message: 'Error checking stock availability'
                    };
                }
            }

            function showError(message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                errorDiv.innerHTML = message;
                form.insertBefore(errorDiv, form.firstChild);
                setTimeout(() => errorDiv.remove(), 5000);
            }

            function setupRow(row) {
                const productSelect = row.querySelector('[name="products[]"]');
                const categoryInput = row.querySelector('[name="categories[]"]');
                const categoryNameInput = row.querySelector('[name="category_names[]"]');
                const priceInput = row.querySelector('[name="prices[]"]');
                const quantityInput = row.querySelector('[name="quantities[]"]');
                const stockInfo = row.querySelector('.stock-info');

                productSelect.addEventListener('change', async function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const categoryName = selectedOption.getAttribute('data-category-name') || 'No Category';
                    const price = selectedOption.getAttribute('data-price');
                    const stock = selectedOption.getAttribute('data-stock');

                    categoryInput.value = categoryName;
                    categoryNameInput.value = categoryName;
                    priceInput.value = price;
                    stockInfo.textContent = `Stock: ${stock}`;
                    updateRowTotal(row);
                });

                quantityInput.addEventListener('input', async function() {
                    const quantity = parseInt(this.value) || 0;
                    const productId = productSelect.value;

                    if (productId && quantity > 0) {
                        const result = await checkStock(productId, quantity);
                        if (!result.success || !result.isAvailable) {
                            showError(result.message);
                            this.value = '';
                            updateRowTotal(row);
                            return;
                        }
                    }
                    updateRowTotal(row);
                });

                row.querySelector('.remove-row').addEventListener('click', function() {
                    if (document.querySelectorAll('.sale-row').length > 1) {
                        row.remove();
                        updateGrandTotal();
                        updateAddButton();
                    }
                });
            }

            // Form submission handler
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                let isValid = true;
                const rows = document.querySelectorAll('.sale-row');

                for (const row of rows) {
                    const productId = row.querySelector('[name="products[]"]').value;
                    const quantity = parseInt(row.querySelector('[name="quantities[]"]').value) || 0;

                    if (productId && quantity > 0) {
                        const result = await checkStock(productId, quantity);
                        if (!result.success || !result.isAvailable) {
                            showError(result.message);
                            isValid = false;
                            break;
                        }
                    }
                }

                if (isValid) {
                    this.submit();
                }
            });

            // Setup initial row
            setupRow(salesContainer.querySelector('.sale-row'));

            // Add new row button
            addButton.addEventListener('click', function() {
                const currentRows = document.querySelectorAll('.sale-row').length;
                if (currentRows < MAX_ROWS) {
                    const newRow = salesContainer.querySelector('.sale-row').cloneNode(true);
                    newRow.querySelectorAll('input').forEach(input => input.value = '');
                    newRow.querySelector('select').selectedIndex = 0;
                    newRow.querySelector('.row-total').textContent = '0.00';
                    newRow.querySelector('.stock-info').textContent = '';
                    salesContainer.appendChild(newRow);
                    setupRow(newRow);
                    updateAddButton();
                }
            });

            function updateAddButton() {
                const currentRows = document.querySelectorAll('.sale-row').length;
                const remainingSlots = MAX_ROWS - currentRows;
                const counterDisplay = document.getElementById('rowCounter');
                
                if (currentRows >= MAX_ROWS) {
                    addButton.disabled = true;
                    addButton.textContent = 'Maximum Limit Reached';
                    addButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                    addButton.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-75');
                    counterDisplay.textContent = '(No slots remaining)';
                    counterDisplay.classList.remove('text-gray-600');
                    counterDisplay.classList.add('text-red-500');
                } else {
                    addButton.disabled = false;
                    addButton.textContent = 'Add Another Sale';
                    addButton.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-75');
                    addButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
                    counterDisplay.textContent = `(${remainingSlots} ${remainingSlots === 1 ? 'slot' : 'slots'} remaining)`;
                    counterDisplay.classList.remove('text-red-500');
                    counterDisplay.classList.add('text-gray-600');
                }
            }

            // Initial setup
            setupRow(salesContainer.querySelector('.sale-row'));
            updateAddButton();
        });
    </script>
</body>

</html>