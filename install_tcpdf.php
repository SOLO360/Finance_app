<?php
/**
 * TCPDF Installation Script
 * This script downloads and installs the TCPDF library for PDF generation
 */

echo "Starting TCPDF installation...\n";

// Create tcpdf directory if it doesn't exist
if (!is_dir('tcpdf')) {
    mkdir('tcpdf', 0755, true);
    echo "Created tcpdf directory\n";
}

// Try multiple download sources for TCPDF
$download_sources = [
    'https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip',
    'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.zip',
    'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.4.zip'
];

$zip_file = 'tcpdf.zip';
$download_success = false;

foreach ($download_sources as $tcpdf_url) {
    echo "Trying to download from: $tcpdf_url\n";
    
    // Set up context for HTTPS requests
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]
    ]);
    
    $zip_content = file_get_contents($tcpdf_url, false, $context);
    
    if ($zip_content !== false) {
        echo "Successfully downloaded TCPDF library\n";
        $download_success = true;
        break;
    } else {
        echo "Failed to download from this source, trying next...\n";
    }
}

if (!$download_success) {
    echo "Error: Could not download TCPDF library from any source.\n";
    echo "Please download manually using one of these methods:\n\n";
    echo "Method 1 - Direct Download:\n";
    echo "1. Go to: https://github.com/tecnickcom/TCPDF\n";
    echo "2. Click the green 'Code' button\n";
    echo "3. Select 'Download ZIP'\n";
    echo "4. Extract the ZIP file\n";
    echo "5. Copy all contents to a 'tcpdf' folder in your project root\n\n";
    
    echo "Method 2 - Composer (if available):\n";
    echo "1. Run: composer require tecnickcom/tcpdf\n";
    echo "2. Copy vendor/tecnickcom/tcpdf to tcpdf/\n\n";
    
    echo "Method 3 - Manual Installation:\n";
    echo "1. Download from: https://github.com/tecnickcom/TCPDF/releases\n";
    echo "2. Extract to 'tcpdf' folder in your project root\n\n";
    
    echo "After manual installation, you can use the PDF export feature.\n";
    echo "The system will automatically fall back to HTML-to-PDF if TCPDF is not available.\n";
    exit(1);
}

// Save the zip file
file_put_contents($zip_file, $zip_content);
echo "Saved TCPDF library to zip file\n";

// Extract the zip file
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo('tcpdf_temp/');
    $zip->close();
    echo "Extracted TCPDF library\n";
    
    // Find the TCPDF directory in the extracted files
    $temp_dir = 'tcpdf_temp/';
    $tcpdf_source_dir = null;
    
    // Look for the TCPDF directory
    $files = scandir($temp_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_dir($temp_dir . $file)) {
            if (strpos($file, 'TCPDF') !== false) {
                $tcpdf_source_dir = $temp_dir . $file . '/';
                break;
            }
        }
    }
    
    if ($tcpdf_source_dir && is_dir($tcpdf_source_dir)) {
        echo "Found TCPDF source directory: $tcpdf_source_dir\n";
        
        // Copy all files from source to tcpdf directory
        $files = scandir($tcpdf_source_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $source = $tcpdf_source_dir . $file;
                $destination = 'tcpdf/' . $file;
                
                if (is_dir($source)) {
                    // Copy directory recursively
                    if (!is_dir($destination)) {
                        mkdir($destination, 0755, true);
                    }
                    copyDirectory($source, $destination);
                } else {
                    // Copy file
                    copy($source, $destination);
                }
            }
        }
        echo "Moved TCPDF files to tcpdf directory\n";
    } else {
        echo "Error: Could not find TCPDF source directory in extracted files\n";
        echo "Please check the extracted contents manually\n";
    }
    
    // Clean up
    unlink($zip_file);
    deleteDirectory('tcpdf_temp');
    echo "Cleaned up temporary files\n";
    
} else {
    echo "Error: Could not extract TCPDF library\n";
    exit(1);
}

echo "TCPDF installation completed successfully!\n";
echo "You can now use PDF export functionality in your reports.\n";

/**
 * Copy directory recursively
 */
function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            if (is_dir($source_path)) {
                copyDirectory($source_path, $dest_path);
            } else {
                copy($source_path, $dest_path);
            }
        }
    }
}

/**
 * Delete directory recursively
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
    }
    
    rmdir($dir);
}
?> 