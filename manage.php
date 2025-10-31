<?php
/**
 * File Management Interface
 * Provides a web interface to manage uploaded files
 */

require_once 'config.php';

// Handle delete requests
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']);
    $filePath = UPLOAD_DIR . $filename;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            // Remove from log
            $logFile = UPLOAD_DIR . 'upload_log.json';
            if (file_exists($logFile)) {
                $logData = json_decode(file_get_contents($logFile), true);
                if ($logData) {
                    $logData = array_filter($logData, function($file) use ($filename) {
                        return $file['stored_name'] !== $filename;
                    });
                    file_put_contents($logFile, json_encode(array_values($logData), JSON_PRETTY_PRINT));
                }
            }
            
            // Delete thumbnail if exists
            $thumbnailPath = UPLOAD_DIR . 'thumbnails/' . $filename;
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            logActivity("File deleted: $filename");
            $message = "File deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete file.";
            $messageType = "error";
        }
    } else {
        $message = "File not found.";
        $messageType = "error";
    }
}

// Get uploaded files
$uploadedFiles = array();
$logFile = UPLOAD_DIR . 'upload_log.json';

if (file_exists($logFile)) {
    $logData = json_decode(file_get_contents($logFile), true);
    if ($logData && is_array($logData)) {
        // Sort by upload date (newest first)
        usort($logData, function($a, $b) {
            return strtotime($b['upload_date']) - strtotime($a['upload_date']);
        });
        $uploadedFiles = $logData;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            color: #333;
        }
        
        .nav-links {
            margin-top: 10px;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #0056b3;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .file-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .file-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            margin-right: 15px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: white;
        }
        
        .file-icon.image { background-color: #28a745; }
        .file-icon.document { background-color: #dc3545; }
        .file-icon.archive { background-color: #ffc107; color: #333; }
        .file-icon.audio { background-color: #6f42c1; }
        .file-icon.video { background-color: #fd7e14; }
        .file-icon.other { background-color: #6c757d; }
        
        .file-name {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
            word-break: break-word;
        }
        
        .file-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .file-description {
            color: #777;
            font-style: italic;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        
        .btn-download {
            background-color: #28a745;
            color: white;
        }
        
        .btn-download:hover {
            background-color: #218838;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #999;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📁 File Manager</h1>
        <div class="nav-links">
            <a href="index.html">🔄 Back to Upload</a>
            <a href="?action=refresh">🔃 Refresh</a>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($uploadedFiles); ?></div>
                <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $totalSize = 0;
                    foreach ($uploadedFiles as $file) {
                        $totalSize += $file['size'];
                    }
                    echo formatFileSize($totalSize);
                ?></div>
                <div class="stat-label">Total Size</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $imageCount = 0;
                    foreach ($uploadedFiles as $file) {
                        if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                            $imageCount++;
                        }
                    }
                    echo $imageCount;
                ?></div>
                <div class="stat-label">Images</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $recentCount = 0;
                    $oneDayAgo = time() - 86400;
                    foreach ($uploadedFiles as $file) {
                        if (strtotime($file['upload_date']) > $oneDayAgo) {
                            $recentCount++;
                        }
                    }
                    echo $recentCount;
                ?></div>
                <div class="stat-label">Last 24h</div>
            </div>
        </div>

        <?php if (!empty($uploadedFiles)): ?>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search files by name or description...">
            </div>

            <div class="filters">
                <button class="filter-btn active" data-filter="all">All Files</button>
                <button class="filter-btn" data-filter="image">Images</button>
                <button class="filter-btn" data-filter="document">Documents</button>
                <button class="filter-btn" data-filter="archive">Archives</button>
                <button class="filter-btn" data-filter="audio">Audio</button>
                <button class="filter-btn" data-filter="video">Video</button>
            </div>

            <div class="file-grid" id="fileGrid">
                <?php foreach ($uploadedFiles as $file): ?>
                    <div class="file-card" data-filename="<?php echo strtolower($file['original_name']); ?>" data-description="<?php echo strtolower($file['description'] ?? ''); ?>" data-type="<?php 
                        $ext = $file['extension'];
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) echo 'image';
                        elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf'])) echo 'document';
                        elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) echo 'archive';
                        elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'flac'])) echo 'audio';
                        elseif (in_array($ext, ['mp4', 'avi', 'mov', 'wmv'])) echo 'video';
                        else echo 'other';
                    ?>">
                        <div class="file-header">
                            <div class="file-icon <?php 
                                $ext = $file['extension'];
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) echo 'image';
                                elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf'])) echo 'document';
                                elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) echo 'archive';
                                elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'flac'])) echo 'audio';
                                elseif (in_array($ext, ['mp4', 'avi', 'mov', 'wmv'])) echo 'video';
                                else echo 'other';
                            ?>">
                                <?php 
                                    $ext = $file['extension'];
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) echo '🖼️';
                                    elseif (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf'])) echo '📄';
                                    elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) echo '🗜️';
                                    elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'flac'])) echo '🎵';
                                    elseif (in_array($ext, ['mp4', 'avi', 'mov', 'wmv'])) echo '🎬';
                                    else echo '📎';
                                ?>
                            </div>
                            <div class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></div>
                        </div>
                        
                        <div class="file-info">
                            <strong>Size:</strong> <?php echo formatFileSize($file['size']); ?><br>
                            <strong>Type:</strong> <?php echo strtoupper($file['extension']); ?><br>
                            <strong>Uploaded:</strong> <?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?>
                        </div>
                        
                        <?php if (!empty($file['description'])): ?>
                            <div class="file-description">
                                "<?php echo htmlspecialchars($file['description']); ?>"
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-actions">
                            <a href="download.php?file=<?php echo urlencode($file['stored_name']); ?>" 
                               class="btn btn-download" title="Download">
                                📥 Download
                            </a>
                            <form style="display: inline;" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to delete this file?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['stored_name']); ?>">
                                <button type="submit" class="btn btn-delete" title="Delete">
                                    🗑️ Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>📂 No files uploaded yet</h3>
                <p>Upload some files to see them here.</p>
                <a href="index.html" class="btn btn-download">Go to Upload Page</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const fileGrid = document.getElementById('fileGrid');
        const fileCards = document.querySelectorAll('.file-card');
        const filterBtns = document.querySelectorAll('.filter-btn');

        let currentFilter = 'all';

        // Search files
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                filterFiles(searchTerm, currentFilter);
            });
        }

        // Filter files by type
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                currentFilter = this.dataset.filter;
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                filterFiles(searchTerm, currentFilter);
            });
        });

        function filterFiles(searchTerm, typeFilter) {
            fileCards.forEach(card => {
                const filename = card.dataset.filename;
                const description = card.dataset.description;
                const type = card.dataset.type;
                
                const matchesSearch = !searchTerm || 
                    filename.includes(searchTerm) || 
                    description.includes(searchTerm);
                
                const matchesType = typeFilter === 'all' || type === typeFilter;
                
                if (matchesSearch && matchesType) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>