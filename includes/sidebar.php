<!-- Sidebar Toggle Button (Mobile) -->
<button id="sidebarToggle" class="fixed top-20 left-4 z-50 p-2 bg-slate-800/50 backdrop-blur-lg border border-white/10 rounded-lg text-white hover:bg-slate-700/50 transition-all duration-300 lg:hidden">
    <i class="fas fa-bars text-lg"></i>
</button>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-full bg-slate-800/50 backdrop-blur-lg border-r border-white/10 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 sidebar-transition sidebar-mobile lg:sidebar-desktop" style="transform: translateX(-100%);">
    <!-- Close button for mobile -->
    <button id="sidebarClose" class="absolute top-4 right-4 p-2 text-white hover:bg-white/10 rounded-lg transition-colors duration-200 lg:hidden">
        <i class="fas fa-times text-lg"></i>
    </button>
    
    <div class="p-4 pt-20 lg:pt-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center">
                    <img src="img/smart signage logo.png" alt="FinanceTracker Logo" class="h-full w-full object-contain">
                </div>
                <span class="text-xl font-bold text-white">FinanceTracker</span>
            </div>
            <!-- Desktop toggle button -->
            <button id="sidebarToggleDesktop" class="hidden lg:block p-2 text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                <i class="fas fa-chevron-left text-lg chevron-rotate"></i>
            </button>
        </div>
    </div>
    
    <nav class="mt-8 px-4">
        <!-- Dashboard -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Main
            </h3>
            <div class="mt-2 space-y-1">
                <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium text-white bg-white/10 rounded-lg">
                    <i class="fas fa-home w-5 h-5 mr-3"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <!-- Income -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Income
            </h3>
            <div class="mt-2 space-y-1">
                <a href="income_form.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus w-5 h-5 mr-3"></i>
                    Add Income
                </a>
                <a href="view_income.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-list w-5 h-5 mr-3"></i>
                    View Income
                </a>
            </div>
        </div>

        <!-- Customers -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Customers
            </h3>
            <div class="mt-2 space-y-1">
                <a href="customers.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-users w-5 h-5 mr-3"></i>
                    All Customers
                </a>
                <a href="customer_loyalty.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-star w-5 h-5 mr-3"></i>
                    Loyalty Program
                </a>
                <a href="customer_reports.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-chart-line w-5 h-5 mr-3"></i>
                    Customer Reports
                </a>
            </div>
        </div>

        <!-- Expenses -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Expenses
            </h3>
            <div class="mt-2 space-y-1">
                <a href="expenses_form.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus w-5 h-5 mr-3"></i>
                    Add Expense
                </a>
                <a href="view_expenses.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-list w-5 h-5 mr-3"></i>
                    View Expenses
                </a>
            </div>
        </div>

        <!-- Reports & Analysis -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Reports & Analysis
            </h3>
            <div class="mt-2 space-y-1">
                <a href="summary.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-chart-pie w-5 h-5 mr-3"></i>
                    Summary
                </a>
                <a href="reports.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-chart-bar w-5 h-5 mr-3"></i>
                    Reports
                </a>
                <a href="price_list.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-tags w-5 h-5 mr-3"></i>
                    Price List
                </a>
            </div>
        </div>

        <!-- Settings -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                Settings
            </h3>
            <div class="mt-2 space-y-1">
                <a href="settings.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-cog w-5 h-5 mr-3"></i>
                    Settings
                </a>
                <a href="add_user.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-user-plus w-5 h-5 mr-3"></i>
                    Add User
                </a>
                <a href="logout.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                    <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>
</div>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden hidden"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.lg\\:ml-64'); // Main content area
    const chevronIcon = document.querySelector('#sidebarToggleDesktop i');
    
    // Check if sidebar state is stored in localStorage
    const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
    
    // Initialize sidebar state
    if (sidebarOpen) {
        sidebar.style.transform = 'translateX(0)';
        sidebarOverlay.classList.remove('hidden');
        if (chevronIcon) {
            chevronIcon.classList.remove('fa-chevron-right');
            chevronIcon.classList.add('fa-chevron-left');
            chevronIcon.classList.remove('rotated');
        }
    } else {
        if (chevronIcon) {
            chevronIcon.classList.remove('fa-chevron-left');
            chevronIcon.classList.add('fa-chevron-right');
            chevronIcon.classList.add('rotated');
        }
    }
    
    // Toggle sidebar
    function toggleSidebar() {
        const isOpen = sidebar.style.transform === 'translateX(0px)' || sidebar.style.transform === '';
        
        if (isOpen) {
            sidebar.style.transform = 'translateX(-100%)';
            sidebarOverlay.classList.add('hidden');
            localStorage.setItem('sidebarOpen', 'false');
            
            // Update chevron icon with rotation
            if (chevronIcon) {
                chevronIcon.classList.remove('fa-chevron-left');
                chevronIcon.classList.add('fa-chevron-right');
                chevronIcon.classList.add('rotated');
            }
            
            // On desktop, also adjust main content margin
            if (window.innerWidth >= 1024) {
                mainContent.classList.remove('lg:ml-64');
                mainContent.classList.add('lg:ml-0');
            }
        } else {
            sidebar.style.transform = 'translateX(0)';
            sidebarOverlay.classList.remove('hidden');
            localStorage.setItem('sidebarOpen', 'true');
            
            // Update chevron icon with rotation
            if (chevronIcon) {
                chevronIcon.classList.remove('fa-chevron-right');
                chevronIcon.classList.add('fa-chevron-left');
                chevronIcon.classList.remove('rotated');
            }
            
            // On desktop, also adjust main content margin
            if (window.innerWidth >= 1024) {
                mainContent.classList.remove('lg:ml-0');
                mainContent.classList.add('lg:ml-64');
            }
        }
    }
    
    // Event listeners
    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarToggleDesktop.addEventListener('click', toggleSidebar);
    sidebarClose.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar);
    
    // Close sidebar on window resize (if going from mobile to desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) { // lg breakpoint
            sidebar.style.transform = 'translateX(0)';
            sidebarOverlay.classList.add('hidden');
            
            // Check localStorage for sidebar state on desktop
            const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
            if (sidebarOpen) {
                mainContent.classList.remove('lg:ml-0');
                mainContent.classList.add('lg:ml-64');
                if (chevronIcon) {
                    chevronIcon.classList.remove('fa-chevron-right');
                    chevronIcon.classList.add('fa-chevron-left');
                    chevronIcon.classList.remove('rotated');
                }
            } else {
                mainContent.classList.remove('lg:ml-64');
                mainContent.classList.add('lg:ml-0');
                if (chevronIcon) {
                    chevronIcon.classList.remove('fa-chevron-left');
                    chevronIcon.classList.add('fa-chevron-right');
                    chevronIcon.classList.add('rotated');
                }
            }
        } else {
            // On mobile, check localStorage for state
            const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
            if (!sidebarOpen) {
                sidebar.style.transform = 'translateX(-100%)';
                sidebarOverlay.classList.add('hidden');
            }
        }
    });
    
    // Initialize main content margin based on sidebar state on desktop
    if (window.innerWidth >= 1024) {
        const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
        if (!sidebarOpen) {
            mainContent.classList.remove('lg:ml-64');
            mainContent.classList.add('lg:ml-0');
        }
    }
});
</script> 