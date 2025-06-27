<!DOCTYPE html>
<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Tracker</title>
    <link href="output.css" rel="stylesheet">
    
    <!-- DataTables CSS and JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
    $(document).ready(function(){
        $('#dataTable').DataTable();
    });
    </script>

    <style>
        .nav-link {
            @apply px-4 py-2 text-gray-600 hover:text-blue-600 transition-colors duration-200;
        }
        .nav-link.active {
            @apply text-blue-600 font-medium;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php">
                            <img src="img/horizontal_logo.png" alt="Finance Tracker" class="h-12 w-auto">
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                        <a href="income_form.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'income_form.php' ? 'active' : ''; ?>">
                            Income
                        </a>
                        <a href="expenses_form.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'expenses_form.php' ? 'active' : ''; ?>">
                            Expenses
                        </a>
                        <a href="summary.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'summary.php' ? 'active' : ''; ?>">
                            Summary
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="logout.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile menu -->
    <div class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <a href="index.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                Dashboard
            </a>
            <a href="income_form.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                Income
            </a>
            <a href="expenses_form.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                Expenses
            </a>
            <a href="summary.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50">
                Summary
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
