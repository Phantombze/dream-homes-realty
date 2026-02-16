<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

$message = '';
$messageType = 'success';

// Available directories
$directories = [
    'media' => [
        'label' => 'Media Library',
        'path' => __DIR__ . '/../uploads/media/',
        'url' => '/uploads/media/'
    ],
    'listings' => [
        'label' => 'Listing Images',
        'path' => __DIR__ . '/../uploads/listings/',
        'url' => '/uploads/listings/'
    ]
];

// Get selected directory
$selectedDir = $_GET['dir'] ?? 'media';
if (!isset($directories[$selectedDir])) {
    $selectedDir = 'media';
}

$uploadDir = $directories[$selectedDir]['path'];
$uploadDirUrl = $directories[$selectedDir]['url'];

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/bootstrap.php';
    require_csrf();
    
    // Update selected directory based on form submission
    if (isset($_POST['current_dir']) && isset($directories[$_POST['current_dir']])) {
        $selectedDir = $_POST['current_dir'];
        $uploadDir = $directories[$selectedDir]['path'];
        $uploadDirUrl = $directories[$selectedDir]['url'];
    }

    // Handle bulk delete
    if (isset($_POST['delete_selected']) && isset($_POST['selected_files']) && is_array($_POST['selected_files'])) {
        $deletedCount = 0;
        $failedCount = 0;
        
        foreach ($_POST['selected_files'] as $filename) {
            $filename = basename($filename);
            $filePath = $uploadDir . $filename;
            
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            } else {
                $failedCount++;
            }
        }
        
        if ($deletedCount > 0 && $failedCount === 0) {
            $message = $deletedCount . ' file(s) deleted successfully.';
            $messageType = 'success';
        } elseif ($deletedCount > 0 && $failedCount > 0) {
            $message = $deletedCount . ' file(s) deleted, ' . $failedCount . ' failed.';
            $messageType = 'error';
        } else {
            $message = 'Failed to delete selected files.';
            $messageType = 'error';
        }
    }
    // Handle single delete
    elseif (isset($_POST['delete_file']) && $_POST['delete_file'] !== '') {
        $filename = basename($_POST['delete_file']);
        $filePath = $uploadDir . $filename;
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $message = 'File deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete file.';
                $messageType = 'error';
            }
        } else {
            $message = 'File not found.';
            $messageType = 'error';
        }
    }
    // Handle upload
    elseif (isset($_FILES['media_file'])) {
        try {
            $file = $_FILES['media_file'];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds the server upload limit.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed size.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'Upload blocked by PHP extension.'
                ];
                throw new Exception($errorMessages[$file['error']] ?? 'Unknown upload error.');
            }
            
            $allowedTypes = [
                'image/png', 
                'image/jpeg', 
                'image/jpg', 
                'image/gif', 
                'image/svg+xml',
                'image/webp'
            ];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            // Check file size explicitly
            if ($file['size'] > $maxSize) {
                $sizeMB = round($file['size'] / 1024 / 1024, 2);
                throw new Exception('File is too large (' . $sizeMB . 'MB). Maximum size is 5MB.');
            }
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Please upload PNG, JPG, GIF, SVG, or WebP.');
            }
            
            // Generate safe filename
            $originalName = $file['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-z0-9_-]/i', '_', $baseName);
            $safeName = preg_replace('/_+/', '_', $safeName);
            $safeName = trim($safeName, '_');
            
            if ($safeName === '') {
                $safeName = 'image_' . time();
            }
            
            // Check if file exists and append number if needed
            $finalName = $safeName . '.' . $extension;
            $counter = 1;
            while (file_exists($uploadDir . $finalName)) {
                $finalName = $safeName . '_' . $counter . '.' . $extension;
                $counter++;
            }
            
            $destination = $uploadDir . $finalName;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $message = 'File uploaded successfully: ' . htmlspecialchars($finalName, ENT_QUOTES, 'UTF-8');
                $messageType = 'success';
            } else {
                throw new Exception('Failed to save file.');
            }
        } catch (Throwable $e) {
            $message = 'Upload failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get list of uploaded files
$files = [];
if (is_dir($uploadDir)) {
    $scanned = scandir($uploadDir);
    foreach ($scanned as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $fullPath = $uploadDir . $item;
        if (is_file($fullPath)) {
            $files[] = [
                'name' => $item,
                'path' => $fullPath,
                'url' => $uploadDirUrl . $item,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath),
                'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
            ];
        }
    }
}

