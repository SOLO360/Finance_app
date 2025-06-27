<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering to catch any errors
ob_start();

try {
    session_start();
    // Prevent back navigation after logout
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    require_once 'connection.php';
    require_once 'includes/loyalty_functions.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['user_id'];

    // Debug connection
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get user info
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found");
    }

    // Pagination settings
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    // Optional: Filtering by date range
    $filter = "";
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        if (!empty($start_date) && !empty($end_date)) {
            $filter = "WHERE income_date BETWEEN '$start_date' AND '$end_date'";
        }
    }

    // Get total records for pagination
    $total_records_sql = "SELECT COUNT(*) as count FROM income $filter";
    $total_records_result = $conn->query($total_records_sql);
    $total_records = $total_records_result->fetch_assoc()['count'];
    $total_pages = ceil($total_records / $records_per_page);

    // Get income records with pagination
    $sql = "SELECT i.*, c.name as customer_name, c.email as customer_email 
            FROM income i 
            LEFT JOIN customers c ON i.customer_id = c.id 
            ORDER BY i.income_date DESC
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $records_per_page, $offset);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $income_records = $stmt->get_result();
    if (!$income_records) {
        throw new Exception("Query failed: " . $stmt->error);
    }

    // Calculate total income for all records
    $total_income_sql = "SELECT SUM(amount) as total FROM income";
    $total_income_result = $conn->query($total_income_sql);
    $total_income = $total_income_result->fetch_assoc()['total'] ?? 0;

    // Calculate average income
    $avg_income = $total_records > 0 ? $total_income / $total_records : 0;

} catch (Exception $e) {
    // Log the error
    error_log("Error in view_income.php: " . $e->getMessage());
    
    // Display error message
    echo "<div style='background-color: #fee; color: #c00; padding: 20px; margin: 20px; border: 1px solid #c00; border-radius: 5px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the error logs for more details.</p>";
    echo "</div>";
    
    // Stop execution
    exit();
}

// Flush the output buffer
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Income - Finance Tracker</title>
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
                            <h1 class="text-xl font-bold text-white">Income Records</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <a href="income_form.php" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Add Income
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-400">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Income Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Total Income</h3>
                                <p class="text-3xl font-bold text-white"><?php echo formatCurrency($total_income); ?></p>
                            </div>
                            <div class="p-3 bg-green-500/20 rounded-xl">
                                <i class="fas fa-dollar-sign text-2xl text-green-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Total Records</h3>
                                <p class="text-3xl font-bold text-white"><?php echo $income_records->num_rows; ?></p>
                            </div>
                            <div class="p-3 bg-blue-500/20 rounded-xl">
                                <i class="fas fa-list text-2xl text-blue-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-effect rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-white/60 text-sm font-medium mb-1">Average Income</h3>
                                <?php
                                $avg_income = $income_records->num_rows > 0 ? $total_income / $income_records->num_rows : 0;
                                ?>
                                <p class="text-3xl font-bold text-white"><?php echo formatCurrency($avg_income); ?></p>
                            </div>
                            <div class="p-3 bg-purple-500/20 rounded-xl">
                                <i class="fas fa-chart-line text-2xl text-purple-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                    <form method="POST" action="" class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" id="search" placeholder="Search transactions..." 
                                       class="w-full pl-10 pr-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <i class="fas fa-search absolute left-3 top-3 text-white/40"></i>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <input type="date" name="start_date" required
                                   class="px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <input type="date" name="end_date" required
                                   class="px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <button type="submit" 
                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Income Records -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/50 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <?php 
                                $income_records->data_seek(0);
                                while ($record = $income_records->fetch_assoc()): 
                                ?>
                                    <tr class="hover:bg-white/5 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-white">
                                            <?php echo date('M d, Y', strtotime($record['income_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-white">
                                            <?php echo formatCurrency($record['amount']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-500/20 text-blue-400">
                                                <?php echo htmlspecialchars($record['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-white">
                                            <?php echo htmlspecialchars($record['source'] ?? 'Not specified'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($record['customer_name'])): ?>
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                                        <i class="fas fa-user text-white text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-white font-medium"><?php echo htmlspecialchars($record['customer_name']); ?></div>
                                                        <?php if (!empty($record['customer_email'])): ?>
                                                            <div class="text-white/60 text-sm"><?php echo htmlspecialchars($record['customer_email']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-white/40">No customer</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-white/80">
                                            <?php echo htmlspecialchars($record['description'] ?? 'No description'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <a href="edit_income.php?id=<?php echo $record['id']; ?>" 
                                                   class="text-yellow-400 hover:text-yellow-300">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_income.php?id=<?php echo $record['id']; ?>" 
                                                   class="text-red-400 hover:text-red-300"
                                                   onclick="return confirm('Are you sure you want to delete this income record?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="mt-6 flex items-center justify-between">
                            <div class="text-white/60 text-sm">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1) {
                                    echo '<a href="?page=1" class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">1</a>';
                                    if ($start_page > 2) {
                                        echo '<span class="px-2 text-white/60">...</span>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    $active = $i === $page ? 'bg-green-500 text-white' : 'bg-white/5 text-white hover:bg-white/10';
                                    echo "<a href='?page=$i' class='px-3 py-1 border border-white/10 rounded-lg $active transition-colors duration-200'>$i</a>";
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="px-2 text-white/60">...</span>';
                                    }
                                    echo "<a href='?page=$total_pages' class='px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200'>$total_pages</a>";
                                }
                                ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
