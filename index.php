<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'connection.php';

// Debug connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Get current month income
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_income FROM income WHERE user_id = ? AND MONTH(income_date) = MONTH(CURRENT_DATE()) AND YEAR(income_date) = YEAR(CURRENT_DATE())");
$stmt->bind_param("i", $userId);
$stmt->execute();
$total_income = $stmt->get_result()->fetch_assoc()['total_income'];

// Get current month expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE user_id = ? AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
$stmt->bind_param("i", $userId);
$stmt->execute();
$total_expenses = $stmt->get_result()->fetch_assoc()['total_expenses'];

// Calculate total balance for the month
$total_balance = $total_income - $total_expenses;

// Get recent transactions (last 5 from both income and expenses, with category name, ordered by date)
$sql = "
    SELECT amount, description, 'income' as type, income_date as date, category as category_name, 'fa-arrow-trend-up' as category_icon FROM income
    UNION ALL
    SELECT e.amount, e.description, 'expense' as type, e.expense_date as date, c.name as category_name, COALESCE(c.icon, 'fa-tag') as category_icon FROM expenses e LEFT JOIN categories c ON e.category_id = c.id
    ORDER BY date DESC
    LIMIT 5
";
$result = $conn->query($sql);
$recentTransactions = $result;

// Get monthly summary
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expenses
    FROM transactions 
    WHERE user_id = ? 
    AND MONTH(date) = MONTH(CURRENT_DATE())
    AND YEAR(date) = YEAR(CURRENT_DATE())
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$monthlySummary = $stmt->get_result()->fetch_assoc();

// Calculate savings rate
$savingsRate = $monthlySummary['total_income'] > 0 
    ? round(($monthlySummary['total_income'] - $monthlySummary['total_expenses']) / $monthlySummary['total_income'] * 100) 
    : 0;

