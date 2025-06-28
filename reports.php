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
$start_date = '';
$end_date = '';
$report_type = '';
$income_data = [];
$expense_data = [];
$category_data = [];
$monthly_data = [];
$payment_method_data = [];
$top_expenses = [];
$income_sources = [];

// Set default date range to current month if no dates are set
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $report_type = 'summary';
} else {
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');
    $report_type = $_POST['report_type'] ?? 'summary';
}

try {
    // Get income data
    $sql_income = "SELECT SUM(amount) as total_income FROM income WHERE income_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_income);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_income = $stmt->get_result();
    $total_income = $result_income->fetch_assoc()['total_income'] ?? 0;
    
    // Get expense data
    $sql_expenses = "SELECT SUM(amount) as total_expenses FROM expenses WHERE expense_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_expenses);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_expenses = $stmt->get_result();
    $total_expenses = $result_expenses->fetch_assoc()['total_expenses'] ?? 0;
    
    // Calculate net profit
    $net_profit = $total_income - $total_expenses;
    
    // Get detailed income data
    $sql_income_detailed = "SELECT i.*, i.category as category_name 
                           FROM income i 
                           WHERE i.income_date BETWEEN ? AND ? 
                           ORDER BY i.income_date DESC";
    $stmt = $conn->prepare($sql_income_detailed);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_income_detailed = $stmt->get_result();
    while ($row = $result_income_detailed->fetch_assoc()) {
        $income_data[] = $row;
    }
    
    // Get detailed expense data
    $sql_expense_detailed = "SELECT e.*, ec.name as category_name, pm.name as payment_method_name 
                            FROM expenses e 
                            LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                            LEFT JOIN payment_methods pm ON e.payment_method_id = pm.id 
                            WHERE e.expense_date BETWEEN ? AND ? 
                            ORDER BY e.expense_date DESC";
    $stmt = $conn->prepare($sql_expense_detailed);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_expense_detailed = $stmt->get_result();
    while ($row = $result_expense_detailed->fetch_assoc()) {
        $expense_data[] = $row;
    }
    
    // Get expenses by category
    $sql_category = "SELECT ec.name as category_name, SUM(e.amount) as category_total, COUNT(e.id) as transaction_count
                    FROM expenses e 
                    LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                    WHERE e.expense_date BETWEEN ? AND ? 
                    GROUP BY ec.id, ec.name 
                    ORDER BY category_total DESC";
    $stmt = $conn->prepare($sql_category);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_category = $stmt->get_result();
    while ($row = $result_category->fetch_assoc()) {
        $category_data[] = $row;
    }
    
    // Get expenses by payment method
    $sql_payment_method = "SELECT pm.name as payment_method_name, SUM(e.amount) as method_total, COUNT(e.id) as transaction_count
                          FROM expenses e 
                          LEFT JOIN payment_methods pm ON e.payment_method_id = pm.id 
                          WHERE e.expense_date BETWEEN ? AND ? 
                          GROUP BY pm.id, pm.name 
                          ORDER BY method_total DESC";
    $stmt = $conn->prepare($sql_payment_method);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_payment_method = $stmt->get_result();
    while ($row = $result_payment_method->fetch_assoc()) {
        $payment_method_data[] = $row;
    }
    
    // Get top 10 expenses
    $sql_top_expenses = "SELECT e.*, ec.name as category_name 
                        FROM expenses e 
                        LEFT JOIN expense_categories ec ON e.category_id = ec.id 
                        WHERE e.expense_date BETWEEN ? AND ? 
                        ORDER BY e.amount DESC 
                        LIMIT 10";
    $stmt = $conn->prepare($sql_top_expenses);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_top_expenses = $stmt->get_result();
    while ($row = $result_top_expenses->fetch_assoc()) {
        $top_expenses[] = $row;
    }
    
    // Get income sources
    $sql_income_sources = "SELECT ic.name as source_name, SUM(i.amount) as source_total, COUNT(i.id) as transaction_count
                          FROM income i 
                          LEFT JOIN income_categories ic ON i.category_id = ic.id 
                          WHERE i.income_date BETWEEN ? AND ? 
                          GROUP BY ic.id, ic.name 
                          ORDER BY source_total DESC";
    $stmt = $conn->prepare($sql_income_sources);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result_income_sources = $stmt->get_result();
    while ($row = $result_income_sources->fetch_assoc()) {
        $income_sources[] = $row;
    }
    
    // Get monthly data for the last 12 months
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
                        WHERE income_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(income_date, '%Y-%m')
                        
                        UNION ALL
                        
                        SELECT 
                            DATE_FORMAT(expense_date, '%Y-%m') as month,
                            0 as income,
                            SUM(amount) as expense
                        FROM expenses 
                        WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
                    ) combined
                    GROUP BY month
                    ORDER BY month DESC";
    $stmt = $conn->prepare($sql_monthly);
    $stmt->execute();
    $result_monthly = $stmt->get_result();
    while ($row = $result_monthly->fetch_assoc()) {
        $monthly_data[] = $row;
    }
    
} catch (Exception $e) {
    $error_message = "Error generating report: " . $e->getMessage();
}

