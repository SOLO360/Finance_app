<!-- Sidebar -->
<div class="w-64 bg-slate-800/50 backdrop-blur-lg border-r border-white/10">
    <div class="p-4">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-lg flex items-center justify-center">
                <img src="images/logo.png" alt="FinanceTracker Logo" class="h-full w-full object-contain">
            </div>
            <span class="text-xl font-bold text-white">FinanceTracker</span>
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