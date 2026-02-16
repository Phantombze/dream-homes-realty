<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

// Get current tab from query parameter
$currentTab = $_GET['tab'] ?? 'listings';
if (!in_array($currentTab, ['listings', 'categories'], true)) {
    $currentTab = 'listings';
}

$message = '';
$messageType = 'success';

// Handle form submissions based on tab
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $tab = $_POST['tab'] ?? 'listings';
    
    if ($tab === 'categories') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $imageUrl = trim($_POST['image_url'] ?? '');
            $url = trim($_POST['url'] ?? '#');

            if ($name !== '') {
                if ($id > 0) {
                    $stmt = db()->prepare('UPDATE categories SET name = :name, image_url = :image_url, url = :url WHERE id = :id');
                    $stmt->execute([
                        'name' => $name,
                        'image_url' => $imageUrl,
                        'url' => $url,
                        'id' => $id,
                    ]);
                    $message = 'Category updated successfully.';
                } else {
                    $stmt = db()->prepare('INSERT INTO categories (name, image_url, url) VALUES (:name, :image_url, :url)');
                    $stmt->execute([
                        'name' => $name,
                        'image_url' => $imageUrl,
                        'url' => $url,
                    ]);
                    $message = 'Category added successfully.';
                }
            } else {
                $message = 'Category name is required.';
                $messageType = 'error';
            }
        }
        
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = db()->prepare('DELETE FROM categories WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'Category deleted.';
            }
        }
    }
}

// Get data for both tabs
$categories = db()->query('SELECT * FROM categories ORDER BY id DESC')->fetchAll();
$listings = db()->query('SELECT * FROM listings ORDER BY id DESC')->fetchAll();
$listingCount = count($listings);
$categoryCount = count($categories);

require __DIR__ . '/includes/header.php';
?>

<div class="admin-header">
    <div>
        <h1 class="admin-title">Content Management</h1>
        <p class="admin-subtitle">Manage your <?php echo $listingCount; ?> listings and <?php echo $categoryCount; ?> categories</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-notice <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- Tab Switcher - Styled like segmented control -->
<div class="admin-tabs-container">
    <div class="admin-tabs">
        <a href="/admin/content.php?tab=listings" class="admin-tab <?php echo $currentTab === 'listings' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10" />
            </svg>
            <span>Listings</span>
            <span class="admin-tab-count"><?php echo $listingCount; ?></span>
        </a>
        <a href="/admin/content.php?tab=categories" class="admin-tab <?php echo $currentTab === 'categories' ? 'is-active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h6v6H4zM14 6h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
            </svg>
            <span>Categories</span>
            <span class="admin-tab-count"><?php echo $categoryCount; ?></span>
        </a>
    </div>
</div>

<?php if ($currentTab === 'listings'): ?>
<!-- Listings Panel -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>All Listings</h2>
        <a href="/admin/listings.php" class="admin-btn admin-btn-primary">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; margin-right: 6px;">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Add New Listing
        </a>
    </div>
    
    <?php if (empty($listings)): ?>
        <div class="admin-empty-state">
            <div class="admin-empty-icon">🏠</div>
            <h3>No listings yet</h3>
            <p>Create your first property listing to get started.</p>
            <a href="/admin/listings.php" class="admin-btn admin-btn-primary">Add Listing</a>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing): ?>
                        <tr>
                            <td>
                                <div class="admin-listing-cell">
                                    <?php if (!empty($listing['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($listing['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="admin-listing-thumb">
                                    <?php else: ?>
                                        <div class="admin-listing-thumb-placeholder">🏠</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if (!empty($listing['badge'])): ?>
                                            <span class="admin-badge"><?php echo htmlspecialchars($listing['badge'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($listing['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($listing['price'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td>
                                <?php if ($listing['featured']): ?>
                                    <span class="admin-badge admin-badge-gold">Featured</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-muted">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                            <td>
                                <div class="admin-table-actions">
                                    <a href="/admin/listings.php?id=<?php echo $listing['id']; ?>" class="admin-btn admin-btn-sm admin-btn-secondary">Edit</a>
                                    <a href="/listing.php?slug=<?php echo htmlspecialchars($listing['slug'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-ghost">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Categories Panel -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>All Categories</h2>
        <button class="admin-btn admin-btn-primary" onclick="openCategoryModal()">
            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; margin-right: 6px;">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Add New Category
        </button>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="admin-empty-state">
            <div class="admin-empty-icon">📁</div>
            <h3>No categories yet</h3>
            <p>Create categories to organize your listings.</p>
            <button class="admin-btn admin-btn-primary" onclick="openCategoryModal()">Add Category</button>
        </div>
    <?php else: ?>
        <div class="admin-category-grid">
            <?php foreach ($categories as $category): ?>
                <div class="admin-category-item">
                    <?php if (!empty($category['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($category['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>" class="admin-category-img">
                    <?php else: ?>
                        <div class="admin-category-img-placeholder">📁</div>
                    <?php endif; ?>
                    <div class="admin-category-content">
                        <h3><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="admin-category-actions">
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($category['image_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($category['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>')">Edit</button>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this category?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="tab" value="categories">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Category Form Modal -->
<div class="admin-modal" id="categoryModal">
    <div class="admin-modal-overlay" onclick="closeCategoryModal()"></div>
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h2 class="admin-modal-title" id="categoryModalTitle">Add Category</h2>
            <button class="admin-modal-close" type="button" onclick="closeCategoryModal()" aria-label="Close">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="post" class="admin-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="categories">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="categoryId" value="0">
            
            <div class="admin-modal-body">
                <div class="admin-field">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" name="name" id="categoryName" placeholder="e.g., Beachfront Properties" required>
                </div>
                
                <div class="admin-field">
                    <label>Image URL</label>
                    <input type="url" name="image_url" id="categoryImageUrl" placeholder="https://example.com/image.jpg">
                    <p class="admin-hint">Direct link to an image for this category</p>
                </div>
                
                <div class="admin-field">
                    <label>Link URL</label>
                    <input type="text" name="url" id="categoryUrl" value="#" placeholder="/listings.php?category=beachfront">
                    <p class="admin-hint">Where clicking this category should lead</p>
                </div>
            </div>
            
            <div class="admin-modal-footer">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Add Category';
    document.getElementById('categoryId').value = '0';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryImageUrl').value = '';
    document.getElementById('categoryUrl').value = '#';
    document.getElementById('categoryModal').classList.add('is-open');
}

function editCategory(id, name, imageUrl, url) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('categoryImageUrl').value = imageUrl;
    document.getElementById('categoryUrl').value = url || '#';
    document.getElementById('categoryModal').classList.add('is-open');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('is-open');
}

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCategoryModal();
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
