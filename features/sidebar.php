<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System Sidebar</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../CSS/employee_dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="bg-dark text-white vh-100 p-3" style="width: 300px;">
        <div class="pt-5">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <a class="sidebar-link fs-5 py-3 mb-3" href="../dashboards/admin_dashboard.php" onclick="setActive(this)">Dashboard</a>
            <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employee'): ?>
                <a class="sidebar-link fs-5 py-3 mb-3" href="../dashboards/employee_dashboard.php" onclick="setActive(this)">Dashboard</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <a class="sidebar-link fs-5 py-3 mb-3" href="../features/manage_users.php" onclick="setActive(this)">User Management</a>
            <?php else: ?>
                <a class="sidebar-link fs-5 py-3 mb-3" href="#" style="pointer-events: none; color: gray;">User Management</a>
            <?php endif; ?>
            
            <a class="sidebar-link fs-5 py-3 mb-3" href="../features/category.php" onclick="setActive(this)">Categories</a>
            
            <!-- Products Collapse Dropdown -->
            <a class="sidebar-link fs-5 py-3 mb-3" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="productsCollapse" onclick="setActive(this)">
                Products
            </a>
            <div class="collapse" id="productsCollapse">
                <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/manage_products.php" onclick="setActive(this)">Manage Products</a>
                <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/add_product.php" onclick="setActive(this)">Add Products</a>
            </div>
            
            <!-- Sales Collapse Dropdown -->
            <a class="sidebar-link fs-5 py-3 mb-3" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="salesCollapse" onclick="setActive(this)">
                Sales
            </a>
            <div class="collapse" id="salesCollapse">
                <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/manage_sales.php" onclick="setActive(this)">Manage Sales</a>
                <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/add_sales.php" onclick="setActive(this)">Add Sales</a>
            </div>
            
            <a class="sidebar-link fs-5 py-3 mb-3" href="#" onclick="setActive(this)">Sales Report</a>
        </div>
    </div>

    <script>
        // Ensure page refresh on navigation
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };

        function setActive(link) {
            // Check if the clicked link is a sidebar link
            if (!link.classList.contains('sidebar-link')) return;

            // Get all sidebar links
            const links = document.querySelectorAll('.sidebar-link');

            // Remove active class from all links except the current one
            links.forEach((item) => {
                if (item !== link) {
                    item.classList.remove('active', 'bg-warning', 'text-dark');
                }
            });

            // Toggle active class for the clicked link
            if (!link.classList.contains('active')) {
                link.classList.add('active', 'bg-warning', 'text-dark');
            }
        }
    </script>
</body>
</html>
