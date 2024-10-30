<div class="bg-dark text-white vh-100 p-3" style="width: 300px;">
    <h2 class="text-center fs-2 py-4">Inventory System</h2>
    <div>
        <a class="sidebar-link fs-5 py-3" href="../dashboards/employee_dashboard.php" onclick="setActive(this)">Dashboard</a>
        <a class="sidebar-link fs-5 py-3" href="#" style="pointer-events: none; color: gray;">User Management</a>
        <a class="sidebar-link fs-5 py-3" href="../features/category.php" onclick="setActive(this)">Categories</a>
        <!-- Products Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3" href="#productsCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
            Products
        </a>
        <div class="collapse" id="productsCollapse">
            <a class="sidebar-link fs-5 py-3" href="../features/manage_products.php" onclick="setActive(this)">Manage Products</a>
            <a class="sidebar-link fs-5 py-3" href="../features/add_product.php" onclick="setActive(this)">Add Products</a>
        </div>
        <!-- Sales Collapse Dropdown -->
        <a class="sidebar-link fs-5 py-3" href="#salesCollapse" data-bs-toggle="collapse" aria-expanded="false" onclick="setActive(this)">
            Sales
        </a>
        <div class="collapse" id="salesCollapse">
            <a class="sidebar-link fs-5 py-3" href="../features/manage_sales.php" onclick="setActive(this)">Manage Sales</a>
            <a class="sidebar-link fs-5 py-3" href="../features/add_sales.php" onclick="setActive(this)">Add Sales</a>
        </div>
        <a class="sidebar-link fs-5 py-3" href="#" onclick="setActive(this)">Sales Report</a>
    </div>
</div>

