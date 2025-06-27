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

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get all customers with their purchase history
$sql = "SELECT customers.*, 
        COUNT(DISTINCT i.id) as total_transactions,
        MAX(i.income_date) as last_purchase_date,
        COALESCE(SUM(i.amount), 0) as total_purchases,
        COALESCE(loyalty_points, 0) as loyalty_points
        FROM customers
        LEFT JOIN income i ON customers.id = i.customer_id
        GROUP BY customers.id
        ORDER BY total_purchases DESC";
$customers = $conn->query($sql);

// Calculate loyalty tiers
function getLoyaltyTier($total_purchases) {
    if ($total_purchases >= 10000) return ['name' => 'Diamond', 'color' => 'purple'];
    if ($total_purchases >= 5000) return ['name' => 'Gold', 'color' => 'yellow'];
    if ($total_purchases >= 1000) return ['name' => 'Silver', 'color' => 'gray'];
    return ['name' => 'Bronze', 'color' => 'amber'];
}

function formatCurrency($amount) {
    return 'TSh ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Finance Tracker</title>
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Customer Management</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <a href="add_customer.php" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Add Customer
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Customer Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Total Customers</h3>
                                <p class="text-3xl font-bold text-white"><?php echo $customers->num_rows; ?></p>
                            </div>
                            <div class="p-3 bg-blue-500/20 rounded-xl">
                                <i class="fas fa-users text-2xl text-blue-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Total Revenue</h3>
                                <?php
                                $total_revenue = 0;
                                $customers->data_seek(0);
                                while ($customer = $customers->fetch_assoc()) {
                                    $total_revenue += $customer['total_purchases'];
                                }
                                ?>
                                <p class="text-3xl font-bold text-white"><?php echo formatCurrency($total_revenue); ?></p>
                            </div>
                            <div class="p-3 bg-green-500/20 rounded-xl">
                                <i class="fas fa-dollar-sign text-2xl text-green-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Average Purchase</h3>
                                <?php
                                $avg_purchase = $customers->num_rows > 0 ? $total_revenue / $customers->num_rows : 0;
                                ?>
                                <p class="text-3xl font-bold text-white"><?php echo formatCurrency($avg_purchase); ?></p>
                            </div>
                            <div class="p-3 bg-purple-500/20 rounded-xl">
                                <i class="fas fa-chart-line text-2xl text-purple-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customers List -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Loyalty Tier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Total Purchases</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Points</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Last Purchase</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php 
                                $customers->data_seek(0);
                                while ($customer = $customers->fetch_assoc()): 
                                    $tier = getLoyaltyTier($customer['total_purchases']);
                                ?>
                                    <tr class="hover:bg-white/5 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="text-white font-medium"><?php echo htmlspecialchars($customer['name']); ?></div>
                                                    <?php if ($customer['email']): ?>
                                                        <div class="text-white/60 text-sm"><?php echo htmlspecialchars($customer['email']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $tier['color']; ?>-500/20 text-<?php echo $tier['color']; ?>-400">
                                                <?php echo $tier['name']; ?> Tier
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-white">
                                            <?php echo formatCurrency($customer['total_purchases']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-white">
                                            <?php echo number_format((int)$customer['loyalty_points']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-white/60">
                                            <?php echo $customer['last_purchase_date'] ? date('M d, Y', strtotime($customer['last_purchase_date'])) : 'Never'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <a href="view_customer.php?id=<?php echo $customer['id']; ?>" 
                                                   class="text-blue-400 hover:text-blue-300">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_customer.php?id=<?php echo $customer['id']; ?>" 
                                                   class="text-yellow-400 hover:text-yellow-300">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_customer.php?id=<?php echo $customer['id']; ?>" 
                                                   class="text-red-400 hover:text-red-300"
                                                   onclick="return confirm('Are you sure you want to delete this customer?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 