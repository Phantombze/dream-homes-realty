<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

$editing = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
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
                $message = 'Category updated.';
            } else {
                $stmt = db()->prepare('INSERT INTO categories (name, image_url, url) VALUES (:name, :image_url, :url)');
                $stmt->execute([
                    'name' => $name,
                    'image_url' => $imageUrl,
                    'url' => $url,
                ]);
                $message = 'Category added.';
            }
        } else {
            $message = 'Category name is required.';
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

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch();
}

$categories = db()->query('SELECT * FROM categories ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-header">
    <h1 class="admin-title">Categories</h1>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-card" style="margin-bottom: 16px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom: 24px;">
    <h2><?php echo $editing ? 'Edit Category' : 'Add Category'; ?></h2>
    <form method="post" class="admin-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo $editing['id'] ?? 0; ?>">

        <div class="admin-grid">
            <div>
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required>
            </div>
            <div>
                <label>Image URL</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>">
            </div>
            <div>
                <label>Category URL</label>
                <input type="text" name="url" value="<?php echo htmlspecialchars($editing['url'] ?? '#'); ?>">
            </div>
        </div>

        <div class="admin-actions">
            <button type="submit">Save Category</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <h2>All Categories</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo htmlspecialchars($category['url']); ?></td>
                    <td>
                        <a href="/admin/categories.php?edit=<?php echo (int) $category['id']; ?>">Edit</a>
                        <form method="post" style="display:inline; margin-left:10px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
                            <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
