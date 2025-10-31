# File Upload System

A comprehensive PHP file upload system with security features, file management, and a modern web interface.

## Features

### 🔒 Security
- File type validation (extension and MIME type)
- File size limits
- Malicious content detection
- Secure file naming to prevent overwrites
- Directory traversal protection

### 📁 File Management
- Multiple file upload support
- File descriptions
- Automatic thumbnail generation for images
- File listing and search
- Download functionality
- Delete functionality with confirmation

### 🎨 User Interface
- Modern, responsive design
- Drag-and-drop interface
- Progress bar for uploads
- Real-time file information display
- File type filtering
- Search functionality

### ⚙️ Configuration
- Configurable file size limits
- Customizable allowed file types
- Storage limits and cleanup options
- Logging system
- Thumbnail settings

## Installation

1. Clone or download the files to your web server directory
2. Ensure PHP has write permissions to the project directory
3. Configure settings in `config.php` if needed
4. Access `index.html` in your web browser

## File Structure

```
PR-POC/
├── index.html          # Main upload interface
├── upload.php          # File upload handler
├── config.php          # Configuration settings
├── get_files.php       # API to retrieve file list
├── download.php        # Secure file download handler
├── manage.php          # File management interface
├── uploads/            # Upload directory (auto-created)
│   ├── thumbnails/     # Image thumbnails (auto-created)
│   └── upload_log.json # File metadata log
└── logs/               # Log files (auto-created)
```

## Configuration Options

### File Types
Edit `ALLOWED_EXTENSIONS` and `ALLOWED_MIME_TYPES` in `config.php` to control which file types are allowed.

### Size Limits
- `MAX_FILE_SIZE`: Maximum size per file (default: 5MB)
- `MAX_FILES_PER_UPLOAD`: Maximum files per upload (default: 10)
- `MAX_TOTAL_SIZE_PER_UPLOAD`: Maximum total size per upload (default: 50MB)

### Storage Management
- `AUTO_CLEANUP_DAYS`: Automatically delete files older than X days
- `MAX_STORAGE_SIZE`: Maximum total storage size

### Security Settings
- `PRESERVE_ORIGINAL_NAMES`: Whether to keep original filenames
- `ENABLE_VIRUS_SCAN`: Enable virus scanning (requires ClamAV)

## Usage

### Basic Upload
1. Open `index.html` in your browser
2. Select one or more files
3. Add an optional description
4. Click "Upload Files"

### File Management
1. Access `manage.php` for file management
2. Search and filter uploaded files
3. Download or delete files as needed

### API Endpoints

#### Upload Files
```
POST /upload.php
Content-Type: multipart/form-data

files[]: File data
description: Optional description
```

#### Get File List
```
GET /get_files.php
Returns: JSON array of uploaded files
```

#### Download File
```
GET /download.php?file=filename
Returns: File download
```

## Security Considerations

1. **File Validation**: All uploaded files are validated for type and content
2. **Secure Storage**: Files are renamed to prevent conflicts and security issues
3. **Access Control**: Downloads are handled through a secure script
4. **Content Scanning**: Basic malicious content detection is implemented
5. **Logging**: All activities are logged for security monitoring

## Requirements

- PHP 7.0 or higher
- GD extension (for image thumbnails)
- Write permissions for upload directory
- Web server (Apache, Nginx, etc.)

## Customization

### Styling
Edit the CSS in `index.html` and `manage.php` to customize the appearance.

### File Processing
Modify `upload.php` to add custom file processing logic.

### Storage Backend
Replace the JSON file storage with a database by implementing custom storage functions.

## Troubleshooting

### Upload Fails
1. Check file size limits in both `config.php` and `php.ini`
2. Verify write permissions on upload directory
3. Check error logs in the `logs/` directory

### Files Not Displaying
1. Ensure `upload_log.json` exists and is readable
2. Check file permissions
3. Verify JSON format is valid

### Thumbnails Not Generated
1. Ensure GD extension is installed
2. Check write permissions on `uploads/thumbnails/` directory
3. Verify image files are valid

## License

This project is open source and available under the MIT License.

## Support

For issues and questions, please check the error logs first, then review the configuration settings.