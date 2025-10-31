<?php
/**
 * File Upload Configuration
 * Modify these settings according to your requirements
 */

// Upload directory (with trailing slash)
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Maximum file size in bytes (5MB = 5 * 1024 * 1024)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Allowed file extensions
define('ALLOWED_EXTENSIONS', [
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
    
    // Documents
    'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',
    
    // Spreadsheets
    'xls', 'xlsx', 'csv', 'ods',
    
    // Presentations
    'ppt', 'pptx', 'odp',
    
    // Archives
    'zip', 'rar', '7z', 'tar', 'gz',
    
    // Audio
    'mp3', 'wav', 'ogg', 'flac',
    
    // Video
    'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'
]);

// Allowed MIME types for additional security
define('ALLOWED_MIME_TYPES', [
    // Images
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
    
    // Documents
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
    'text/rtf',
    'application/vnd.oasis.opendocument.text',
    
    // Spreadsheets
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv',
    'application/vnd.oasis.opendocument.spreadsheet',
    
    // Presentations
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.oasis.opendocument.presentation',
    
    // Archives
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
    'application/x-tar',
    'application/gzip',
    
    // Audio
    'audio/mpeg',
    'audio/wav',
    'audio/ogg',
    'audio/flac',
    
    // Video
    'video/mp4',
    'video/x-msvideo',
    'video/quicktime',
    'video/x-ms-wmv',
    'video/x-flv',
    'video/webm'
]);

// Database configuration (if you want to use database instead of JSON file)
define('DB_HOST', 'localhost');
define('DB_NAME', 'file_upload');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security settings
define('ENABLE_VIRUS_SCAN', false); // Set to true if you have ClamAV installed
define('QUARANTINE_DIR', __DIR__ . '/quarantine/');

// Thumbnail settings for images
define('THUMBNAIL_WIDTH', 150);
define('THUMBNAIL_HEIGHT', 150);
define('THUMBNAIL_QUALITY', 85);

// File naming strategy
define('PRESERVE_ORIGINAL_NAMES', false); // Set to true to keep original filenames
define('ADD_TIMESTAMP', true);
define('ADD_RANDOM_STRING', true);

// Upload limits
define('MAX_FILES_PER_UPLOAD', 10);
define('MAX_TOTAL_SIZE_PER_UPLOAD', 50 * 1024 * 1024); // 50MB total

// Cleanup settings
define('AUTO_CLEANUP_DAYS', 30); // Delete files older than 30 days (0 = disabled)
define('MAX_STORAGE_SIZE', 1024 * 1024 * 1024); // 1GB max storage (0 = unlimited)

// Logging settings
define('ENABLE_LOGGING', true);
define('LOG_FILE', __DIR__ . '/logs/upload.log');

/**
 * Get formatted file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Log upload activity
 */
function logActivity($message, $level = 'INFO') {
    if (!ENABLE_LOGGING) return;
    
    $logDir = dirname(LOG_FILE);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = "[$timestamp] [$level] [IP: $ip] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Clean up old files
 */
function cleanupOldFiles() {
    if (AUTO_CLEANUP_DAYS <= 0) return;
    
    $cutoffTime = time() - (AUTO_CLEANUP_DAYS * 24 * 60 * 60);
    $uploadDir = UPLOAD_DIR;
    
    if (!is_dir($uploadDir)) return;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getMTime() < $cutoffTime) {
            unlink($file->getPathname());
            logActivity("Cleaned up old file: " . $file->getFilename());
        }
    }
}

/**
 * Check storage limits
 */
function checkStorageLimit() {
    if (MAX_STORAGE_SIZE <= 0) return true;
    
    $totalSize = 0;
    $uploadDir = UPLOAD_DIR;
    
    if (!is_dir($uploadDir)) return true;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
        }
    }
    
    return $totalSize < MAX_STORAGE_SIZE;
}

// Initialize logging
logActivity("Configuration loaded");

// Run cleanup if enabled
if (rand(1, 100) <= 5) { // 5% chance to run cleanup on each request
    cleanupOldFiles();
}
?>