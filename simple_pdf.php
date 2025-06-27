<?php
/**
 * Simple PDF Generation
 * Alternative PDF generation using HTML to PDF conversion
 * This doesn't require external libraries like TCPDF
 */

function generateSimplePDF($data, $filename = 'report.pdf') {
    // Create HTML content
    $html = createPDFHTML($data);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Convert HTML to PDF using browser's print functionality
    // This is a simple approach - for production, consider using a proper PDF library
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .summary { margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #333; border-bottom: 2px solid #333; padding-bottom: 5px; }
            .positive { color: green; }
            .negative { color: red; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()">Print/Save as PDF</button>
        </div>
        ' . $html . '
    </body>
    </html>';
}

function createPDFHTML($data) {
    $html = '';
    
    // Report Header
    $html .= '<div class="header">
        <h1>' . ucfirst($data['report_type']) . ' Financial Report</h1>
        <p>Period: ' . date('M d, Y', strtotime($data['start_date'])) . ' to ' . date('M d, Y', strtotime($data['end_date'])) . '</p>
        <p>Generated on: ' . date('M d, Y H:i:s') . '</p>
    </div>';
    
    // Summary Section
    $html .= '<div class="section">
        <h2>Financial Summary</h2>
        <table>
            <tr>
                <th>Category</th>
                <th>Amount (TSh)</th>
                <th>Transactions</th>
            </tr>
            <tr>
                <td>Total Income</td>
                <td class="positive">' . number_format($data['total_income'], 2) . '</td>
                <td>' . count($data['income_data']) . '</td>
            </tr>
            <tr>
                <td>Total Expenses</td>
                <td class="negative">' . number_format($data['total_expenses'], 2) . '</td>
                <td>' . count($data['expense_data']) . '</td>
            </tr>
            <tr>
                <td>Net ' . ($data['net_profit'] >= 0 ? 'Profit' : 'Loss') . '</td>
                <td class="' . ($data['net_profit'] >= 0 ? 'positive' : 'negative') . '">' . number_format(abs($data['net_profit']), 2) . '</td>
                <td>-</td>
            </tr>
        </table>
    </div>';
    
    // Category Breakdown
    if (!empty($data['category_data'])) {
        $html .= '<div class="section">
            <h2>Expenses by Category</h2>
            <table>
                <tr>
                    <th>Category</th>
                    <th>Amount (TSh)</th>
                    <th>Transactions</th>
                </tr>';
        
        foreach ($data['category_data'] as $category) {
            $html .= '<tr>
                <td>' . htmlspecialchars($category['category_name'] ?? 'Uncategorized') . '</td>
                <td>' . number_format($category['category_total'], 2) . '</td>
                <td>' . $category['transaction_count'] . '</td>
            </tr>';
        }
        
        $html .= '</table></div>';
    }
    
    // Payment Method Breakdown
    if (!empty($data['payment_method_data'])) {
        $html .= '<div class="section">
            <h2>Expenses by Payment Method</h2>
            <table>
                <tr>
                    <th>Payment Method</th>
                    <th>Amount (TSh)</th>
                    <th>Transactions</th>
                </tr>';
        
        foreach ($data['payment_method_data'] as $method) {
            $html .= '<tr>
                <td>' . htmlspecialchars($method['payment_method_name'] ?? 'N/A') . '</td>
                <td>' . number_format($method['method_total'], 2) . '</td>
                <td>' . $method['transaction_count'] . '</td>
            </tr>';
        }
        
        $html .= '</table></div>';
    }
    
    // Top Expenses
    if (!empty($data['top_expenses'])) {
        $html .= '<div class="section">
            <h2>Top 10 Expenses</h2>
            <table>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Amount (TSh)</th>
                </tr>';
        
        foreach ($data['top_expenses'] as $index => $expense) {
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($expense['description']) . '</td>
                <td>' . htmlspecialchars($expense['category_name'] ?? 'N/A') . '</td>
                <td>' . number_format($expense['amount'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</table></div>';
    }
    
    // Monthly Data
    if (!empty($data['monthly_data'])) {
        $html .= '<div class="section">
            <h2>Monthly Breakdown (Last 12 Months)</h2>
            <table>
                <tr>
                    <th>Month</th>
                    <th>Income (TSh)</th>
                    <th>Expenses (TSh)</th>
                    <th>Net (TSh)</th>
                    <th>Status</th>
                </tr>';
        
        foreach ($data['monthly_data'] as $month) {
            $month_net = $month['income'] - $month['expense'];
            $month_name = date('M Y', strtotime($month['month'] . '-01'));
            $status = $month_net >= 0 ? 'Profit' : 'Loss';
            
            $html .= '<tr>
                <td>' . $month_name . '</td>
                <td class="positive">' . number_format($month['income'], 2) . '</td>
                <td class="negative">' . number_format($month['expense'], 2) . '</td>
                <td class="' . ($month_net >= 0 ? 'positive' : 'negative') . '">' . number_format(abs($month_net), 2) . '</td>
                <td>' . $status . '</td>
            </tr>';
        }
        
        $html .= '</table></div>';
    }
    
    // Detailed Transactions (if detailed report)
    if ($data['report_type'] == 'detailed') {
        // Income Details
        if (!empty($data['income_data'])) {
            $html .= '<div class="section">
                <h2>Income Details</h2>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount (TSh)</th>
                    </tr>';
            
            foreach ($data['income_data'] as $income) {
                $html .= '<tr>
                    <td>' . date('M d, Y', strtotime($income['income_date'])) . '</td>
                    <td>' . htmlspecialchars($income['description']) . '</td>
                    <td>' . htmlspecialchars($income['category_name'] ?? 'N/A') . '</td>
                    <td class="positive">' . number_format($income['amount'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</table></div>';
        }
        
        // Expense Details
        if (!empty($data['expense_data'])) {
            $html .= '<div class="section">
                <h2>Expense Details</h2>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Payment Method</th>
                        <th>Amount (TSh)</th>
                    </tr>';
            
            foreach ($data['expense_data'] as $expense) {
                $html .= '<tr>
                    <td>' . date('M d, Y', strtotime($expense['expense_date'])) . '</td>
                    <td>' . htmlspecialchars($expense['description']) . '</td>
                    <td>' . htmlspecialchars($expense['category_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($expense['payment_method_name'] ?? 'N/A') . '</td>
                    <td class="negative">' . number_format($expense['amount'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</table></div>';
        }
    }
    
    return $html;
}
?> 