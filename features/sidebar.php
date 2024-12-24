<link href="../src/output.css" rel="stylesheet">

<!-- Alpine JS  -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div x-data="{ sidebarOpen: false }" class="relative h-screen">
    <!-- Mobile Toggle Button -->
    <button @click="sidebarOpen = !sidebarOpen"
        class="fixed top-4 left-4 z-50 p-2 bg-green-800 rounded-md text-white hover:bg-green-700 md:hidden focus:outline-none focus:ring-2 focus:ring-green-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Backdrop -->
    <div x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity md:hidden z-40">
    </div>

    <!-- Sidebar -->
    <div 
        :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}"
        class="fixed md:static top-0 left-0 bottom-0 w-[300px] bg-gradient-to-b from-gray-800 to-gray-900 text-white transform transition-transform duration-300 ease-in-out md:translate-x-0 h-full md:min-h-screen z-50 flex flex-col shadow-xl">
        
        <!-- Logo Header -->
        <div class="flex-shrink-0 flex justify-center items-center bg-green-800 h-[120px] border-b border-gray-700">
            <img class="w-[100px] h-[100px]" src="../icons/zefmaven.png" alt="Logo">
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">
            <div class="p-4 space-y-2">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>

                <!-- Dashboard Link -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <a class="flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'admin_dashboard.php') ? 'bg-green-800' : ''; ?>"
                        href="../dashboards/admin_dashboard.php">
                        <img src="../icons/dashboard.svg" class="h-5 w-5 mr-3">
                        <span>Dashboard</span>
                    </a>
                <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employee'): ?>
                    <a class="flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'employee_dashboard.php') ? 'bg-green-800' : ''; ?>"
                        href="../dashboards/employee_dashboard.php">
                        <img src="../icons/dashboard.svg" class="h-5 w-5 mr-3">
                        <span>Dashboard</span>
                    </a>
                <?php endif; ?>

                <!-- Rest of the menu items follow the same pattern -->
                <!-- User Management -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <a class="flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'manage_users.php') ? 'bg-green-800' : ''; ?>"
                        href="../features/manage_users.php">
                        <img src="../icons/usermanagement.svg" class="h-5 w-5 mr-3">
                        <span>User Management</span>
                    </a>
                <?php else: ?>
                    <a class="flex items-center py-3 px-4 rounded-lg text-lg cursor-not-allowed text-gray-500">
                        <img src="../icons/usermanagement.svg" class="h-5 w-5 mr-3 opacity-50">
                        <span>User Management</span>
                    </a>
                <?php endif; ?>

                <!-- Categories -->
                <a class="flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'category.php') ? 'bg-green-800' : ''; ?>"
                    href="../features/category.php">
                    <img src="../icons/categories.svg" class="h-5 w-5 mr-3">
                    <span>Categories</span>
                </a>

                <!-- Products Dropdown -->
                <div x-data="{ open: <?php echo ($current_page == 'manage_products.php' || $current_page == 'add_product.php') ? 'true' : 'false' ?> }"
                    class="relative">
                    <button @click="open = !open"
                        class="w-full flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'manage_products.php' || $current_page == 'add_product.php') ? 'bg-green-800' : ''; ?>">
                        <img src="../icons/cart.svg" class="h-5 w-5 mr-3">
                        <span>Products</span>
                        <svg class="w-4 h-4 ml-auto transform transition-transform duration-200"
                            :class="{'rotate-180': open}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="ml-8 mt-2 space-y-2">
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'manage_products.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/manage_products.php">Manage Products</a>
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'add_product.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/add_product.php">Add Products</a>
                    </div>
                </div>

                <!-- Apply same styling pattern to Sales and Sales Report dropdowns -->
                <div x-data="{ open: <?php echo ($current_page == 'manage_sales.php' || $current_page == 'add_sales.php') ? 'true' : 'false' ?> }"
                    class="relative">
                    <button @click="open = !open"
                        class="w-full flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'manage_sales.php' || $current_page == 'add_sales.php') ? 'bg-green-800' : ''; ?>">
                        <img src="../icons/sales.svg" class="h-5 w-5 mr-3">
                        <span>Sales</span>
                        <svg class="w-4 h-4 ml-auto transform transition-transform duration-200"
                            :class="{'rotate-180': open}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="ml-8 mt-2 space-y-2">
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'manage_sales.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/manage_sales.php">Manage Sales</a>
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'add_sales.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/add_sales.php">Add Sales</a>
                    </div>
                </div>

                <div x-data="{ open: <?php echo in_array($current_page, ['salesPerDay.php', 'salesPerMonth.php', 'salesDateRange.php', 'forecasting.php']) ? 'true' : 'false' ?> }"
                    class="relative">
                    <button @click="open = !open"
                        class="w-full flex items-center py-3 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo in_array($current_page, ['salesPerDay.php', 'salesPerMonth.php', 'salesDateRange.php', 'forecasting.php']) ? 'bg-green-800' : ''; ?>">
                        <img src="../icons/salesreport.svg" class="h-5 w-5 mr-3">
                        <span>Sales Report</span>
                        <svg class="w-4 h-4 ml-auto transform transition-transform duration-200"
                            :class="{'rotate-180': open}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="ml-8 mt-2 space-y-2">
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'salesPerDay.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/salesPerDay.php">Sales by Date</a>
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'salesPerMonth.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/salesPerMonth.php">Sales by Month</a>
                        <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'salesDateRange.php') ? 'bg-green-800' : ''; ?>"
                            href="../features/salesDateRange.php">Sales by Date Range</a>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <a class="block py-2 px-4 rounded-lg text-lg hover:bg-green-700 transition-all duration-200 <?php echo ($current_page == 'forecasting.php') ? 'bg-green-800' : ''; ?>"
                                href="../features-AI/forecasting.php">Predictive Analytics</a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="../JS/sidebarCollapse.js"></script>
</div>
</div>