# TCPDF Installation Guide

This guide provides multiple methods to install TCPDF for PDF generation in your Finance Tracker application.

## Method 1: Automatic Installation (Recommended)

Run the installation script:
```bash
php install_tcpdf.php
```

## Method 2: Manual Download and Installation

### Step 1: Download TCPDF
1. Go to: https://github.com/tecnickcom/TCPDF
2. Click the green "Code" button
3. Select "Download ZIP"
4. Save the file to your computer

### Step 2: Extract and Install
1. Extract the downloaded ZIP file
2. Create a folder named `tcpdf` in your project root (same level as `reports.php`)
3. Copy all contents from the extracted folder to the `tcpdf` folder
4. Your structure should look like:
   ```
   Finance_app/
   ├── tcpdf/
   │   ├── tcpdf.php
   │   ├── config/
   │   ├── fonts/
   │   └── ...
   ├── reports.php
   ├── view_expenses.php
   └── ...
   ```

## Method 3: Using Composer (Advanced)

If you have Composer installed:

```bash
# Navigate to your project directory
cd /path/to/Finance_app

# Install TCPDF via Composer
composer require tecnickcom/tcpdf

# Copy TCPDF to your project
cp -r vendor/tecnickcom/tcpdf tcpdf/
```

## Method 4: Direct Download Links

### Option A: Latest Version
- Download: https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip

### Option B: Stable Release
- Download: https://github.com/tecnickcom/TCPDF/releases/latest

## Verification

After installation, verify that the following file exists:
```
tcpdf/tcpdf.php
```

## Testing PDF Export

1. Go to your reports page: `http://localhost/Finance_app/reports.php`
2. Select a report type and date range
3. Click the "PDF" button
4. The PDF should download automatically

## Troubleshooting

### Issue: "TCPDF library not found"
**Solution**: Ensure the `tcpdf` folder exists in your project root and contains `tcpdf.php`

### Issue: "Permission denied"
**Solution**: 
```bash
chmod -R 755 tcpdf/
```

### Issue: "Download failed"
**Solution**: Use manual installation method (Method 2)

### Issue: "PDF generation fails"
**Solution**: The system will automatically fall back to HTML-to-PDF generation

## Fallback Option

If TCPDF installation fails, the system automatically uses a simple HTML-to-PDF method:
- No external libraries required
- Uses browser's print functionality
- Click "Print/Save as PDF" button
- Works immediately without installation

## File Structure After Installation

```
Finance_app/
├── tcpdf/                    # TCPDF library files
│   ├── tcpdf.php            # Main TCPDF file
│   ├── config/              # Configuration files
│   ├── fonts/               # Font files
│   ├── include/             # Include files
│   └── ...
├── reports.php              # Reports page with PDF export
├── simple_pdf.php           # Fallback PDF generation
├── install_tcpdf.php        # Installation script
└── ...
```

## Support

If you encounter issues:
1. Check that all files are in the correct locations
2. Verify file permissions
3. Try the fallback HTML-to-PDF method
4. Check your web server error logs

The PDF export feature will work with either TCPDF or the fallback method, so you'll always have PDF generation capability! 