// Sort by modified date (newest first)
usort($files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-header">
    <h1 class="admin-title">Media Manager</h1>
</div>

<div class="media-tabs">
    <?php foreach ($directories as $key => $dir): ?>
        <a href="?dir=<?php echo urlencode($key); ?>" class="media-tab <?php echo $selectedDir === $key ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($dir['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-stack admin-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($selectedDir, ENT_QUOTES, 'UTF-8'); ?>">
    
    <fieldset class="admin-fieldset">
        <legend>Upload New Image to <?php echo htmlspecialchars($directories[$selectedDir]['label'], ENT_QUOTES, 'UTF-8'); ?></legend>
        <div>
            <label>Choose Image File</label>
            <input type="file" name="media_file" accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml,image/webp" required>
            <div class="admin-hint">Accepts PNG, JPG, GIF, SVG, WebP. Maximum 5MB.</div>
        </div>
        <div class="admin-actions-row">
            <button type="submit" class="admin-btn">Upload Image</button>
        </div>
    </fieldset>
</form>

<form method="post" id="bulkDeleteForm" class="admin-stack">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($selectedDir, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="delete_selected" value="1">
    
    <div class="admin-header" style="margin-top: 32px;">
        <h2 class="admin-title" style="font-size: 1.25rem;">Uploaded Images (<?php echo count($files); ?>)</h2>
        <div class="media-bulk-actions" style="display: none;">
            <button type="submit" class="admin-btn danger small" onclick="return confirm('Are you sure you want to delete the selected images?');">Delete Selected</button>
            <button type="button" class="admin-btn secondary small" id="deselectAll">Deselect All</button>
        </div>
    </div>

    <?php if (empty($files)): ?>
        <div class="admin-hint" style="padding: 24px; text-align: center; background: #f5f5f5; border-radius: 4px;">
            No images uploaded yet. Use the form above to upload your first image.
        </div>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($files as $file): ?>
                <div class="media-item">
                    <div class="media-select-wrapper">
                        <input type="checkbox" name="selected_files[]" value="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>" class="media-checkbox">
                    </div>
                    <div class="media-thumb">
                        <?php if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])): ?>
                            <img src="<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="media-thumb-placeholder">
                                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16v12H4z" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <path d="M4 16l4-4 3 3 5-5 4 4" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <div class="media-name" title="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="media-meta">
                            <?php echo formatBytes($file['size']); ?> • <?php echo date('M j, Y', $file['modified']); ?>
                        </div>
                        <div class="media-url">
                            <input type="text" value="<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>" readonly onclick="this.select();">
                            <button type="button" class="media-copy-btn" data-url="<?php echo htmlspecialchars($file['url'], ENT_QUOTES, 'UTF-8'); ?>" title="Copy URL">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" />
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                </svg>
                            </button>
                        </div>
                        <form method="post" class="media-delete-form" onsubmit="return confirm('Are you sure you want to delete this image?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($selectedDir, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="media-delete-btn">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</form>

<style>
.media-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e0e0e0;
}

.media-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.media-tab:hover {
    color: #0F2B46;
    background: rgba(15, 43, 70, 0.05);
}

.media-tab.active {
    color: #0F2B46;
    border-bottom-color: #0F2B46;
    font-weight: 600;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.media-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s;
    position: relative;
}

.media-item.selected {
    border-color: #0F2B46;
    box-shadow: 0 0 0 2px rgba(15, 43, 70, 0.2);
}

.media-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.media-select-wrapper {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 10;
}

.media-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #0F2B46;
}

.media-thumb {
    width: 100%;
    height: 200px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-thumb-placeholder {
    color: #999;
}

.media-info {
    padding: 16px;
}

.media-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #0F2B46;
}

.media-meta {
    font-size: 0.75rem;
    color: #666;
    margin-bottom: 12px;
}

.media-url {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.media-url input {
    flex: 1;
    font-size: 0.75rem;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    background: #f9f9f9;
    cursor: pointer;
}

.media-url input:focus {
    outline: none;
    border-color: #0F2B46;
    background: #fff;
}

.media-copy-btn {
    padding: 6px 12px;
    background: #0F2B46;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.media-copy-btn:hover {
    background: #1a3f5f;
}

.media-copy-btn.copied {
    background: #28a745;
}

.media-delete-form {
    margin: 0;
}

.media-delete-btn {
    width: 100%;
    padding: 8px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: background 0.2s;
}

.media-delete-btn:hover {
    background: #c82333;
}

.media-bulk-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Multi-select functionality
    const checkboxes = document.querySelectorAll('.media-checkbox');
    const bulkActions = document.querySelector('.media-bulk-actions');
    const deselectAllBtn = document.getElementById('deselectAll');
    
    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.media-checkbox:checked').length;
        if (bulkActions) {
            bulkActions.style.display = checkedCount > 0 ? 'flex' : 'none';
        }
        
        // Update visual selection
        checkboxes.forEach(function(checkbox) {
            const item = checkbox.closest('.media-item');
            if (checkbox.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updateBulkActions();
        });
    }
    
    // Copy URL functionality
    document.querySelectorAll('.media-copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const input = this.previousElementSibling;
            
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showCopied(this);
                }).catch(() => {
                    // Fallback to input select
                    input.select();
                    document.execCommand('copy');
                    showCopied(this);
                });
            } else {
                // Fallback for older browsers
                input.select();
                document.execCommand('copy');
                showCopied(this);
            }
        });
    });
    
    function showCopied(btn) {
        btn.classList.add('copied');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = originalHTML;
        }, 2000);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