// Handle export functionality
if (isset($_POST['export']) && $_POST['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ['Financial Report', 'From: ' . $start_date, 'To: ' . $end_date]);
    fputcsv($output, []);
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Total Income', 'Total Expenses', 'Net Profit']);
    fputcsv($output, [$total_income, $total_expenses, $net_profit]);
    fputcsv($output, []);
    
    // If detailed report, add both income and expense details
    if ($report_type == 'detailed') {
        // Add income details
        fputcsv($output, ['Income Details']);
        fputcsv($output, ['Date', 'Description', 'Category', 'Amount']);
        foreach ($income_data as $income) {
            fputcsv($output, [
                $income['income_date'],
                $income['description'],
                $income['category_name'] ?? 'N/A',
                $income['amount']
            ]);
        }
        fputcsv($output, []);
    // Add expense details
    fputcsv($output, ['Expense Details']);
    fputcsv($output, ['Date', 'Description', 'Category', 'Payment Method', 'Amount']);
    foreach ($expense_data as $expense) {
        fputcsv($output, [
            $expense['expense_date'],
            $expense['description'],
            $expense['category_name'] ?? 'N/A',
            $expense['payment_method_name'] ?? 'N/A',
            $expense['amount']
        ]);
        }
    } else {
        // Default: only expense details (legacy behavior)
        fputcsv($output, ['Expense Details']);
        fputcsv($output, ['Date', 'Description', 'Category', 'Payment Method', 'Amount']);
        foreach ($expense_data as $expense) {
            fputcsv($output, [
                $expense['expense_date'],
                $expense['description'],
                $expense['category_name'] ?? 'N/A',
                $expense['payment_method_name'] ?? 'N/A',
                $expense['amount']
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Handle PDF export functionality
if (isset($_POST['export']) && $_POST['export'] == 'pdf') {
    // Try to use TCPDF first
    if (file_exists('tcpdf/tcpdf.php')) {
        // Include TCPDF library
        require_once('tcpdf/tcpdf.php');
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Finance Tracker');
        $pdf->SetAuthor($user['username']);
        $pdf->SetTitle(ucfirst($report_type) . ' Financial Report');
        $pdf->SetSubject('Financial Report');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'Finance Tracker', 'Financial Report', array(0,0,0), array(0,0,0));
        $pdf->setFooterData(array(0,0,0), array(0,0,0));
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Report Header
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, ucfirst($report_type) . ' Financial Report', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)), 0, 1, 'C');
        $pdf->Cell(0, 10, 'Generated on: ' . date('M d, Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Summary Section
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Financial Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        // Summary Table
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(60, 10, 'Category', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Amount (TSh)', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Transactions', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(60, 10, 'Total Income', 1, 0, 'L');
        $pdf->Cell(40, 10, number_format($total_income, 2), 1, 0, 'R');
        $pdf->Cell(40, 10, count($income_data), 1, 1, 'C');
        
        $pdf->Cell(60, 10, 'Total Expenses', 1, 0, 'L');
        $pdf->Cell(40, 10, number_format($total_expenses, 2), 1, 0, 'R');
        $pdf->Cell(40, 10, count($expense_data), 1, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(60, 10, 'Net ' . ($net_profit >= 0 ? 'Profit' : 'Loss'), 1, 0, 'L');
        $pdf->Cell(40, 10, number_format(abs($net_profit), 2), 1, 0, 'R');
        $pdf->Cell(40, 10, '', 1, 1, 'C');
        $pdf->Ln(10);
        
        // Category Breakdown
        if (!empty($category_data)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Expenses by Category', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(80, 10, 'Category', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Amount (TSh)', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Transactions', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 12);
            foreach ($category_data as $category) {
                $pdf->Cell(80, 10, $category['category_name'] ?? 'Uncategorized', 1, 0, 'L');
                $pdf->Cell(40, 10, number_format($category['category_total'], 2), 1, 0, 'R');
                $pdf->Cell(40, 10, $category['transaction_count'], 1, 1, 'C');
            }
            $pdf->Ln(10);
        }
        
        // Payment Method Breakdown
        if (!empty($payment_method_data)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Expenses by Payment Method', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(80, 10, 'Payment Method', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Amount (TSh)', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Transactions', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 12);
            foreach ($payment_method_data as $method) {
                $pdf->Cell(80, 10, $method['payment_method_name'] ?? 'N/A', 1, 0, 'L');
                $pdf->Cell(40, 10, number_format($method['method_total'], 2), 1, 0, 'R');
                $pdf->Cell(40, 10, $method['transaction_count'], 1, 1, 'C');
            }
            $pdf->Ln(10);
        }
        
        // Top Expenses
        if (!empty($top_expenses)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Top 10 Expenses', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
            $pdf->Cell(60, 10, 'Description', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Category', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Amount (TSh)', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 12);
            foreach ($top_expenses as $index => $expense) {
                $pdf->Cell(15, 10, $index + 1, 1, 0, 'C');
                $pdf->Cell(60, 10, substr($expense['description'], 0, 25), 1, 0, 'L');
                $pdf->Cell(40, 10, substr($expense['category_name'] ?? 'N/A', 0, 15), 1, 0, 'L');
                $pdf->Cell(40, 10, number_format($expense['amount'], 2), 1, 1, 'R');
            }
            $pdf->Ln(10);
        }
        
        // Monthly Data
        if (!empty($monthly_data)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Monthly Breakdown (Last 12 Months)', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(30, 10, 'Month', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Income (TSh)', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Expenses (TSh)', 1, 0, 'C', true);
            $pdf->Cell(35, 10, 'Net (TSh)', 1, 0, 'C', true);
            $pdf->Cell(25, 10, 'Status', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 12);
            foreach ($monthly_data as $month) {
                $month_net = $month['income'] - $month['expense'];
                $month_name = date('M Y', strtotime($month['month'] . '-01'));
                $status = $month_net >= 0 ? 'Profit' : 'Loss';
                
                $pdf->Cell(30, 10, $month_name, 1, 0, 'L');
                $pdf->Cell(35, 10, number_format($month['income'], 2), 1, 0, 'R');
                $pdf->Cell(35, 10, number_format($month['expense'], 2), 1, 0, 'R');
                $pdf->Cell(35, 10, number_format(abs($month_net), 2), 1, 0, 'R');
                $pdf->Cell(25, 10, $status, 1, 1, 'C');
            }
            $pdf->Ln(10);
        }
        
        // Detailed Transactions (if detailed report)
        if ($report_type == 'detailed') {
            // Income Details
            if (!empty($income_data)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'Income Details', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 12);
                
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(30, 10, 'Date', 1, 0, 'C', true);
                $pdf->Cell(70, 10, 'Description', 1, 0, 'C', true);
                $pdf->Cell(40, 10, 'Category', 1, 0, 'C', true);
                $pdf->Cell(40, 10, 'Amount (TSh)', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 12);
                foreach ($income_data as $income) {
                    $pdf->Cell(30, 10, date('M d, Y', strtotime($income['income_date'])), 1, 0, 'L');
                    $pdf->Cell(70, 10, substr($income['description'], 0, 30), 1, 0, 'L');
                    $pdf->Cell(40, 10, substr($income['category_name'] ?? 'N/A', 0, 20), 1, 0, 'L');
                    $pdf->Cell(40, 10, number_format($income['amount'], 2), 1, 1, 'R');
                }
                $pdf->Ln(10);
            }
            
            // Expense Details
            if (!empty($expense_data)) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 16);
                $pdf->Cell(0, 10, 'Expense Details', 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 12);
                
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(25, 10, 'Date', 1, 0, 'C', true);
                $pdf->Cell(50, 10, 'Description', 1, 0, 'C', true);
                $pdf->Cell(30, 10, 'Category', 1, 0, 'C', true);
                $pdf->Cell(30, 10, 'Payment', 1, 0, 'C', true);
                $pdf->Cell(35, 10, 'Amount (TSh)', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 12);
                foreach ($expense_data as $expense) {
                    $pdf->Cell(25, 10, date('M d, Y', strtotime($expense['expense_date'])), 1, 0, 'L');
                    $pdf->Cell(50, 10, substr($expense['description'], 0, 25), 1, 0, 'L');
                    $pdf->Cell(30, 10, substr($expense['category_name'] ?? 'N/A', 0, 15), 1, 0, 'L');
                    $pdf->Cell(30, 10, substr($expense['payment_method_name'] ?? 'N/A', 0, 15), 1, 0, 'L');
                    $pdf->Cell(35, 10, number_format($expense['amount'], 2), 1, 1, 'R');
                }
            }
        }
        
        // Output PDF
        $pdf->Output('financial_report_' . date('Y-m-d') . '.pdf', 'D');
        exit();
    } else {
        // Fallback to simple PDF generation
        require_once('simple_pdf.php');
        
        $pdf_data = [
            'report_type' => $report_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
            'income_data' => $income_data,
            'expense_data' => $expense_data,
            'category_data' => $category_data,
            'payment_method_data' => $payment_method_data,
            'top_expenses' => $top_expenses,
            'monthly_data' => $monthly_data
        ];
        
        generateSimplePDF($pdf_data, 'financial_report_' . date('Y-m-d') . '.pdf');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Finance Tracker</title>
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
                            <h1 class="text-xl font-bold text-white">Financial Reports</h1>
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
                <!-- Report Controls -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                    <h2 class="text-lg font-medium text-white mb-4">Generate Report</h2>
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="report_type" class="block text-sm font-medium text-white/60 mb-2">Report Type</label>
                            <select name="report_type" id="report_type" 
                                    class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                                <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                                <option value="category" <?php echo $report_type == 'category' ? 'selected' : ''; ?>>Category Analysis</option>
                                <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Trends</option>
                            </select>
                        </div>
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
                            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Generate
                            </button>
                            <button type="submit" name="export" value="csv" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-download mr-2"></i>
                                CSV
                            </button>
                            <button type="submit" name="export" value="pdf" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Report Period -->
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-white mb-2">
                        <?php echo ucfirst($report_type); ?> Report
                    </h3>
                    <p class="text-white/60">
                        <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </p>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Income -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/60 text-sm font-medium">Total Income</p>
                                <p class="text-3xl font-bold text-green-400">TSh <?php echo number_format($total_income, 2); ?></p>
                                <p class="text-white/40 text-xs mt-1"><?php echo count($income_data); ?> transactions</p>
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
                                <p class="text-white/40 text-xs mt-1"><?php echo count($expense_data); ?> transactions</p>
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

                <!-- Report Content Based on Type -->
                <?php if ($report_type == 'summary'): ?>
                    <!-- Summary Report -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Category Breakdown -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Expenses by Category</h3>
                            <?php if (!empty($category_data)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($category_data as $category): ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-3 w-3 bg-blue-400 rounded-full"></div>
                                                <span class="text-white"><?php echo htmlspecialchars($category['category_name'] ?? 'Uncategorized'); ?></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-white font-medium">TSh <?php echo number_format($category['category_total'], 2); ?></span>
                                                <p class="text-white/40 text-xs"><?php echo $category['transaction_count']; ?> transactions</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60 text-center py-8">No expense categories found for this period.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Payment Method Breakdown -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Expenses by Payment Method</h3>
                            <?php if (!empty($payment_method_data)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($payment_method_data as $method): ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-3 w-3 bg-purple-400 rounded-full"></div>
                                                <span class="text-white"><?php echo htmlspecialchars($method['payment_method_name'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-white font-medium">TSh <?php echo number_format($method['method_total'], 2); ?></span>
                                                <p class="text-white/40 text-xs"><?php echo $method['transaction_count']; ?> transactions</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60 text-center py-8">No payment method data found for this period.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($report_type == 'detailed'): ?>
                    <!-- Detailed Report -->
                    <div class="space-y-8">
                        <!-- Income Details -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Income Details</h3>
                            <?php if (!empty($income_data)): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-white/60 text-sm">
                                                <th class="pb-4">Date</th>
                                                <th class="pb-4">Description</th>
                                                <th class="pb-4">Category</th>
                                                <th class="pb-4">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-white">
                                            <?php foreach ($income_data as $income): ?>
                                                <tr class="border-t border-white/10 hover:bg-white/5 transition-colors duration-200">
                                                    <td class="py-4"><?php echo date('M d, Y', strtotime($income['income_date'])); ?></td>
                                                    <td class="py-4 font-medium"><?php echo htmlspecialchars($income['description']); ?></td>
                                                    <td class="py-4">
                                                        <span class="px-3 py-1 rounded-full text-sm bg-green-500/20 text-green-400">
                                                            <?php echo htmlspecialchars($income['category_name'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 font-bold text-green-400">TSh <?php echo number_format($income['amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60 text-center py-8">No income records found for this period.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Expense Details -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Expense Details</h3>
                            <?php if (!empty($expense_data)): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-white/60 text-sm">
                                                <th class="pb-4">Date</th>
                                                <th class="pb-4">Description</th>
                                                <th class="pb-4">Category</th>
                                                <th class="pb-4">Payment Method</th>
                                                <th class="pb-4">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-white">
                                            <?php foreach ($expense_data as $expense): ?>
                                                <tr class="border-t border-white/10 hover:bg-white/5 transition-colors duration-200">
                                                    <td class="py-4"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                                    <td class="py-4 font-medium"><?php echo htmlspecialchars($expense['description']); ?></td>
                                                    <td class="py-4">
                                                        <span class="px-3 py-1 rounded-full text-sm bg-blue-500/20 text-blue-400">
                                                            <?php echo htmlspecialchars($expense['category_name'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4">
                                                        <span class="px-3 py-1 rounded-full text-sm bg-purple-500/20 text-purple-400">
                                                            <?php echo htmlspecialchars($expense['payment_method_name'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 font-bold text-red-400">TSh <?php echo number_format($expense['amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60 text-center py-8">No expense records found for this period.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($report_type == 'category'): ?>
                    <!-- Category Analysis -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Category Chart -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Expense Categories</h3>
                            <canvas id="categoryChart" height="300"></canvas>
                        </div>

                        <!-- Top Expenses -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h3 class="text-lg font-medium text-white mb-4">Top 10 Expenses</h3>
                            <?php if (!empty($top_expenses)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($top_expenses as $index => $expense): ?>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-white/40 text-sm">#<?php echo $index + 1; ?></span>
                                                <div class="h-3 w-3 bg-red-400 rounded-full"></div>
                                                <span class="text-white"><?php echo htmlspecialchars($expense['description']); ?></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-white font-medium">TSh <?php echo number_format($expense['amount'], 2); ?></span>
                                                <p class="text-white/40 text-xs"><?php echo htmlspecialchars($expense['category_name'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-white/60 text-center py-8">No expenses found for this period.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($report_type == 'monthly'): ?>
                    <!-- Monthly Trends -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                        <h3 class="text-lg font-medium text-white mb-4">12-Month Trends</h3>
                        <canvas id="monthlyChart" height="300"></canvas>
                    </div>

                    <!-- Monthly Table -->
                    <?php if (!empty($monthly_data)): ?>
                    <div class="glass-effect rounded-2xl p-6 shadow-xl">
                        <h3 class="text-lg font-medium text-white mb-4">Monthly Breakdown</h3>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Category Chart
            <?php if ($report_type == 'category' && !empty($category_data)): ?>
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($category_data, 'category_name')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($category_data, 'category_total')); ?>,
                            backgroundColor: [
                                '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
                                '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#ffffff'
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>

            // Monthly Chart
            <?php if ($report_type == 'monthly' && !empty($monthly_data)): ?>
            const monthlyCtx = document.getElementById('monthlyChart');
            if (monthlyCtx) {
                const monthlyLabels = <?php echo json_encode(array_map(function($month) { 
                    return date('M Y', strtotime($month['month'] . '-01')); 
                }, $monthly_data)); ?>;
                const monthlyIncome = <?php echo json_encode(array_column($monthly_data, 'income')); ?>;
                const monthlyExpense = <?php echo json_encode(array_column($monthly_data, 'expense')); ?>;

                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Income',
                            data: monthlyIncome,
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderColor: '#10b981',
                            borderWidth: 1
                        }, {
                            label: 'Expenses',
                            data: monthlyExpense,
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                            borderColor: '#ef4444',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                                    color: '#ffffff'
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
            }
            <?php endif; ?>
        });
    </script>
</body>
</html> 