// Get category expenses
$stmt = $conn->prepare("
    SELECT c.name, c.icon, SUM(t.amount) as total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.type = 'expense'
    AND MONTH(t.date) = MONTH(CURRENT_DATE())
    AND YEAR(t.date) = YEAR(CURRENT_DATE())
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 5
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$categoryExpenses = $stmt->get_result();

// Get financial goals
$stmt = $conn->prepare("
    SELECT * FROM financial_goals 
    WHERE user_id = ? 
    ORDER BY progress DESC 
    LIMIT 3
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$financialGoals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 transition-all duration-300 min-h-screen">
        <!-- Header -->
        <header class="glass-effect border-b border-white/10 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-white">Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-6">
            <!-- Quick Actions -->
            <div class="my-12 max-w-7xl mx-auto">
                <div class="flex flex-wrap gap-4 justify-center sm:justify-start">
                    <a href="add_transaction.php" class="group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform duration-300"></i>
                        Add Transaction
                    </a>
                    <a href="reports.php" class="group bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 hover:bg-white/20">
                        <i class="fas fa-chart-bar mr-2 group-hover:bounce"></i>
                        View Reports
                    </a>
                    <a href="settings.php" class="group bg-white/10 backdrop-blur-sm border border-white/20 text-white px-6 py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 hover:bg-white/20">
                        <i class="fas fa-cog mr-2 group-hover:rotate-180 transition-transform duration-300"></i>
                        Settings
                    </a>
                </div>
            </div>

            <!-- Financial Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12 max-w-7xl mx-auto">
                <!-- Total Balance -->
                <div class="card-hover bg-gradient-to-br from-emerald-400 to-emerald-600 p-6 rounded-2xl shadow-xl text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-wallet text-2xl"></i>
                            </div>
                            <div class="text-2xl font-bold floating">💰</div>
                        </div>
                        <h3 class="text-white/80 text-sm font-medium mb-1">Total Balance</h3>
                        <p class="text-3xl font-bold">TSh<?php echo number_format($total_balance, 2); ?></p>
                    </div>
                </div>

                <!-- Monthly Income -->
                <div class="card-hover bg-gradient-to-br from-blue-400 to-blue-600 p-6 rounded-2xl shadow-xl text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-arrow-trend-up text-2xl"></i>
                            </div>
                            <div class="text-2xl font-bold floating">📈</div>
                        </div>
                        <h3 class="text-white/80 text-sm font-medium mb-1">Monthly Income</h3>
                        <p class="text-3xl font-bold">TSh<?php echo number_format($total_income, 2); ?></p>
                    </div>
                </div>

                <!-- Monthly Expenses -->
                <div class="card-hover bg-gradient-to-br from-purple-400 to-purple-600 p-6 rounded-2xl shadow-xl text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-arrow-trend-down text-2xl"></i>
                            </div>
                            <div class="text-2xl font-bold floating">📉</div>
                        </div>
                        <h3 class="text-white/80 text-sm font-medium mb-1">Monthly Expenses</h3>
                        <p class="text-3xl font-bold">TSh<?php echo number_format($total_expenses, 2); ?></p>
                    </div>
                </div>

                <!-- Savings Rate -->
                <div class="card-hover bg-gradient-to-br from-amber-400 to-amber-600 p-6 rounded-2xl shadow-xl text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-piggy-bank text-2xl"></i>
                            </div>
                            <div class="text-2xl font-bold floating">🏦</div>
                        </div>
                        <h3 class="text-white/80 text-sm font-medium mb-1">Savings Rate</h3>
                        <p class="text-3xl font-bold"><?php echo $savingsRate; ?>%</p>
                    </div>
                </div>
            </div>

            <!-- Charts and Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <!-- Recent Transactions -->
                <div class="lg:col-span-2 glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-history mr-3 text-blue-400"></i>
                            Recent Transactions
                        </h3>
                        <a href="transactions.php" class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors duration-200">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                        <div class="flex items-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-200 group">
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-<?php echo $transaction['type'] === 'income' ? 'green' : 'red'; ?>-400 to-<?php echo $transaction['type'] === 'income' ? 'green' : 'red'; ?>-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas <?php echo !empty($transaction['category_icon']) ? $transaction['category_icon'] : ($transaction['type'] === 'income' ? 'fa-arrow-trend-up' : 'fa-tag'); ?> text-white"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></p>
                                <p class="text-white/60 text-sm"><?php echo htmlspecialchars($transaction['category_name'] ?? ''); ?> • <?php echo date('M d, Y', strtotime($transaction['date'])); ?></p>
                            </div>
                            <div class="text-<?php echo $transaction['type'] === 'income' ? 'green' : 'red'; ?>-400 font-bold">
                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>TSh<?php echo number_format($transaction['amount'], 2); ?>
                                <span class="ml-2 text-xs text-white/60">(<?php echo ucfirst($transaction['type']); ?>)</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-chart-pie mr-3 text-purple-400"></i>
                            Categories
                        </h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php 
                        $colors = ['blue', 'green', 'purple', 'yellow', 'red'];
                        $i = 0;
                        while ($category = $categoryExpenses->fetch_assoc()): 
                        ?>
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-<?php echo $colors[$i % count($colors)]; ?>-500 rounded-full mr-3"></div>
                                <span class="text-white text-sm"><?php echo htmlspecialchars($category['name']); ?></span>
                            </div>
                            <span class="text-white font-bold">TSh<?php echo number_format($category['total'], 2); ?></span>
                        </div>
                        <?php 
                        $i++;
                        endwhile; 
                        ?>
                    </div>
                    
                    <!-- Mini Chart -->
                    <div class="mt-6 p-4 bg-white/5 rounded-xl">
                        <canvas id="categoryChart" width="200" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Goals Section -->
            <div class="mt-12 max-w-7xl mx-auto">
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-bullseye mr-3 text-amber-400"></i>
                            Financial Goals
                        </h3>
                        <a href="add_goal.php" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-plus mr-2"></i>
                            Add Goal
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($goal = $financialGoals->fetch_assoc()): 
                            $remaining = $goal['target_amount'] - $goal['current_amount'];
                            $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                            $progressColor = $progress >= 75 ? 'green' : ($progress >= 50 ? 'yellow' : 'blue');
                            
                            // Handle target date
                            $days_left = null;
                            if (!empty($goal['target_date'])) {
                                $current_date = new DateTime();
                                $target_date = new DateTime($goal['target_date']);
                                $days_left = $current_date->diff($target_date)->days;
                            }
                        ?>
                        <div class="bg-white/5 rounded-xl p-6 hover:bg-white/10 transition-all duration-200 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-<?php echo $progressColor; ?>-500/20 rounded-lg group-hover:scale-110 transition-transform duration-200">
                                        <i class="fas fa-bullseye text-<?php echo $progressColor; ?>-400"></i>
                                    </div>
                                    <h4 class="text-white font-medium"><?php echo htmlspecialchars($goal['name']); ?></h4>
                                </div>
                                <span class="text-<?php echo $progressColor; ?>-400 text-sm font-bold bg-<?php echo $progressColor; ?>-500/20 px-3 py-1 rounded-full">
                                    <?php echo number_format($progress, 0); ?>%
                                </span>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="w-full bg-white/10 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-<?php echo $progressColor; ?>-400 to-<?php echo $progressColor; ?>-500 h-2.5 rounded-full transition-all duration-300" 
                                         style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white/5 rounded-lg p-3">
                                        <p class="text-white/60 text-sm mb-1">Current</p>
                                        <p class="text-white font-bold">TSh<?php echo number_format($goal['current_amount'], 2); ?></p>
                                    </div>
                                    <div class="bg-white/5 rounded-lg p-3">
                                        <p class="text-white/60 text-sm mb-1">Target</p>
                                        <p class="text-white font-bold">TSh<?php echo number_format($goal['target_amount'], 2); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center text-white/60">
                                        <i class="fas fa-clock mr-2"></i>
                                        <span>
                                            <?php if ($days_left !== null): ?>
                                                <?php echo $days_left > 0 ? $days_left . " days left" : "Target date reached"; ?>
                                            <?php else: ?>
                                                No target date set
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="text-<?php echo $progressColor; ?>-400 font-medium">
                                        TSh<?php echo number_format($remaining, 2); ?> remaining
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize category chart
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php 
                    $categoryExpenses->data_seek(0);
                    $labels = [];
                    $data = [];
                    while ($category = $categoryExpenses->fetch_assoc()) {
                        $labels[] = $category['name'];
                        $data[] = $category['total'];
                    }
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#8B5CF6',
                        '#F59E0B',
                        '#EF4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });

        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
