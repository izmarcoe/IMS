<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../CSS/dashboard.css">

<div class="bg-dark text-white min-vh-100 p-1" style="width: 300px;">
    <div class="pt-5">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="../dashboards/admin_dashboard.php" onclick="setActive(this)">
                <img src="../icons/dashboard.svg" style="height:25px; margin-right: 10px;"> Dashboard
            </a>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employee'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="../dashboards/employee_dashboard.php" onclick="setActive(this)">
                <img src="../icons/dashboard.svg" style="height:25px; margin-right: 10px;"> Dashboard
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="../features/manage_users.php" onclick="setActive(this)">
                <img src="../icons/usermanagement.svg" style="height:25px; margin-right: 10px;"> User Management
            </a>
        <?php else: ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="#" style="pointer-events: none; color: gray;">
                <img src="../icons/usermanagement.svg" style="height:25px; margin-right: 10px;"> User Management
            </a>
        <?php endif; ?>

        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="../features/category.php" onclick="setActive(this)">
            <img src="../icons/categories.svg" style="height:25px; margin-right: 10px;"> Categories
        </a>

        <!-- Products Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="productsCollapse" onclick="setActive(this, event)">
            <img src="../icons/cart.svg" style="height:25px; margin-right: 10px;"> Products
        </a>
        <div class="collapse" id="productsCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/manage_products.php" onclick="setActive(this, event)">Manage Products</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/add_product.php" onclick="setActive(this, event)">Add Products</a>
        </div>

        <!-- Sales Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="salesCollapse" onclick="setActive(this, event)">
            <img src="../icons/sales.svg" style="height:35px; margin-right: 10px;"> Sales
        </a>
        <div class="collapse" id="salesCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/manage_sales.php" onclick="setActive(this, event)">Manage Sales</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/add_sales.php" onclick="setActive(this, event)">Add Sales</a>
        </div>

        <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="#reportCollapse" data-bs-toggle="collapse" aria-expanded="false" aria-controls="reportCollapse" onclick="setActive(this, event)">
            <img src="../icons/salesreport.svg" style="height:25px; margin-right: 10px;">Sales Report</a>
        <div class="collapse" id="reportCollapse">
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/salesPerDay.php" onclick="setActive(this, event)">Sales by Date</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/salesPerMonth.php" onclick="setActive(this, event)">Sales by Month</a>
            <a class="sidebar-link fs-5 py-3 ps-4 mb-3" href="../features/salesDateRange.php" onclick="setActive(this, event)">Sales by Date Range</a>
        </div>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a class="sidebar-link fs-5 py-3 mb-3 d-flex align-items-center" href="../features-AI/forecasting.php" onclick="setActive(this)">Predictive Analytics</a>
        <?php endif; ?>
        <script src="../JS/sidebarCollapse.js"></script>
    </div>
</div>