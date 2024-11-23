<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="bg-dark text-white min-vh-100 p-1" style="width: 300px;">
    <div class="pt-5">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>" href="../dashboards/admin_dashboard.php" onclick="setActive(this)">
                <img src="../icons/dashboard.svg" style="height:25px; margin-right: 10px;"> Dashboard
            </a>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employee'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'employee_dashboard.php') ? 'active' : ''; ?>" href="../dashboards/employee_dashboard.php" onclick="setActive(this)">
                <img src="../icons/dashboard.svg" style="height:25px; margin-right: 10px;"> Dashboard
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>" href="../features/manage_users.php" onclick="setActive(this)">
                <img src="../icons/usermanagement.svg" style="height:25px; margin-right: 10px;"> User Management
            </a>
        <?php else: ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="#" style="pointer-events: none; color: gray;">
                <img src="../icons/usermanagement.svg" style="height:25px; margin-right: 10px;"> User Management
            </a>
        <?php endif; ?>

        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'category.php') ? 'active' : ''; ?>" href="../features/category.php" onclick="setActive(this)">
            <img src="../icons/categories.svg" style="height:25px; margin-right: 10px;"> Categories
        </a>

        <!-- Products Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'manage_products.php' || $current_page == 'add_product.php') ? 'active' : ''; ?>" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="productsCollapse" onclick="setActive(this, event)">
            <img src="../icons/cart.svg" style="height:25px; margin-right: 10px;"> Products
        </a>
        <div class="collapse <?php echo ($current_page == 'manage_products.php' || $current_page == 'add_product.php') ? 'show' : ''; ?>" id="productsCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'manage_products.php') ? 'active' : ''; ?>" href="../features/manage_products.php" onclick="setActive(this, event)">Manage Products</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'add_product.php') ? 'active' : ''; ?>" href="../features/add_product.php" onclick="setActive(this, event)">Add Products</a>
        </div>

        <!-- Sales Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo ($current_page == 'manage_sales.php' || $current_page == 'add_sales.php') ? 'active' : ''; ?>" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="salesCollapse" onclick="setActive(this, event)">
            <img src="../icons/sales.svg" style="height:35px; margin-right: 10px;"> Sales
        </a>
        <div class="collapse <?php echo ($current_page == 'manage_sales.php' || $current_page == 'add_sales.php') ? 'show' : ''; ?>" id="salesCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'manage_sales.php') ? 'active' : ''; ?>" href="../features/manage_sales.php" onclick="setActive(this, event)">Manage Sales</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'add_sales.php') ? 'active' : ''; ?>" href="../features/add_sales.php" onclick="setActive(this, event)">Add Sales</a>
        </div>

        <!-- Sales Report Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center <?php echo in_array($current_page, ['salesPerDay.php', 'salesPerMonth.php', 'salesDateRange.php', 'forecasting.php']) ? 'active' : ''; ?>" href="#reportCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="reportCollapse" onclick="setActive(this, event)">
            <img src="../icons/salesreport.svg" style="height:25px; margin-right: 10px;">Sales Report
        </a>
        <div class="collapse <?php echo in_array($current_page, ['salesPerDay.php', 'salesPerMonth.php', 'salesDateRange.php', 'forecasting.php']) ? 'show' : ''; ?>" id="reportCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'salesPerDay.php') ? 'active' : ''; ?>" href="../features/salesPerDay.php" onclick="setActive(this, event)">Sales by Date</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'salesPerMonth.php') ? 'active' : ''; ?>" href="../features/salesPerMonth.php" onclick="setActive(this, event)">Sales by Month</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'salesDateRange.php') ? 'active' : ''; ?>" href="../features/salesDateRange.php" onclick="setActive(this, event)">Sales by Date Range</a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <a class="sidebar-link fs-5 py-3 ps-4 mb-3 <?php echo ($current_page == 'forecasting.php') ? 'active' : ''; ?>" href="../features-AI/forecasting.php" onclick="setActive(this, event)">Predictive Analytics</a>
            <?php endif; ?>
        </div>

        <script src="../JS/sidebarCollapse.js"></script>
    </div>
</div>