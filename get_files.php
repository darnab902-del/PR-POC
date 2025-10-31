<?php
/**
 * Get uploaded files list
 * Returns JSON response with list of uploaded files
 */

require_once 'config.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'files' => array(),
    'message' => ''
);

try {
    $logFile = UPLOAD_DIR . 'upload_log.json';
    
    if (file_exists($logFile)) {
        $logData = file_get_contents($logFile);
        $files = json_decode($logData, true);
        
        if ($files && is_array($files)) {
            // Sort files by upload date (newest first)
            usort($files, function($a, $b) {
                return strtotime($b['upload_date']) - strtotime($a['upload_date']);
            });
            
            // Format files for display
            $formattedFiles = array();
            foreach (array_slice($files, 0, 10) as $file) { // Show only last 10 files
                $formattedFiles[] = array(
                    'name' => $file['original_name'],
                    'size' => formatFileSize($file['size']),
                    'date' => date('M j, Y H:i', strtotime($file['upload_date'])),
                    'type' => $file['extension'],
                    'path' => $file['stored_name'],
                    'description' => $file['description'] ?? ''
                );
            }
            
            $response['success'] = true;
            $response['files'] = $formattedFiles;
        }
    }
    
    if (empty($response['files'])) {
        $response['message'] = 'No files uploaded yet.';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error loading files: ' . $e->getMessage();
    error_log('Get files error: ' . $e->getMessage());
}

echo json_encode($response);
?>