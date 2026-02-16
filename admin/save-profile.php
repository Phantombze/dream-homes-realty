<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/../includes/site-settings.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/dashboard.php');
    exit;
}

require_csrf();
dh_ensure_site_settings_table();

$action = $_POST['action'] ?? '';
$redirectTo = $_SERVER['HTTP_REFERER'] ?? '/admin/dashboard.php';

try {
    if ($action === 'remove_avatar') {
        // Remove the current profile picture
        $currentAvatar = dh_site_setting('admin.profile_picture', '');
        if ($currentAvatar) {
            $filePath = __DIR__ . '/..' . $currentAvatar;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        dh_set_site_setting('admin.profile_picture', null);
        header('Location: ' . $redirectTo);
        exit;
    }
    
    if ($action === 'save_avatar' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            throw new Exception('Invalid file type. Please upload PNG, JPG, or WebP.');
        }
        
        // Validate file size (max 1MB)
        if ($file['size'] > 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 1MB.');
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Delete old avatar if exists
        $currentAvatar = dh_site_setting('admin.profile_picture', '');
        if ($currentAvatar) {
            $oldPath = __DIR__ . '/..' . $currentAvatar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . uniqid() . '.' . strtolower($extension);
        $destination = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save the file.');
        }
        
        // Save the path to site_settings
        $avatarUrl = '/uploads/avatars/' . $filename;
        dh_set_site_setting('admin.profile_picture', $avatarUrl);
        
        header('Location: ' . $redirectTo);
        exit;
    }
    
    // No file uploaded, just redirect back
    header('Location: ' . $redirectTo);
    exit;
    
} catch (Exception $e) {
    // In case of error, redirect back with error
    header('Location: ' . $redirectTo . '?error=' . urlencode($e->getMessage()));
    exit;
}
