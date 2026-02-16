<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

$uploadDir = dirname(__DIR__) . '/uploads/listings';
$maxImages = 10;
$amenityIconOptions = [
    'gate' => 'Gated Entry',
    'canal' => 'Canal Access',
    'sun' => 'Natural Lighting',
    'rooftop' => 'Rooftop Deck',
    'utilities' => 'Utilities Ready',
    'ready' => 'Move-in Ready',
    'pool' => 'Pool',
    'parking' => 'Parking',
    'wifi' => 'Wi-Fi',
    'ac' => 'Air Conditioning',
    'security' => 'Security',
    'view' => 'Scenic View',
    'default' => 'Generic',
];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function slugify(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
    $value = preg_replace('/[\s-]+/', '-', $value);
    return trim($value, '-');
}

function unique_slug(string $base, ?int $excludeId = null): string {
    $slug = slugify($base);
    if ($slug === '') {
        $slug = 'listing';
    }
    $candidate = $slug;
    $counter = 1;

    while (true) {
        if ($excludeId) {
            $stmt = db()->prepare('SELECT id FROM listings WHERE slug = :slug AND id != :id LIMIT 1');
            $stmt->execute(['slug' => $candidate, 'id' => $excludeId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM listings WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $counter += 1;
        $candidate = $slug . '-' . $counter;
    }
}

function is_valid_image(array $file): bool {
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return false;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return false;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    return in_array($mime, $allowed, true);
}

function store_image(array $file, string $uploadDir): ?string {
    if (!is_valid_image($file)) {
        return null;
    }
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('listing_', true) . '.' . strtolower($extension);
    $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }
    return '/uploads/listings/' . $name;
}

$editing = null;
$images = [];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $badgeStyle = trim($_POST['badge_style'] ?? 'navy');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $imageUrl = trim($_POST['image_url'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        $url = trim($_POST['url'] ?? '#');
        $mapQuery = trim($_POST['map_query'] ?? '');
        $statsCsv = trim($_POST['stats'] ?? '');
        $keywordsCsv = trim($_POST['keywords'] ?? '');
        $amenityLabels = $_POST['amenity_label'] ?? [];
        $amenityIcons = $_POST['amenity_icon'] ?? [];

        $stats = array_values(array_filter(array_map('trim', explode(',', $statsCsv))));
        $keywords = array_values(array_filter(array_map('trim', explode(',', $keywordsCsv))));
        $amenities = [];
        if (is_array($amenityLabels) && is_array($amenityIcons)) {
            $count = max(count($amenityLabels), count($amenityIcons));
            for ($i = 0; $i < $count; $i += 1) {
                $label = trim((string) ($amenityLabels[$i] ?? ''));
                $icon = trim((string) ($amenityIcons[$i] ?? 'default'));
                if ($label === '') {
                    continue;
                }
                if (!array_key_exists($icon, $amenityIconOptions)) {
                    $icon = 'default';
                }
                $amenities[] = [
                    'label' => $label,
                    'icon' => $icon,
                ];
            }
        }

        if ($title !== '' && $location !== '' && $price !== '') {
            $slugBase = $slugInput !== '' ? $slugInput : $title;
            $slug = unique_slug($slugBase, $id > 0 ? $id : null);
            if ($id > 0) {
                $stmt = db()->prepare('UPDATE listings SET title = :title, slug = :slug, location = :location, price = :price, badge = :badge, badge_style = :badge_style, featured = :featured, image_url = :image_url, video_url = :video_url, url = :url, map_query = :map_query, stats_json = :stats_json, keywords_json = :keywords_json, amenities_json = :amenities_json WHERE id = :id');
                $stmt->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'location' => $location,
                    'price' => $price,
                    'badge' => $badge,
                    'badge_style' => $badgeStyle,
                    'featured' => $featured,
                    'image_url' => $imageUrl,
                    'video_url' => $videoUrl,
                    'url' => $url,
                    'map_query' => $mapQuery,
                    'stats_json' => json_encode($stats),
                    'keywords_json' => json_encode($keywords),
                    'amenities_json' => json_encode($amenities),
                    'id' => $id,
                ]);
                $message = 'Listing updated.';
                $messageType = 'success';
            } else {
                $stmt = db()->prepare('INSERT INTO listings (title, slug, location, price, badge, badge_style, featured, image_url, video_url, url, map_query, stats_json, keywords_json, amenities_json) VALUES (:title, :slug, :location, :price, :badge, :badge_style, :featured, :image_url, :video_url, :url, :map_query, :stats_json, :keywords_json, :amenities_json)');
                $stmt->execute([
                    'title' => $title,
                    'slug' => $slug,
                    'location' => $location,
                    'price' => $price,
                    'badge' => $badge,
                    'badge_style' => $badgeStyle,
                    'featured' => $featured,
                    'image_url' => $imageUrl,
                    'video_url' => $videoUrl,
                    'url' => $url,
                    'map_query' => $mapQuery,
                    'stats_json' => json_encode($stats),
                    'keywords_json' => json_encode($keywords),
                    'amenities_json' => json_encode($amenities),
                ]);
                $message = 'Listing added.';
                $messageType = 'success';
                $id = (int) db()->lastInsertId();
            }

            if ($id > 0) {
                $coverFile = $_FILES['cover_image'] ?? null;
                if ($coverFile && is_valid_image($coverFile)) {
                    $coverPath = store_image($coverFile, $uploadDir);
                    if ($coverPath) {
                        db()->prepare('UPDATE listings SET image_url = :image_url WHERE id = :id')->execute([
                            'image_url' => $coverPath,
                            'id' => $id,
                        ]);
                        db()->prepare('UPDATE listing_images SET is_primary = 0 WHERE listing_id = :id')->execute(['id' => $id]);
                        db()->prepare('INSERT INTO listing_images (listing_id, file_path, is_primary, sort_order) VALUES (:listing_id, :file_path, 1, 0)')->execute([
                            'listing_id' => $id,
                            'file_path' => $coverPath,
                        ]);
                    }
                }

                $galleryFiles = $_FILES['gallery_images'] ?? null;
                if ($galleryFiles && isset($galleryFiles['name']) && is_array($galleryFiles['name'])) {
                    $count = count($galleryFiles['name']);
                    $limit = min($count, $maxImages);
                    $orderStmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM listing_images WHERE listing_id = :id');
                    $orderStmt->execute(['id' => $id]);
                    $sortOrder = (int) ($orderStmt->fetch()['max_sort'] ?? 0);

                    for ($i = 0; $i < $limit; $i += 1) {
                        $file = [
                            'name' => $galleryFiles['name'][$i],
                            'type' => $galleryFiles['type'][$i],
                            'tmp_name' => $galleryFiles['tmp_name'][$i],
                            'error' => $galleryFiles['error'][$i],
                            'size' => $galleryFiles['size'][$i],
                        ];
                        if (!is_valid_image($file)) {
                            continue;
                        }
                        $path = store_image($file, $uploadDir);
                        if (!$path) {
                            continue;
                        }
                        $sortOrder += 1;
                        db()->prepare('INSERT INTO listing_images (listing_id, file_path, is_primary, sort_order) VALUES (:listing_id, :file_path, 0, :sort_order)')->execute([
                            'listing_id' => $id,
                            'file_path' => $path,
                            'sort_order' => $sortOrder,
                        ]);
                    }
                }

                // Handle category associations
                $selectedCategories = $_POST['categories'] ?? [];
                if (!is_array($selectedCategories)) {
                    $selectedCategories = [];
                }
                $selectedCategories = array_values(array_filter(array_map('intval', $selectedCategories)));
                
                // Delete existing associations
                db()->prepare('DELETE FROM listing_categories WHERE listing_id = :id')->execute(['id' => $id]);
                
                // Insert new associations
                if (!empty($selectedCategories)) {
                    $stmt = db()->prepare('INSERT INTO listing_categories (listing_id, category_id) VALUES (:listing_id, :category_id)');
                    foreach ($selectedCategories as $categoryId) {
                        $stmt->execute(['listing_id' => $id, 'category_id' => $categoryId]);
                    }
                }
            }
        } else {
            $message = 'Title, location, and price are required.';
            $messageType = 'error';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM listings WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $message = 'Listing deleted.';
            $messageType = 'success';
        }
    }

    if ($action === 'delete_image') {
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $imageId = (int) ($_POST['image_id'] ?? 0);
        if ($listingId > 0 && $imageId > 0) {
            $stmt = db()->prepare('SELECT file_path, is_primary FROM listing_images WHERE id = :id AND listing_id = :listing_id');
            $stmt->execute(['id' => $imageId, 'listing_id' => $listingId]);
            $image = $stmt->fetch();

            if ($image) {
                $filePath = dirname(__DIR__) . $image['file_path'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }
                db()->prepare('DELETE FROM listing_images WHERE id = :id')->execute(['id' => $imageId]);

                if ((int) $image['is_primary'] === 1) {
                    $stmt = db()->prepare('SELECT id, file_path FROM listing_images WHERE listing_id = :listing_id ORDER BY sort_order ASC, id ASC LIMIT 1');
                    $stmt->execute(['listing_id' => $listingId]);
                    $next = $stmt->fetch();
                    if ($next) {
                        db()->prepare('UPDATE listing_images SET is_primary = 1 WHERE id = :id')->execute(['id' => $next['id']]);
                        db()->prepare('UPDATE listings SET image_url = :image_url WHERE id = :id')->execute([
                            'image_url' => $next['file_path'],
                            'id' => $listingId,
                        ]);
                    } else {
                        db()->prepare('UPDATE listings SET image_url = "" WHERE id = :id')->execute(['id' => $listingId]);
                    }
                }

                $message = 'Image deleted.';
                $messageType = 'success';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = db()->prepare('SELECT * FROM listings WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch();
    if ($editing) {
        $stmt = db()->prepare('SELECT * FROM listing_images WHERE listing_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['id' => $editId]);
        $images = $stmt->fetchAll();
    }
}

$editingAmenities = [];
if ($editing) {
    $editingAmenities = json_decode($editing['amenities_json'] ?? '[]', true);
    if (!is_array($editingAmenities)) {
        $editingAmenities = [];
    }
}

// Load all categories
$allCategories = db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();

// Load categories for the current listing being edited
$editingCategories = [];
if ($editing) {
    $stmt = db()->prepare('SELECT category_id FROM listing_categories WHERE listing_id = :id');
    $stmt->execute(['id' => $editing['id']]);
    $editingCategories = array_map(fn($row) => $row['category_id'], $stmt->fetchAll());
}

$listings = db()->query('SELECT * FROM listings ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-header">
    <h1 class="admin-title">Listings</h1>
    <div class="admin-header-right">
        <?php if ($editing): ?>
            <a class="admin-btn secondary" href="/admin/listings.php">Cancel Edit</a>
        <?php endif; ?>
        <a class="admin-btn secondary" href="/admin/categories.php">Add Category</a>
        <a class="admin-btn" href="/admin/listings.php">Add New</a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="admin-stack">
<div class="admin-card">
    <h2><?php echo $editing ? 'Edit Listing' : 'Add Listing'; ?></h2>
    <form method="post" class="admin-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo $editing['id'] ?? 0; ?>">

        <div class="admin-note">
            Required fields: Title, Location, Price. Uploads replace the Image URL if you add a cover image.
        </div>

        <fieldset class="admin-fieldset">
            <legend>Basics</legend>
            <div class="admin-grid">
                <div>
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>Slug (optional)</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($editing['slug'] ?? ''); ?>" placeholder="oceanview-villa">
                    <div class="admin-hint">Leave blank to auto-generate from the title.</div>
                </div>
                <div>
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($editing['location'] ?? ''); ?>" required>
                    <div class="admin-hint">Displayed on cards. Example: “Placencia, Belize”.</div>
                </div>
                <div>
                    <label>Price</label>
                    <input type="text" name="price" value="<?php echo htmlspecialchars($editing['price'] ?? ''); ?>" required>
                    <div class="admin-hint">Use the format you want visitors to see.</div>
                </div>
                <div>
                    <label>Badge</label>
                    <input type="text" name="badge" value="<?php echo htmlspecialchars($editing['badge'] ?? ''); ?>">
                    <div class="admin-hint">Short label like “Featured” or “New”.</div>
                </div>
                <div>
                    <label>Badge Style</label>
                    <select name="badge_style">
                        <option value="navy" <?php echo ($editing['badge_style'] ?? '') === 'navy' ? 'selected' : ''; ?>>Navy</option>
                        <option value="gold" <?php echo ($editing['badge_style'] ?? '') === 'gold' ? 'selected' : ''; ?>>Gold</option>
                    </select>
                </div>
                <div>
                    <label class="admin-checkbox">
                        <input type="checkbox" name="featured" value="1" <?php echo !empty($editing['featured']) ? 'checked' : ''; ?>>
                        Featured Listing
                    </label>
                </div>
                <div>
                    <label>Listing URL (optional)</label>
                    <input type="text" name="url" value="<?php echo htmlspecialchars($editing['url'] ?? '#'); ?>">
                    <div class="admin-hint">Leave blank to use /listing.php?slug=your-slug.</div>
                </div>
            </div>
        </fieldset>

        <fieldset class="admin-fieldset">
            <legend>Categories</legend>
            <label>Assign Categories (optional)</label>
            <div class="admin-check-grid">
                <?php foreach ($allCategories as $category): ?>
                    <label class="admin-check-item">
                        <input type="checkbox" name="categories[]" value="<?php echo (int) $category['id']; ?>" <?php echo in_array($category['id'], $editingCategories) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="admin-hint">Check which categories this listing belongs to. Visitors can filter by category on the listings page.</div>
        </fieldset>

        <fieldset class="admin-fieldset">
            <legend>Media</legend>
            <div class="admin-grid">
                <div>
                    <label>Image URL (optional)</label>
                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>">
                    <div class="admin-hint">Used if you do not upload a cover image.</div>
                </div>
                <div>
                    <label>YouTube Video URL (optional)</label>
                    <input type="text" name="video_url" value="<?php echo htmlspecialchars($editing['video_url'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
                    <div class="admin-hint">Replaces the cover image with a video in the listing hero.</div>
                </div>
                <div>
                    <label>Cover Image Upload (JPG/PNG/WEBP)</label>
                    <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                    <div class="admin-hint">Uploads take priority over Image URL.</div>
                </div>
                <div>
                    <label>Gallery Images (up to 10)</label>
                    <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="admin-hint">Hold Ctrl/Command to select multiple files.</div>
                </div>
            </div>
        </fieldset>

        <fieldset class="admin-fieldset">
            <legend>Map</legend>
            <label>Map Address or Coordinates (optional)</label>
            <input type="text" name="map_query" value="<?php echo htmlspecialchars($editing['map_query'] ?? ''); ?>" placeholder="e.g. Placencia, Belize or Ambergris Caye, San Pedro">
            <div class="admin-hint">Paste a Google Maps link, coordinates, or an address. Leave blank to omit the map.</div>
        </fieldset>

        <fieldset class="admin-fieldset">
            <legend>Details</legend>
            <div class="admin-grid">
                <div>
                    <label>Stats (comma separated)</label>
                    <input type="text" name="stats" value="<?php echo htmlspecialchars($editing ? implode(', ', json_decode($editing['stats_json'] ?? '[]', true)) : ''); ?>" placeholder="4 Bed, 3 Bath, 2500 SqFt">
                    <div class="admin-hint">These appear as highlights on the card.</div>
                </div>
                <div>
                    <label>Keywords (comma separated)</label>
                    <input type="text" name="keywords" value="<?php echo htmlspecialchars($editing ? implode(', ', json_decode($editing['keywords_json'] ?? '[]', true)) : ''); ?>" placeholder="belize, beach, condo">
                    <div class="admin-hint">Used for search on the front end.</div>
                </div>
            </div>
        </fieldset>

        <fieldset class="admin-fieldset">
            <legend>Amenities</legend>
            <div class="admin-hint">Add amenities shown on the listing page. Leave rows blank to skip them.</div>
            <div class="admin-grid">
                <?php
                $amenityRowCount = max(6, min(12, count($editingAmenities) + 2));
                for ($i = 0; $i < $amenityRowCount; $i += 1):
                    $amenity = $editingAmenities[$i] ?? ['label' => '', 'icon' => 'default'];
                    $amenityLabel = is_array($amenity) ? (string) ($amenity['label'] ?? '') : '';
                    $amenityIcon = is_array($amenity) ? (string) ($amenity['icon'] ?? 'default') : 'default';
                ?>
                <div>
                    <label>Amenity Label</label>
                    <input type="text" name="amenity_label[]" value="<?php echo htmlspecialchars($amenityLabel); ?>" placeholder="e.g. Gated Entry">
                </div>
                <div>
                    <label>Icon</label>
                    <select name="amenity_icon[]">
                        <?php foreach ($amenityIconOptions as $iconKey => $iconLabel): ?>
                            <option value="<?php echo htmlspecialchars($iconKey); ?>" <?php echo $amenityIcon === $iconKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($iconLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endfor; ?>
            </div>
        </fieldset>

        <div class="admin-actions-row">
            <?php if ($editing && !empty($editing['slug'])): ?>
                <a class="admin-btn secondary" href="/listing.php?slug=<?php echo urlencode($editing['slug']); ?>" target="_blank" rel="noopener">Preview</a>
            <?php endif; ?>
            <button class="admin-btn" type="submit">Save Listing</button>
        </div>
    </form>
</div>

<?php if (!empty($images)): ?>
    <div class="admin-card">
        <h2>Listing Images</h2>
        <div class="admin-image-grid">
            <?php foreach ($images as $image): ?>
                <div class="admin-image-card">
                    <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="Listing image">
                    <?php if (!empty($image['is_primary'])): ?>
                        <div class="admin-hint" style="margin: 0 0 6px 0;">Primary</div>
                    <?php endif; ?>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="listing_id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
                        <input type="hidden" name="image_id" value="<?php echo (int) $image['id']; ?>">
                        <button type="submit" class="admin-btn danger small" onclick="return confirm('Delete this image?');">Delete Image</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="admin-card">
    <h2>All Listings</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $listing): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($listing['title']); ?></td>
                        <td><?php echo htmlspecialchars($listing['location']); ?></td>
                        <td><?php echo htmlspecialchars($listing['price']); ?></td>
                        <td>
                            <span class="admin-pill <?php echo !empty($listing['featured']) ? 'on' : ''; ?>">
                                <?php echo !empty($listing['featured']) ? 'Featured' : 'Standard'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="admin-table-actions">
                                <a class="admin-link" href="/admin/listings.php?edit=<?php echo (int) $listing['id']; ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $listing['id']; ?>">
                                    <button type="submit" class="admin-btn danger small">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
