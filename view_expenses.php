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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$error_message = null;
$success_message = null;

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get user info
try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    $error_message = "Error fetching user info: " . $e->getMessage();
}

// Handle date filtering
$filter = "";
$start_date = "";
$end_date = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    if (!empty($start_date) && !empty($end_date)) {
        $filter = "WHERE e.expense_date BETWEEN ? AND ? AND e.user_id = ?";
    } else {
        $filter = "WHERE e.user_id = ?";
    }
} else {
    $filter = "WHERE e.user_id = ?";
}

// Get total count for pagination
try {
    if (!empty($start_date) && !empty($end_date)) {
        $count_sql = "SELECT COUNT(*) as total FROM expenses e $filter";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("ssi", $start_date, $end_date, $userId);
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM expenses e $filter";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $userId);
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
} catch (Exception $e) {
    $error_message = "Error counting expenses: " . $e->getMessage();
    $total_records = 0;
    $total_pages = 0;
}

// Fetch expense records with category and payment method names
try {
    if (!empty($start_date) && !empty($end_date)) {
        $sql = "SELECT e.*, ec.name as category_name, pm.name as payment_method_name 
                FROM expenses e 
                LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                LEFT JOIN payment_methods pm ON e.payment_method_id = pm.id 
                $filter 
                ORDER BY e.expense_date DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $start_date, $end_date, $userId, $records_per_page, $offset);
    } else {
        $sql = "SELECT e.*, ec.name as category_name, pm.name as payment_method_name 
                FROM expenses e 
                LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                LEFT JOIN payment_methods pm ON e.payment_method_id = pm.id 
                $filter 
                ORDER BY e.expense_date DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $userId, $records_per_page, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Exception $e) {
    $error_message = "Error fetching expenses: " . $e->getMessage();
    $result = null;
}

// Display success message if set
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Display error message if set
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Build pagination URL parameters
$url_params = [];
if (!empty($start_date)) $url_params['start_date'] = $start_date;
if (!empty($end_date)) $url_params['end_date'] = $end_date;
$url_params_str = !empty($url_params) ? '&' . http_build_query($url_params) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            <h1 class="text-xl font-bold text-white">View Expenses</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <a href="expenses_form.php" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Add Expense
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-400">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                    <h2 class="text-lg font-medium text-white mb-4">Filter Expenses</h2>
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
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-filter mr-2"></i>
                                Filter
                            </button>
                            <a href="view_expenses.php" class="bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-times mr-2"></i>
                                Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Expenses Table -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <!-- Pagination Info -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="text-white/60 text-sm">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> expenses
                        </div>
                        <div class="text-white/60 text-sm">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-white/60 text-sm">
                                    <th class="pb-4">Date</th>
                                    <th class="pb-4">Description</th>
                                    <th class="pb-4">Category</th>
                                    <th class="pb-4">Payment Method</th>
                                    <th class="pb-4">Amount</th>
                                    <th class="pb-4">Receipt</th>
                                    <th class="pb-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-white">
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    $total_amount = 0;
                                    while($row = $result->fetch_assoc()) {
                                        $total_amount += $row['amount'];
                                        echo "<tr class='border-t border-white/10 hover:bg-white/5 transition-colors duration-200'>
                                                <td class='py-4'>". date('M d, Y', strtotime($row['expense_date'])) ."</td>
                                                <td class='py-4 font-medium'>". htmlspecialchars($row['description']) ."</td>
                                                <td class='py-4'>
                                                    <span class='px-3 py-1 rounded-full text-sm bg-blue-500/20 text-blue-400'>
                                                        ". htmlspecialchars($row['category_name'] ?? 'N/A') ."
                                                    </span>
                                                </td>
                                                <td class='py-4'>
                                                    <span class='px-3 py-1 rounded-full text-sm bg-purple-500/20 text-purple-400'>
                                                        ". htmlspecialchars($row['payment_method_name'] ?? 'N/A') ."
                                                    </span>
                                                </td>
                                                <td class='py-4 font-bold text-red-400'>TSh ". number_format($row['amount'], 2) ."</td>
                                                <td class='py-4 text-white/70'>". htmlspecialchars($row['receipt_number'] ?? 'N/A') ."</td>
                                                <td class='py-4'>
                                                    <div class='flex items-center space-x-3'>
                                                        <a href='edit_expense.php?id=". htmlspecialchars($row['id']) ."' class='text-blue-400 hover:text-blue-300'>
                                                            <i class='fas fa-edit'></i>
                                                        </a>
                                                        <form method='POST' action='delete_expense.php' class='inline-block'>
                                                            <input type='hidden' name='expense_id' value='". htmlspecialchars($row['id']) ."'>
                                                            <button type='submit' class='text-red-400 hover:text-red-300' onclick=\"return confirm('Are you sure you want to delete this expense?');\">
                                                                <i class='fas fa-trash'></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>";
                                    }
                                    echo "<tr class='border-t border-white/20 bg-white/5'>
                                            <td colspan='4' class='py-4 font-bold text-white'>Page Total</td>
                                            <td class='py-4 font-bold text-red-400'>TSh ". number_format($total_amount, 2) ."</td>
                                            <td colspan='2'></td>
                                          </tr>";
                                } else {
                                    echo "<tr><td colspan='7' class='py-4 text-center text-white/60'>No expenses found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center items-center space-x-2 mt-6">
                        <!-- First Page -->
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo $url_params_str; ?>" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $url_params_str; ?>" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?><?php echo $url_params_str; ?>" 
                               class="px-3 py-2 rounded-lg transition-colors duration-200 <?php echo $i == $page ? 'bg-green-500 text-white' : 'bg-white/10 hover:bg-white/20 text-white'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $url_params_str; ?>" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Last Page -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $url_params_str; ?>" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default date range to current month if no dates are set
        if (!document.getElementById('start_date').value && !document.getElementById('end_date').value) {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];
        }
    </script>
</body>
</html>