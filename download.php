<?php
/**
 * File Download Handler
 * Securely serves uploaded files
 */

require_once 'config.php';

// Get the requested file
$fileName = $_GET['file'] ?? '';

if (empty($fileName)) {
    http_response_code(400);
    die('No file specified');
}

// Sanitize filename to prevent directory traversal
$fileName = basename($fileName);
$filePath = UPLOAD_DIR . $fileName;

// Check if file exists
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Get file info from log
$logFile = UPLOAD_DIR . 'upload_log.json';
$originalName = $fileName;
$fileType = 'application/octet-stream';

if (file_exists($logFile)) {
    $logData = json_decode(file_get_contents($logFile), true);
    if ($logData) {
        foreach ($logData as $fileInfo) {
            if ($fileInfo['stored_name'] === $fileName) {
                $originalName = $fileInfo['original_name'];
                $fileType = $fileInfo['type'];
                break;
            }
        }
    }
}

// Security check: verify file extension
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
    http_response_code(403);
    die('File type not allowed');
}

// Log download
logActivity("File downloaded: $originalName (stored as: $fileName)");

// Set appropriate headers
header('Content-Type: ' . $fileType);
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private');
header('Pragma: private');

// Output file
readfile($filePath);
exit;
?>