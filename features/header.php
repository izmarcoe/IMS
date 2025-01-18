<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>


<header class="flex flex-row sticky z-[55]">
    <div class="flex items-center text-black p-3 flex-grow bg-gray-600 z-[999]">
        <div class="ml-6 flex flex-start text-white">
            <h2 class="text-[1.5rem] font-bold capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?> Dashboard</h2>
        </div>
        <div class="hidden lg:flex justify-end flex-grow text-white"> <!-- Added hidden md:flex -->
            <span class="px-4 font-bold text-[1rem]" id="datetime"><?php echo date('F j, Y, g:i A'); ?></span>
        </div>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="relative mr-4 z-[999]">
                <button id="notificationBtn" class="relative p-2 text-white hover:text-gray-200 z-[999]">
                    <i class="fas fa-bell"></i>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM product_modification_requests WHERE status = 'pending'");
                    $stmt->execute();
                    $pendingCount = $stmt->fetchColumn();
                    if ($pendingCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full px-2 py-1 text-xs">
                            <?php echo $pendingCount; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-[99999]">
                    <div id="notificationContent" class="max-h-96 overflow-y-auto z-[99999]">
                        <!-- Content loaded via JavaScript -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
                <span><?php echo htmlspecialchars($fname); ?></span>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../JS/notifications.js"></script>
</header>