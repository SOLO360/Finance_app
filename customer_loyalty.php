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
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get loyalty tiers
$tiers = [
    'Bronze' => ['min' => 0, 'max' => 999, 'color' => 'amber', 'benefits' => ['Basic customer support', 'Standard shipping']],
    'Silver' => ['min' => 1000, 'max' => 4999, 'color' => 'gray', 'benefits' => ['Priority support', 'Free shipping', '5% discount']],
    'Gold' => ['min' => 5000, 'max' => 9999, 'color' => 'yellow', 'benefits' => ['VIP support', 'Free express shipping', '10% discount', 'Early access to sales']],
    'Diamond' => ['min' => 10000, 'max' => PHP_FLOAT_MAX, 'color' => 'purple', 'benefits' => ['24/7 concierge', 'Free shipping worldwide', '15% discount', 'Exclusive events', 'Personal shopping assistant']]
];

// Get customers grouped by tier
$customers_by_tier = [];
foreach ($tiers as $tier_name => $tier_info) {
    $sql = "SELECT c.*, COUNT(DISTINCT i.id) as total_transactions
            FROM customers c
            LEFT JOIN income i ON c.id = i.customer_id
            WHERE c.total_purchases >= ? AND c.total_purchases < ?
            GROUP BY c.id
            ORDER BY c.total_purchases DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dd", $tier_info['min'], $tier_info['max']);
    $stmt->execute();
    $customers_by_tier[$tier_name] = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program - Finance Tracker</title>
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Loyalty Program</h1>
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
                <!-- Loyalty Tiers Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($tiers as $tier_name => $tier_info): ?>
                        <div class="glass-effect rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-<?php echo $tier_info['color']; ?>-400">
                                    <?php echo $tier_name; ?> Tier
                                </h3>
                                <div class="p-2 bg-<?php echo $tier_info['color']; ?>-500/20 rounded-lg">
                                    <i class="fas fa-crown text-<?php echo $tier_info['color']; ?>-400"></i>
                                </div>
                            </div>
                            <div class="text-white/60 text-sm mb-4">
                                $<?php echo number_format($tier_info['min']); ?> - 
                                <?php echo $tier_info['max'] == PHP_FLOAT_MAX ? '∞' : '$' . number_format($tier_info['max']); ?>
                            </div>
                            <ul class="space-y-2">
                                <?php foreach ($tier_info['benefits'] as $benefit): ?>
                                    <li class="flex items-center text-white/80 text-sm">
                                        <i class="fas fa-check text-<?php echo $tier_info['color']; ?>-400 mr-2"></i>
                                        <?php echo $benefit; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Customers by Tier -->
                <?php foreach ($tiers as $tier_name => $tier_info): ?>
                    <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-white">
                                <?php echo $tier_name; ?> Members
                                <span class="text-white/60 text-sm ml-2">
                                    (<?php echo $customers_by_tier[$tier_name]->num_rows; ?> customers)
                                </span>
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-white/10">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Total Purchases</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Points</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Transactions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    <?php while ($customer = $customers_by_tier[$tier_name]->fetch_assoc()): ?>
                                        <tr class="hover:bg-white/5 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-<?php echo $tier_info['color']; ?>-500 to-<?php echo $tier_info['color']; ?>-600 rounded-full flex items-center justify-center">
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
                                            <td class="px-6 py-4 whitespace-nowrap text-white">
                                                $<?php echo number_format($customer['total_purchases'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-white">
                                                <?php echo number_format($customer['loyalty_points']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-white">
                                                <?php echo $customer['total_transactions']; ?>
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
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html> 