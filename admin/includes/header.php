<?php
require_once __DIR__ . '/bootstrap.php';
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream Homes Admin</title>
    <?php
    $faviconIcoPath = __DIR__ . '/../../assets/img/favicon.ico';
    $faviconPngPath = __DIR__ . '/../../assets/img/favicon.png';
    $faviconIcoUrl = '/assets/img/favicon.ico';
    $faviconPngUrl = '/assets/img/favicon.png';
    ?>
    <?php if (file_exists($faviconIcoPath)): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo $faviconIcoUrl . '?v=' . filemtime($faviconIcoPath); ?>">
    <?php endif; ?>
    <?php if (file_exists($faviconPngPath)): ?>
        <link rel="icon" type="image/png" href="<?php echo $faviconPngUrl . '?v=' . filemtime($faviconPngPath); ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css?v=20260216c">
</head>
<body>
<div class="admin-shell">
    <nav class="admin-topbar">
        <button class="admin-sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 6h18M3 12h18M3 18h18" />
            </svg>
        </button>
        <span class="admin-topbar-label">Public Site</span>
        <div class="admin-topbar-links">
            <a href="/index.php" class="admin-topbar-link">Home</a>
            <a href="/listings.php" class="admin-topbar-link">Listings</a>
            <a href="/about.php" class="admin-topbar-link">About</a>
            <a href="/contact.php" class="admin-topbar-link">Contact</a>
            <a href="/sitemap.php" class="admin-topbar-link">Sitemap</a>
        </div>
    </nav>
    <aside class="admin-sidebar">
        <div class="admin-sidebar-top">
            <div class="admin-brand">
                <span class="admin-brand-full">Dream Homes</span>
                <span class="admin-brand-short">DH</span>
            </div>
        </div>
        <nav class="admin-nav-wrapper">
            <div class="admin-nav">
            <a href="/admin/dashboard.php" class="<?php echo $current === 'dashboard.php' ? 'is-active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" />
                    </svg>
                </span>
                <span class="admin-nav-label">Dashboard</span>
            </a>
            <a href="/admin/content.php" class="<?php echo in_array($current, ['content.php', 'listings.php']) ? 'is-active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h10" />
                    </svg>
                </span>
                <span class="admin-nav-label">Content</span>
            </a>
            <a href="/admin/media.php" class="<?php echo $current === 'media.php' ? 'is-active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 4h16v12H4z" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <path d="M4 16l4-4 3 3 5-5 4 4" />
                        <path d="M20 20v-4" />
                    </svg>
                </span>
                <span class="admin-nav-label">Media</span>
            </a>
            <a href="/admin/settings.php" class="<?php echo $current === 'settings.php' ? 'is-active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z" />
                        <path d="M4 12a8 8 0 0 1 .2-1.8l-1.7-1.3 2-3.5 2 .6a8.3 8.3 0 0 1 3.1-1.8l.3-2.1h4l.3 2.1a8.3 8.3 0 0 1 3.1 1.8l2-.6 2 3.5-1.7 1.3a8 8 0 0 1 0 3.6l1.7 1.3-2 3.5-2-.6a8.3 8.3 0 0 1-3.1 1.8l-.3 2.1h-4l-.3-2.1a8.3 8.3 0 0 1-3.1-1.8l-2 .6-2-3.5 1.7-1.3A8 8 0 0 1 4 12z" />
                    </svg>
                </span>
                <span class="admin-nav-label">Settings</span>
            </a>
            <a href="/admin/view-logs.php" class="<?php echo $current === 'view-logs.php' ? 'is-active' : ''; ?>">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <path d="M14 2v6h6M10 13h4M10 17h4M8 13h.01M8 17h.01" />
                    </svg>
                </span>
                <span class="admin-nav-label">System Logs</span>
            </a>
            <a href="/admin/logout.php">
                <span class="admin-nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M15 4h4v16h-4" />
                        <path d="M10 7l5 5-5 5" />
                        <path d="M15 12H5" />
                    </svg>
                </span>
                <span class="admin-nav-label">Logout</span>
            </a>
            </div>
        </nav>
        <div class="admin-sidebar-bottom">
            <?php if (!empty($_SESSION['admin_email'])): 
                $profilePic = dh_site_setting('admin.profile_picture', '');
                $profilePicUrl = '';
                if ($profilePic !== '') {
                    $profilePicPath = __DIR__ . '/../..' . $profilePic;
                    if (is_file($profilePicPath)) {
                        $profilePicUrl = $profilePic . '?v=' . filemtime($profilePicPath);
                    }
                }
                $userInitial = strtoupper(substr($_SESSION['admin_email'], 0, 1));
            ?>
                <div class="admin-user-profile">
                    <button class="admin-user-button" type="button" data-user-menu aria-label="User menu">
                        <?php if ($profilePicUrl !== ''): ?>
                            <div class="admin-user-circle" style="background-image: url('<?php echo htmlspecialchars($profilePicUrl, ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center;"></div>
                        <?php else: ?>
                            <div class="admin-user-circle"><?php echo $userInitial; ?></div>
                        <?php endif; ?>
                    </button>
                    <div class="admin-user-name-label"><?php echo htmlspecialchars($_SESSION['admin_email'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </aside>
    <main class="admin-main">

