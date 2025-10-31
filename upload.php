<?php
// Upload configuration
require_once 'config.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'uploaded_files' => array()
);

try {
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No files were selected for upload.');
    }

    // Get description
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            throw new Exception('Failed to create upload directory.');
        }
    }

    // Process each uploaded file
    $uploadedFiles = array();
    $files = $_FILES['files'];
    $fileCount = count($files['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        // Skip if no file was uploaded for this input
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Check for upload errors
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception(getUploadError($files['error'][$i]));
        }

        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileType = $files['type'][$i];

        // Validate file
        $validation = validateFile($fileName, $fileSize, $fileTmpName);
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }

        // Generate unique filename to prevent overwrites
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueFileName = generateUniqueFileName($fileName, $fileExtension);
        $uploadPath = UPLOAD_DIR . $uniqueFileName;

        // Move uploaded file
        if (!move_uploaded_file_too($fileTmpName, $uploadPath)) {
            throw new Exception('Failed to move uploaded file: ' . $fileName);
        }

        // Save file information to database/log
        $fileInfo = array(
            'original_name' => $fileName,
            'stored_name' => $uniqueFileName,
            'size' => $fileSize,
            'type' => $fileType,
            'extension' => $fileExtension,
            'upload_date' => date('Y-m-d H:i:s'),
            'description' => $description,
            'path' => $uploadPath
        );

        saveFileInfo($fileInfo);
        $uploadedFiles[] = $fileInfo;

        // Create thumbnail for images
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
            createThumbnail($uploadPath, $fileExtension);
        }
    }

    $response['success'] = true;
    $response['message'] = count($uploadedFiles) . ' file(s) uploaded successfully!';
    $response['uploaded_files'] = $uploadedFiles;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // Log error
    error_log('File upload error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);

/**
 * Validate uploaded file
 */
function validateFile($fileName, $fileSize, $fileTmpName) {
    $result = array('valid' => false, 'message' => '');

    // Check file size
    if ($fileSize > MAX_FILE_SIZE) {
        $maxSizeMB = MAX_FILE_SIZE / (1024 * 1024);
        $result['message'] = "File '$fileName' is too large. Maximum size allowed is {$maxSizeMB}MB.";
        return $result;
    }

    // Check file extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        $result['message'] = "File type '$fileExtension' is not allowed for file '$fileName'.";
        return $result;
    }

    // Check MIME type for additional security
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $result['message'] = "Invalid file type detected for '$fileName'.";
        return $result;
    }

    // Check for malicious content (basic scan)
    $fileContent = file_get_contents($fileTmpName, false, null, 0, 1024);
    $dangerousPatterns = ['<?php', '<?=', '<script', 'javascript:', 'vbscript:'];
    
    foreach ($dangerousPatterns as $pattern) {
        if (stripos($fileContent, $pattern) !== false) {
            $result['message'] = "File '$fileName' contains potentially malicious content.";
            return $result;
        }
    }

    $result['valid'] = true;
    return $result;
}

/**
 * Generate unique filename to prevent overwrites
 */
function generateUniqueFileName($originalName, $extension) {
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $timestamp = date('Y-m-d_H-i-s');
    $randomString = substr(md5(uniqid(rand(), true)), 0, 8);
    
    return $baseName . '_' . $timestamp . '_' . $randomString . '.' . $extension;
}

/**
 * Save file information to JSON file (you can replace this with database storage)
 */
function saveFileInfo($fileInfo) {
    $logFile = UPLOAD_DIR . 'upload_log.json';
    $logData = array();

    // Read existing log data
    if (file_exists($logFile)) {
        $existingData = file_get_contents($logFile);
        $logData = json_decode($existingData, true) ?: array();
    }

    // Add new file info
    $logData[] = $fileInfo;

    // Keep only last 100 entries
    if (count($logData) > 100) {
        $logData = array_slice($logData, -100);
    }

    // Save updated log
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}

/**
 * Create thumbnail for images
 */
function createThumbnail($imagePath, $extension) {
    $thumbnailDir = UPLOAD_DIR . 'thumbnails/';
    
    if (!file_exists($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }

    $thumbnailPath = $thumbnailDir . basename($imagePath);

    try {
        $image = null;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'gif':
                $image = imagecreatefromgif($imagePath);
                break;
        }

        if ($image) {
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            
            $thumbnailWidth = 150;
            $thumbnailHeight = (int)(($originalHeight / $originalWidth) * $thumbnailWidth);
            
            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
            
            // Preserve transparency for PNG and GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $originalWidth, $originalHeight);
            
            // Save thumbnail
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 85);
                    break;
                case 'png':
                    imagepng($thumbnail, $thumbnailPath);
                    break;
                case 'gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
            }
            
            imagedestroy($image);
            imagedestroy($thumbnail);
        }
    } catch (Exception $e) {
        // Thumbnail creation failed, but don't stop the upload process
        error_log('Thumbnail creation failed: ' . $e->getMessage());
    }
}

/**
 * Get upload error message
 */
function getUploadError($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'File is too large.';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension.';
        default:
            return 'Unknown upload error.';
    }
}
?>