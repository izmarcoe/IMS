<?php
session_start();
include('../conn/conn.php'); // Database connection file

// Check if the user is logged in and has the appropriate role to manage sales
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee') {
    header("Location: http://localhost/IMS/");
    exit();
}

// Check if the sale ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_sales.php");
    exit();
}

$saleId = $_GET['id'];

// Fetch the sale data
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = :id");
$stmt->bindParam(':id', $saleId, PDO::PARAM_INT);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header("Location: ../features/manage_sales.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update the sale data
    $productName = $_POST['product_name'];
    $categoryId = $_POST['category_id'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $saleDate = $_POST['sale_date'];

    $updateStmt = $conn->prepare("UPDATE sales SET product_name = :product_name, category_id = :category_id, price = :price, quantity = :quantity, sale_date = :sale_date WHERE id = :id");
    $updateStmt->bindParam(':product_name', $productName);
    $updateStmt->bindParam(':category_id', $categoryId);
    $updateStmt->bindParam(':price', $price);
    $updateStmt->bindParam(':quantity', $quantity);
    $updateStmt->bindParam(':sale_date', $saleDate);
    $updateStmt->bindParam(':id', $saleId, PDO::PARAM_INT);
    $updateStmt->execute();

    $_SESSION['notification'] = "Sale updated successfully!";
    header("Location: ../features/manage_sales.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sale</title>
    <link rel="stylesheet" href="../CSS/employee_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header class="d-flex justify-content-between align-items-center bg-danger text-white p-3">
        <h1 class="m-0">INVENTORY SYSTEM</h1>
        <div>
            <span id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
            <a class="btn btn-light ms-3" href="../endpoint/logout.php">Logout</a>
        </div>
    </header>
    <div class="d-flex">
        <?php include '../features/sidebar.php'; ?>
        <main class="flex-grow-1">
            <div class="container mt-5">
                <h2>Edit Sale</h2>
                <form method="POST" class="p-3 border rounded">
                    <!-- Display product_name and category_id as non-editable fields -->
                    <div class="mb-3">
                        <label class="form-label">Product Name:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($sale['product_name']); ?>" readonly>
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($sale['product_name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category ID:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($sale['category_id']); ?>" readonly>
                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($sale['category_id']); ?>">
                    </div>

                    <!-- Editable fields for quantity, price, and sale_date -->
                    <div class="mb-3">
                        <label class="form-label">Quantity:</label>
                        <input type="number" name="quantity" class="form-control" value="<?php echo htmlspecialchars($sale['quantity']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price:</label>
                        <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($sale['price']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sale Date:</label>
                        <input type="date" name="sale_date" class="form-control" value="<?php echo htmlspecialchars($sale['sale_date']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="../features/manage_sales.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
