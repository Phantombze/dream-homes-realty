<?php
require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

$categoryId = (int) ($_GET['category'] ?? 0);

// Build query based on category filter
if ($categoryId > 0) {
    // Get listings for specific category
    $stmt = db()->prepare('
        SELECT l.* FROM listings l
        INNER JOIN listing_categories lc ON l.id = lc.listing_id
        WHERE lc.category_id = :category_id
        ORDER BY l.featured DESC, l.created_at DESC
    ');
    $stmt->execute(['category_id' => $categoryId]);
    $listings = $stmt->fetchAll();
} else {
    // Get all listings
    $listings = db()->query('SELECT * FROM listings ORDER BY featured DESC, created_at DESC')->fetchAll();
}

$output = [];
foreach ($listings as $listing) {
    // Get images
    $imageStmt = db()->prepare('SELECT file_path, is_primary FROM listing_images WHERE listing_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC');
    $imageStmt->execute(['id' => $listing['id']]);
    $images = $imageStmt->fetchAll();

    $imagePaths = [];
    foreach ($images as $image) {
        $imagePaths[] = $image['file_path'];
    }

    // Get categories for this listing
    $catStmt = db()->prepare('
        SELECT c.id, c.name FROM categories c
        INNER JOIN listing_categories lc ON c.id = lc.category_id
        WHERE lc.listing_id = :listing_id
        ORDER BY c.name ASC
    ');
    $catStmt->execute(['listing_id' => $listing['id']]);
    $categories = $catStmt->fetchAll();

    $slug = $listing['slug'] ?? '';
    $detailUrl = $listing['url'] ?: ($slug ? '/listing.php?slug=' . urlencode($slug) : '#');

    $output[] = [
        'id' => (int) $listing['id'],
        'title' => $listing['title'],
        'slug' => $slug,
        'location' => $listing['location'],
        'price' => $listing['price'],
        'badge' => $listing['badge'],
        'badgeStyle' => $listing['badge_style'],
        'featured' => (int) ($listing['featured'] ?? 0),
        'image' => $listing['image_url'],
        'images' => $imagePaths,
        'url' => $detailUrl,
        'mapQuery' => $listing['map_query'] ?? '',
        'stats' => json_decode($listing['stats_json'] ?? '[]', true),
        'keywords' => json_decode($listing['keywords_json'] ?? '[]', true),
        'amenities' => json_decode($listing['amenities_json'] ?? '[]', true),
        'categories' => array_map(fn($cat) => ['id' => (int) $cat['id'], 'name' => $cat['name']], $categories),
    ];
}

echo json_encode($output);
