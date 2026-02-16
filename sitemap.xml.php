<?php
require_once __DIR__ . '/admin/includes/db.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$staticPaths = [
    '/index.php',
    '/listings.php',
    '/about.php',
    '/contact.php',
    '/sitemap.php',
];

$urls = [];
$now = date('c');
foreach ($staticPaths as $path) {
    $urls[] = [
        'loc' => $baseUrl . $path,
        'lastmod' => $now,
        'changefreq' => 'weekly',
        'priority' => $path === '/index.php' ? '1.0' : '0.7',
    ];
}

$stmt = db()->query('SELECT slug, updated_at FROM listings WHERE slug <> "" ORDER BY updated_at DESC');
$listings = $stmt ? $stmt->fetchAll() : [];
foreach ($listings as $listing) {
    $slug = $listing['slug'] ?? '';
    if (!is_string($slug) || trim($slug) === '') {
        continue;
    }
    $lastmodRaw = $listing['updated_at'] ?? '';
    $lastmod = $lastmodRaw ? date('c', strtotime($lastmodRaw)) : $now;
    $urls[] = [
        'loc' => $baseUrl . '/listing.php?slug=' . rawurlencode($slug),
        'lastmod' => $lastmod,
        'changefreq' => 'weekly',
        'priority' => '0.8',
    ];
}

header('Content-Type: application/xml; charset=UTF-8');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $entry): ?>
    <url>
        <loc><?php echo htmlspecialchars($entry['loc'], ENT_QUOTES, 'UTF-8'); ?></loc>
        <lastmod><?php echo htmlspecialchars($entry['lastmod'], ENT_QUOTES, 'UTF-8'); ?></lastmod>
        <changefreq><?php echo $entry['changefreq']; ?></changefreq>
        <priority><?php echo $entry['priority']; ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
