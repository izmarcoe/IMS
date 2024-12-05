<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
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
                $_SESSION['notification'] = "Product quantity updated successfully!";
            } else {
                // Insert as new product if name and price combination is new
                $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, price, quantity) VALUES (:product_name, :category_id, :price, :quantity)");
                $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['notification'] = "New product added successfully!";
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
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    /* Remove spinner arrows for Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Remove spinner arrows for Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<body style="background-color: #DADBDF;">
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
    <!-- Content -->
    <main class="flex">

        <aside>
            <?php include '../features/sidebar.php' ?>
        </aside>
        <!--ADD-->
        <div class="p-4 md:p-8 rounded-lg shadow-md w-full max-w-[95vw] mx-auto">
            <h2 class="text-2xl font-bold my-6">Add New Product</h2>

            <?php if (isset($_SESSION['notification'])): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" id="notification">
                    <?php
                    echo $_SESSION['notification'];
                    unset($_SESSION['notification']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" id="addProductForm" onsubmit="return validateForm(event)">
                <div class="mb-4">
                    <label for="product_name" class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="product_name" name="product_name">
                </div>
                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                        id="category"
                        name="category_id">
                        <option value="" disabled selected>Select a category</option>
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
                <div class="mb-4">
                    <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                    <input type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="price" name="price">
                </div>
                <div class="mb-4">
                    <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity</label>
                    <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="quantity" name="quantity">
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Add Product
                </button>
            </form>
        </div>
    </main>
    <script src="../JS/time.js"></script>
    <script src="../JS/notificationTimer.js"></script>
    <script>
        // Function to load categories dynamically with AJAX
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
    <script>
        function validateForm(event) {
            event.preventDefault();

            const price = parseFloat(document.getElementById('price').value);
            const quantity = parseInt(document.getElementById('quantity').value);
            const productName = document.getElementById('product_name').value.trim();
            const category = document.getElementById('category').value;

            if (!productName) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Product name cannot be empty!'
                });
                return false;
            }

            if (!category) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Please select a category!'
                });
                return false;
            }

            if (isNaN(price) || price <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Price',
                    text: 'Price must be greater than 0!'
                });
                return false;
            }

            if (isNaN(quantity) || quantity <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Quantity must be greater than 0!'
                });
                return false;
            }

            // If validation passes, submit the form
            document.getElementById('addProductForm').submit();
            return true;
        }
    </script>
</body>

</html>