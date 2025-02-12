<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to add products
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'employee' && $_SESSION['user_role'] != 'admin')) {
    header("Location: http://localhost/IMS/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = trim($_POST['product_name']);
    // Check if category_id exists in POST data
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $error = '';

    // Validate all required fields
    if (!$category_id) {
        $error = "Category is required";
    } elseif ($price <= 0 || $price > 99999.00) {
        $error = "Price must be between 0 and 99,999.00 pesos";
    } elseif ($quantity <= 0 || $quantity > 99) {
        $error = "Quantity must be between 1 and 99";
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


$fname = $_SESSION['Fname'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/roleMonitor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    /* Remove spinner arrows for Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>

<body style="background-color: #DADBDF;">
    <!-- Header -->
    <?php include '../features/header.php' ?>
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
                    <label for="product_name" class="block text-gray-700 text-sm font-bold mb-2">
                        Product Name
                        <span id="charCount" class="text-sm text-gray-500 ml-2">(0/25)</span>
                    </label>
                    <input type="text"
                        id="product_name"
                        name="product_name"
                        maxlength="25"
                        oninput="updateCharCount(this)"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Enter product name (max 25 characters)"
                        required>
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
                <div id="priceContainer" class="mb-4 hidden">
                    <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                    <input type="text"
                        id="price"
                        name="price"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        pattern="^\d{1,5}(\.\d{0,2})?$"
                        placeholder="Enter price (1-99,999.99)"
                        required>
                </div>

                <div id="quantityContainer" class="mb-4 hidden">
                    <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity</label>
                    <input type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        max="999"
                        maxlength="3"
                        onkeydown="return event.keyCode !== 190 && event.keyCode !== 110"
                        oninput="if (this.value.length > 3) this.value = this.value.slice(0, 3); if (this.value > 999) this.value = 999;"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Enter quantity (1-999)"
                        required>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded focus:outline-none focus:shadow-outline">
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

            // Get trimmed product name
            const productName = document.getElementById('product_name').value.trim();
            const category = document.getElementById('category').value;
            const price = parseFloat(document.getElementById('price').value);
            const quantity = parseInt(document.getElementById('quantity').value);

            // Update product name field with trimmed value
            document.getElementById('product_name').value = productName;

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

        // Add input event listener for real-time trimming
        document.getElementById('product_name').addEventListener('input', function(e) {
            this.value = this.value.trim();
        });

        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.querySelector('input[name="price"]').value);
            const quantity = parseInt(document.querySelector('input[name="quantity"]').value);

            if (price <= 0 || price > 99999.00) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Price',
                    text: 'Price must be between 0 and 99,999.00 pesos'
                });
                return;
            }

            if (quantity <= 0 || quantity > 999) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Quantity',
                    text: 'Quantity must be between 1 and 999'
                });
                return;
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addProductForm = document.getElementById('addProductForm');
            
            addProductForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent form submission first
                
                const price = parseFloat(document.querySelector('input[name="price"]').value);
                const quantity = parseInt(document.querySelector('input[name="quantity"]').value);

                // Validate price
                if (isNaN(price) || price <= 0 || price > 99999.00) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Price',
                        text: 'Price must be between 0 and 99,999.00 pesos',
                        confirmButtonColor: '#3085d6'
                    });
                    return false;
                }

                // Validate quantity
                if (isNaN(quantity) || quantity <= 0 || quantity > 999) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Quantity',
                        text: 'Quantity must be between 1 and 999',
                        confirmButtonColor: '#3085d6'
                    });
                    return false;
                }

                // If validation passes, submit the form
                this.submit();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#price').on('input', function(e) {
                let value = $(this).val();
                
                // Allow only numbers and decimal point
                value = value.replace(/[^\d.]/g, '');
                
                // Ensure single decimal point
                let parts = value.split('.');
                if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
                
                // Limit to 5 digits before decimal
                if (parts[0] && parts[0].length > 5) {
                    parts[0] = parts[0].slice(0, 5);
                }
                
                // Limit to 2 decimal places
                if (parts[1] && parts[1].length > 2) {
                    parts[1] = parts[1].slice(0, 2);
                }
                
                value = parts.join('.');
                $(this).val(value);
                
                // Validate range
                const numValue = parseFloat(value);
                if (numValue > 99999.99) {
                    $(this).val('99999.99');
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Price',
                        text: 'Maximum price is 99,999.99',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const productName = document.querySelector('input[name="product_name"]');
    const category = document.querySelector('select[name="category_id"]');
    const priceContainer = document.getElementById('priceContainer');
    const quantityContainer = document.getElementById('quantityContainer');

    function checkInputs() {
        if (productName.value.trim() !== '' && category.value !== '') {
            priceContainer.classList.remove('hidden');
            quantityContainer.classList.remove('hidden');
        } else {
            priceContainer.classList.add('hidden');
            quantityContainer.classList.add('hidden');
        }
    }

    productName.addEventListener('input', checkInputs);
    category.addEventListener('change', checkInputs);
});
</script>
<script>
function updateCharCount(input) {
    const maxLength = 25;
    const currentLength = input.value.length;
    document.getElementById('charCount').textContent = 
        `(${currentLength}/${maxLength})`;
    
    if (currentLength === maxLength) {
        Swal.fire({
            icon: 'warning',
            title: 'Character Limit Reached',
            text: 'Product name cannot exceed 25 characters',
            timer: 2000,
            showConfirmButton: false
        });
    }
}
</script>

</body>

</html>