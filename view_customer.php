<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

// Check if customer ID is provided
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customerId = $_GET['id'];

// Get customer data
$stmt = $conn->prepare("
    SELECT * FROM customers 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $customerId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: customers.php");
    exit();
}

$customer = $result->fetch_assoc();

// Get customer's transactions
$stmt = $conn->prepare("
    SELECT * FROM transactions 
    WHERE customer_id = ? AND user_id = ?
    ORDER BY date DESC
    LIMIT 10
");
$stmt->bind_param("ii", $customerId, $userId);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="flex h-screen">
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
                        <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Customers -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Business
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="customers.php" class="flex items-center px-3 py-2 text-sm font-medium text-white bg-white/10 rounded-lg">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            Customers
                        </a>
                        <a href="add_customer.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-user-plus w-5 h-5 mr-3"></i>
                            Add Customer
                        </a>
                        <a href="price_list.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-tags w-5 h-5 mr-3"></i>
                            Price List
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
                        <a href="logout.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Customer Details</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <a href="edit_customer.php?id=<?php echo $customerId; ?>" 
                               class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Customer
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Customer Details -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Customer Information -->
                    <div class="md:col-span-2">
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($customer['name']); ?></h2>
                                    <p class="text-white/60"><?php echo htmlspecialchars($customer['company']); ?></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Basic Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-medium text-white">Basic Information</h3>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Email</label>
                                        <p class="text-white"><?php echo htmlspecialchars($customer['email']); ?></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Phone</label>
                                        <p class="text-white"><?php echo htmlspecialchars($customer['phone']); ?></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Address</label>
                                        <p class="text-white"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                                    </div>
                                </div>

                                <!-- Business Information -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-medium text-white">Business Information</h3>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Tax ID</label>
                                        <p class="text-white"><?php echo htmlspecialchars($customer['tax_id']); ?></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Credit Limit</label>
                                        <p class="text-white">$<?php echo number_format($customer['credit_limit'], 2); ?></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Payment Terms</label>
                                        <p class="text-white"><?php echo ucfirst($customer['payment_terms']); ?></p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white/60">Status</label>
                                        <span class="px-3 py-1 rounded-full text-sm <?php echo $customer['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                            <?php echo ucfirst($customer['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <?php if (!empty($customer['notes'])): ?>
                            <div class="mt-6">
                                <h3 class="text-lg font-medium text-white mb-2">Notes</h3>
                                <div class="bg-white/5 rounded-lg p-4">
                                    <p class="text-white"><?php echo nl2br(htmlspecialchars($customer['notes'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="md:col-span-1">
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Recent Transactions</h3>
                            
                            <?php if ($transactions->num_rows > 0): ?>
                                <div class="space-y-4">
                                    <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                        <div class="bg-white/5 rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="text-white font-medium"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                                    <p class="text-white/60 text-sm"><?php echo date('M d, Y', strtotime($transaction['date'])); ?></p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-sm <?php echo $transaction['type'] === 'income' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                                    <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60">No recent transactions</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 