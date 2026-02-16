<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = 'Sitemap | Dream Homes Realty';
$metaDescription = 'Explore the Dream Homes Realty sitemap for quick access to listings, collections, and contact details.';
$canonicalUrl = $baseUrl . '/sitemap.php';
$ogImage = 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80';

$seoGraph = [
    [
        '@type' => 'WebPage',
        'name' => $metaTitle,
        'url' => $canonicalUrl,
    ],
];
$seoJson = json_encode(['@context' => 'https://schema.org', '@graph' => $seoGraph], JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <?php
    $faviconIcoPath = __DIR__ . '/assets/img/favicon.ico';
    $faviconPngPath = __DIR__ . '/assets/img/favicon.png';
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
    <link rel="stylesheet" href="assets/css/site.css">
    <script type="application/ld+json"><?php echo $seoJson; ?></script>
</head>
<body>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <section class="dh-page-hero">
        <span class="dh-subtitle">Navigation</span>
        <h1>Website Sitemap</h1>
        <p>Quick links to the most important pages across Dream Homes Realty.</p>
    </section>

    <section class="dh-module-wrapper">
        <div class="dh-header-row">
            <div>
                <span class="dh-subtitle">Main Pages</span>
                <h2 class="dh-title">Explore the Site</h2>
            </div>
        </div>

        <div class="dh-grid-2">
            <div class="dh-surface">
                <h3 style="margin-bottom: 10px;">Primary</h3>
                <ul class="dh-footer-links" style="margin: 0;">
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/listings.php">Listings</a></li>
                    <li><a href="/listings.php#categories">Collections</a></li>
                    <li><a href="/about.php">About Us</a></li>
                    <li><a href="/contact.php">Contact Us</a></li>
                </ul>
            </div>

            <div class="dh-surface">
                <h3 style="margin-bottom: 10px;">Admin</h3>
                <ul class="dh-footer-links" style="margin: 0;">
                    <li><a href="/admin/login.php">Agent Login</a></li>
                </ul>
                <p class="dh-help" style="margin-top: 12px;">
                    Listings, categories, and featured content are managed from the admin dashboard.
                </p>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
