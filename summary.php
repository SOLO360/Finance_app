<?php
session_start();
// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Get user info
try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    $error_message = "Error fetching user info: " . $e->getMessage();
}

// Initialize variables
$total_income = 0;
$total_expenses = 0;
$net_profit = 0;
$start_date = '';
$end_date = '';
$income_data = [];
$expense_data = [];
$category_expenses = [];
$monthly_data = [];

// Set default date range to current month if no dates are set
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
} else {
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');
}

try {
    // Calculate total income
    $sql_income = "SELECT SUM(amount) as total_income FROM income WHERE income_date BETWEEN ? AND ? AND user_id = ?";
    $stmt = $conn->prepare($sql_income);
    $stmt->bind_param("ssi", $start_date, $end_date, $userId);
    $stmt->execute();
    $result_income = $stmt->get_result();
    if ($result_income->num_rows > 0) {
        $row_income = $result_income->fetch_assoc();
        $total_income = $row_income['total_income'] ? $row_income['total_income'] : 0;
    }
    
    // Calculate total expenses
    $sql_expenses = "SELECT SUM(amount) as total_expenses FROM expenses WHERE expense_date BETWEEN ? AND ? AND user_id = ?";
    $stmt = $conn->prepare($sql_expenses);
    $stmt->bind_param("ssi", $start_date, $end_date, $userId);
    $stmt->execute();
    $result_expenses = $stmt->get_result();
    if ($result_expenses->num_rows > 0) {
        $row_expenses = $result_expenses->fetch_assoc();
        $total_expenses = $row_expenses['total_expenses'] ? $row_expenses['total_expenses'] : 0;
    }
    
    // Calculate net profit
    $net_profit = $total_income - $total_expenses;
    
    // Get income data for chart
    $sql_income_chart = "SELECT DATE(income_date) as date, SUM(amount) as daily_income 
                        FROM income 
                        WHERE income_date BETWEEN ? AND ? AND user_id = ? 
                        GROUP BY DATE(income_date) 
                        ORDER BY date";
    $stmt = $conn->prepare($sql_income_chart);
    $stmt->bind_param("ssi", $start_date, $end_date, $userId);
    $stmt->execute();
    $result_income_chart = $stmt->get_result();
    while ($row = $result_income_chart->fetch_assoc()) {
        $income_data[] = $row;
    }
    
    // Get expense data for chart
    $sql_expense_chart = "SELECT DATE(expense_date) as date, SUM(amount) as daily_expense 
                         FROM expenses 
                         WHERE expense_date BETWEEN ? AND ? AND user_id = ? 
                         GROUP BY DATE(expense_date) 
                         ORDER BY date";
    $stmt = $conn->prepare($sql_expense_chart);
    $stmt->bind_param("ssi", $start_date, $end_date, $userId);
    $stmt->execute();
    $result_expense_chart = $stmt->get_result();
    while ($row = $result_expense_chart->fetch_assoc()) {
        $expense_data[] = $row;
    }
    
    // Get expenses by category
    $sql_category = "SELECT ec.name as category_name, SUM(e.amount) as category_total 
                    FROM expenses e 
                    LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                    WHERE e.expense_date BETWEEN ? AND ? AND e.user_id = ? 
                    GROUP BY ec.id, ec.name 
                    ORDER BY category_total DESC";
    $stmt = $conn->prepare($sql_category);
    $stmt->bind_param("ssi", $start_date, $end_date, $userId);
    $stmt->execute();
    $result_category = $stmt->get_result();
    while ($row = $result_category->fetch_assoc()) {
        $category_expenses[] = $row;
    }
    
    // Get monthly data for the last 6 months
    $sql_monthly = "SELECT 
                        month,
                        SUM(income) as income,
                        SUM(expense) as expense
                    FROM (
                        SELECT 
                            DATE_FORMAT(income_date, '%Y-%m') as month,
                            SUM(amount) as income,
                            0 as expense
                        FROM income 
                        WHERE user_id = ? AND income_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(income_date, '%Y-%m')
                        
                        UNION ALL
                        
                        SELECT 
                            DATE_FORMAT(expense_date, '%Y-%m') as month,
                            0 as income,
                            SUM(amount) as expense
                        FROM expenses 
                        WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
                    ) combined
                    GROUP BY month
                    ORDER BY month DESC";
    $stmt = $conn->prepare($sql_monthly);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result_monthly = $stmt->get_result();
    while ($row = $result_monthly->fetch_assoc()) {
        $monthly_data[] = $row;
    }
    
} catch (Exception $e) {
    $error_message = "Error calculating summary: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Summary - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Financial Summary</h1>
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

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Date Range Filter -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                    <h2 class="text-lg font-medium text-white mb-4">Select Date Range</h2>
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-white/60 mb-2">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>"
                                   class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-white/60 mb-2">End Date</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>"
                                   class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
                        <div>
                            <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-chart-line mr-2"></i>
                                Generate Summary
                            </button>
            </div>
        </form>
                </div>

                <!-- Summary Period -->
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-white mb-2">
                        Summary from <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </h3>
                    <p class="text-white/60">Financial overview for the selected period</p>
                </div>

                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Income -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/60 text-sm font-medium">Total Income</p>
                                <p class="text-3xl font-bold text-green-400">TSh <?php echo number_format($total_income, 2); ?></p>
                                <p class="text-white/40 text-xs mt-1">Period total</p>
                            </div>
                            <div class="h-12 w-12 bg-green-500/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-arrow-up text-green-400 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Expenses -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/60 text-sm font-medium">Total Expenses</p>
                                <p class="text-3xl font-bold text-red-400">TSh <?php echo number_format($total_expenses, 2); ?></p>
                                <p class="text-white/40 text-xs mt-1">Period total</p>
                            </div>
                            <div class="h-12 w-12 bg-red-500/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-arrow-down text-red-400 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Net Profit/Loss -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/60 text-sm font-medium">Net <?php echo $net_profit >= 0 ? 'Profit' : 'Loss'; ?></p>
                                <p class="text-3xl font-bold <?php echo $net_profit >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                                    TSh <?php echo number_format(abs($net_profit), 2); ?>
                                </p>
                                <p class="text-white/40 text-xs mt-1">
                                    <?php echo $net_profit >= 0 ? 'Positive balance' : 'Negative balance'; ?>
                                </p>
                            </div>
                            <div class="h-12 w-12 <?php echo $net_profit >= 0 ? 'bg-green-500/20' : 'bg-red-500/20'; ?> rounded-full flex items-center justify-center">
                                <i class="fas <?php echo $net_profit >= 0 ? 'fa-trending-up text-green-400' : 'fa-trending-down text-red-400'; ?> text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Income vs Expenses Chart -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl">
                        <h3 class="text-lg font-medium text-white mb-4">Income vs Expenses Trend</h3>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="incomeExpenseChart"></canvas>
                        </div>
                        <?php if (empty($income_data) && empty($expense_data)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-chart-line text-white/40 text-4xl mb-4"></i>
                                <p class="text-white/60">No data available for the selected period</p>
                                <p class="text-white/40 text-sm mt-2">Add some income or expenses to see the trend</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl">
                        <h3 class="text-lg font-medium text-white mb-4">Expenses by Category</h3>
                        <?php if (!empty($category_expenses)): ?>
                            <div class="space-y-3">
                                <?php foreach ($category_expenses as $category): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-3 w-3 bg-blue-400 rounded-full"></div>
                                            <span class="text-white"><?php echo htmlspecialchars($category['category_name'] ?? 'Uncategorized'); ?></span>
                                        </div>
                                        <span class="text-white font-medium">TSh <?php echo number_format($category['category_total'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-white/60 text-center py-8">No expense categories found for this period.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Overview -->
                <?php if (!empty($monthly_data)): ?>
                <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                    <h3 class="text-lg font-medium text-white mb-4">6-Month Overview</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                <thead>
                                <tr class="text-left text-white/60 text-sm">
                                    <th class="pb-4">Month</th>
                                    <th class="pb-4">Income</th>
                                    <th class="pb-4">Expenses</th>
                                    <th class="pb-4">Net</th>
                                    <th class="pb-4">Status</th>
                    </tr>
                </thead>
                            <tbody class="text-white">
                                <?php foreach ($monthly_data as $month): ?>
                            <?php 
                                    $month_net = $month['income'] - $month['expense'];
                                    $month_name = date('M Y', strtotime($month['month'] . '-01'));
                                    ?>
                                    <tr class="border-t border-white/10 hover:bg-white/5 transition-colors duration-200">
                                        <td class="py-4 font-medium"><?php echo $month_name; ?></td>
                                        <td class="py-4 text-green-400">TSh <?php echo number_format($month['income'], 2); ?></td>
                                        <td class="py-4 text-red-400">TSh <?php echo number_format($month['expense'], 2); ?></td>
                                        <td class="py-4 font-bold <?php echo $month_net >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                                            TSh <?php echo number_format(abs($month_net), 2); ?>
                                        </td>
                                        <td class="py-4">
                                            <span class="px-3 py-1 rounded-full text-sm <?php echo $month_net >= 0 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                                <?php echo $month_net >= 0 ? 'Profit' : 'Loss'; ?>
                                            </span>
                        </td>
                    </tr>
                                <?php endforeach; ?>
                </tbody>
            </table>
                    </div>
                </div>
        <?php endif; ?>

                <!-- Quick Actions -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <h3 class="text-lg font-medium text-white mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="income_form.php" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add Income
                        </a>
                        <a href="expenses_form.php" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add Expense
                        </a>
                        <a href="view_expenses.php" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                            <i class="fas fa-list mr-2"></i>
                            View All Records
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Income vs Expenses Chart
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('incomeExpenseChart');
            if (!canvas) return;
            
            // Destroy existing chart if it exists
            if (window.incomeExpenseChart) {
                window.incomeExpenseChart.destroy();
            }
            
            const incomeData = <?php echo json_encode($income_data); ?>;
            const expenseData = <?php echo json_encode($expense_data); ?>;
            
            // Only proceed if we have data
            if (incomeData.length === 0 && expenseData.length === 0) {
                return; // Let the PHP message show instead
            }
            
            // Create date labels
            const allDates = [...new Set([
                ...incomeData.map(item => item.date),
                ...expenseData.map(item => item.date)
            ])].sort();
            
            const incomeValues = allDates.map(date => {
                const item = incomeData.find(d => d.date === date);
                return item ? parseFloat(item.daily_income) : 0;
            });
            
            const expenseValues = allDates.map(date => {
                const item = expenseData.find(d => d.date === date);
                return item ? parseFloat(item.daily_expense) : 0;
            });
            
            // Create chart
            try {
                window.incomeExpenseChart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: allDates.map(date => new Date(date).toLocaleDateString()),
                        datasets: [{
                            label: 'Income',
                            data: incomeValues,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Expenses',
                            data: expenseValues,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 800
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#ffffff'
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#ffffff',
                                    maxTicksLimit: 8
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            y: {
                                ticks: {
                                    color: '#ffffff',
                                    callback: function(value) {
                                        return 'TSh ' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Chart creation error:', error);
            }
        });
    </script>
</body>
</html>
