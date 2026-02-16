<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

function slugify(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
    $value = preg_replace('/[\s-]+/', '-', $value);
    return trim($value, '-');
}

function unique_slug(string $base): string {
    $slug = slugify($base);
    if ($slug === '') {
        $slug = 'listing';
    }
    $candidate = $slug;
    $counter = 1;

    while (true) {
        $stmt = db()->prepare('SELECT id FROM listings WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $candidate]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $counter += 1;
        $candidate = $slug . '-' . $counter;
    }
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/bootstrap.php';
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'quick_add') {
        $title = trim($_POST['title'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;

        if ($title === '' || $location === '' || $price === '') {
            $message = 'Title, location, and price are required.';
            $messageType = 'error';
        } else {
            $slug = unique_slug($title);
            $stmt = db()->prepare('INSERT INTO listings (title, slug, location, price, badge, badge_style, featured, image_url, url, map_query, stats_json, keywords_json) VALUES (:title, :slug, :location, :price, :badge, :badge_style, :featured, :image_url, :url, :map_query, :stats_json, :keywords_json)');
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'location' => $location,
                'price' => $price,
                'badge' => $badge,
                'badge_style' => 'navy',
                'featured' => $featured,
                'image_url' => null,
                'url' => '#',
                'map_query' => '',
                'stats_json' => json_encode([]),
                'keywords_json' => json_encode([]),
            ]);
            $message = 'Listing added. Add photos and details on the listings page.';
            $messageType = 'success';
        }
    } elseif ($action === 'toggle_featured') {
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $featured = (int) ($_POST['featured'] ?? 0);
        if ($listingId > 0) {
            $stmt = db()->prepare('UPDATE listings SET featured = :featured WHERE id = :id');
            $stmt->execute([
                'featured' => $featured,
                'id' => $listingId,
            ]);
            $message = 'Listing updated.';
            $messageType = 'success';
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'featured', 'standard', 'new'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$filterLabels = [
    'all' => 'All listings',
    'featured' => 'Featured listings',
    'standard' => 'Standard listings',
    'new' => 'New listings (30 days)',
];
$filterLabel = $filterLabels[$filter] ?? 'All listings';

$filterSql = '';
$filterParams = [];
if ($filter === 'featured') {
    $filterSql = 'WHERE featured = 1';
} elseif ($filter === 'standard') {
    $filterSql = 'WHERE featured = 0';
} elseif ($filter === 'new') {
    $filterSql = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

$listingCount = (int) db()->query('SELECT COUNT(*) AS total FROM listings')->fetch()['total'];
$categoryCount = (int) db()->query('SELECT COUNT(*) AS total FROM categories')->fetch()['total'];
$adminCount = (int) db()->query('SELECT COUNT(*) AS total FROM admin_users')->fetch()['total'];
$featuredCount = (int) db()->query('SELECT COUNT(*) AS total FROM listings WHERE featured = 1')->fetch()['total'];
$recentCount = (int) db()->query('SELECT COUNT(*) AS total FROM listings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetch()['total'];

$recentStmt = db()->prepare('SELECT id, title, location, price, featured, created_at FROM listings ' . $filterSql . ' ORDER BY created_at DESC LIMIT 6');
$recentStmt->execute($filterParams);
$recentListings = $recentStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-header">
    <div>
        <h1 class="admin-title">Dashboard</h1>
        <p class="admin-subtitle">Monitor listings, create quick drafts, and manage featured content.</p>
    </div>
    <div class="admin-header-right">
        <a class="admin-btn secondary" href="/admin/listings.php">Manage Listings</a>
        <a class="admin-btn" href="/admin/categories.php">Add Category</a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div class="admin-stat">
        <h3><?php echo $listingCount; ?></h3>
        <span>Listings</span>
    </div>
    <div class="admin-stat">
        <h3><?php echo $categoryCount; ?></h3>
        <span>Categories</span>
    </div>
    <div class="admin-stat">
        <h3><?php echo $adminCount; ?></h3>
        <span>Admins</span>
    </div>
    <div class="admin-stat">
        <h3><?php echo $featuredCount; ?></h3>
        <span>Featured Listings</span>
    </div>
</div>

<div class="admin-dashboard-grid" style="margin-top: 24px;">
    <section class="admin-card">
        <div class="admin-panel-header">
            <div>
                <h2>Quick publish</h2>
                <div class="admin-panel-note">Add the basics now, then enrich the listing with images and details.</div>
            </div>
        </div>
        <form method="post" class="admin-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="quick_add">
            <div>
                <label>Listing Title</label>
                <input type="text" name="title" placeholder="Modern waterfront villa">
            </div>
            <div>
                <label>Location</label>
                <input type="text" name="location" placeholder="Placencia, Belize">
            </div>
            <div class="admin-grid-2">
                <div>
                    <label>Price</label>
                    <input type="text" name="price" placeholder="$875,000">
                </div>
                <div>
                    <label>Badge (optional)</label>
                    <input type="text" name="badge" placeholder="New">
                </div>
            </div>
            <label class="admin-checkbox">
                <input type="checkbox" name="featured" value="1">
                Feature this listing
            </label>
            <div class="admin-actions-row">
                <button type="submit" class="admin-btn">Create Listing</button>
                <a class="admin-btn secondary" href="/admin/listings.php">Full Editor</a>
            </div>
        </form>
    </section>

    <aside class="admin-stack">
        <!-- Email & Security Card -->
        <div class="admin-card">
            <div class="admin-panel-header">
                <div>
                    <h2>Account & Security</h2>
                    <div class="admin-panel-note">Email and security settings</div>
                </div>
            </div>
            <div class="admin-security-section">
                <div class="admin-security-item">
                    <div class="admin-security-label">Email Address</div>
                    <div class="admin-security-value"><?php echo htmlspecialchars($_SESSION['admin_email'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="admin-security-item">
                    <div class="admin-security-label">Account Status</div>
                    <div class="admin-security-value">
                        <span class="admin-badge admin-badge-success">Active</span>
                    </div>
                </div>
                <div class="admin-security-item">
                    <div class="admin-security-label">Last Login</div>
                    <div class="admin-security-value">Now</div>
                </div>
            </div>
            <div class="admin-actions-row" style="margin-top: 16px;">
                <a href="#" class="admin-btn admin-btn-secondary" style="width: 100%;">Change Password</a>
            </div>
        </div>
        <div class="admin-card">
            <div class="admin-panel-header">
                <div>
                    <h2>Quick filters</h2>
                    <div class="admin-panel-note">Use these to review focus areas fast.</div>
                </div>
            </div>
            <div class="admin-filter-bar">
                <a class="admin-filter <?php echo $filter === 'all' ? 'is-active' : ''; ?>" href="/admin/dashboard.php?filter=all">All listings</a>
                <a class="admin-filter <?php echo $filter === 'featured' ? 'is-active' : ''; ?>" href="/admin/dashboard.php?filter=featured">Featured</a>
                <a class="admin-filter <?php echo $filter === 'standard' ? 'is-active' : ''; ?>" href="/admin/dashboard.php?filter=standard">Standard</a>
                <a class="admin-filter <?php echo $filter === 'new' ? 'is-active' : ''; ?>" href="/admin/dashboard.php?filter=new">New (30 days)</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-panel-header">
                <div>
                    <h2>Key metrics</h2>
                    <div class="admin-panel-note">Live snapshot from the database.</div>
                </div>
            </div>
            <div class="admin-metric-list">
                <div class="admin-metric">
                    <span>Total listings</span>
                    <strong><?php echo $listingCount; ?></strong>
                </div>
                <div class="admin-metric">
                    <span>Featured listings</span>
                    <strong><?php echo $featuredCount; ?></strong>
                </div>
                <div class="admin-metric">
                    <span>New in 30 days</span>
                    <strong><?php echo $recentCount; ?></strong>
                </div>
                <div class="admin-metric">
                    <span>Total categories</span>
                    <strong><?php echo $categoryCount; ?></strong>
                </div>
            </div>
        </div>
    </aside>
</div>

<div class="admin-card" style="margin-top: 24px;">
    <div class="admin-panel-header">
        <div>
            <h2>Recent listings</h2>
            <div class="admin-panel-note">Showing <?php echo htmlspecialchars($filterLabel, ENT_QUOTES, 'UTF-8'); ?>.</div>
        </div>
        <a class="admin-link" href="/admin/listings.php">View all</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Price</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentListings)): ?>
                    <tr>
                        <td colspan="5" class="admin-muted">No listings found for this filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentListings as $listing): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="admin-muted"><?php echo htmlspecialchars($listing['location'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($listing['price'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></td>
                            <td>
                                <span class="admin-pill <?php echo !empty($listing['featured']) ? 'on' : ''; ?>">
                                    <?php echo !empty($listing['featured']) ? 'Featured' : 'Standard'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                    <a class="admin-link" href="/admin/listings.php?edit=<?php echo (int) $listing['id']; ?>">Edit</a>
                                    <form method="post" class="admin-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <input type="hidden" name="listing_id" value="<?php echo (int) $listing['id']; ?>">
                                        <input type="hidden" name="featured" value="<?php echo !empty($listing['featured']) ? '0' : '1'; ?>">
                                        <button type="submit" class="admin-btn secondary small">
                                            <?php echo !empty($listing['featured']) ? 'Unfeature' : 'Feature'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
