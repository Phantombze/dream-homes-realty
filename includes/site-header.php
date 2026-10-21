<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdminSession = !empty($_SESSION['admin_email']);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if ($currentPath === '/') {
    $currentPath = '/index.php';
}
$logoPath = __DIR__ . '/../assets/img/logo.png';
$logoUrl = '/assets/img/logo.png';
$hasLogo = file_exists($logoPath);
function nav_active(string $path, string $currentPath): string {
    return $currentPath === $path ? 'is-active' : '';
}
?>
<?php if ($isAdminSession): ?>
<div class="dh-admin-bar">
    <div class="dh-admin-bar-inner">
        <span class="dh-admin-label">Admin Mode</span>
        <div class="dh-admin-links">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/listings.php">Listings</a>
            <a href="/admin/settings.php">Settings</a>
            <a href="/admin/logout.php">Sign Out</a>
        </div>
    </div>
</div>
<?php endif; ?>
<nav class="dh-nav <?php echo $isAdminSession ? 'dh-nav-with-admin' : ''; ?>">
    <a class="dh-nav-logo" href="/index.php">
        <?php if ($hasLogo): ?>
            <img class="dh-logo-img" src="<?php echo $logoUrl; ?>" alt="Dream Homes logo">
        <?php else: ?>
            <span class="dh-logo-text">Dream Homes <span>Realty</span></span>
        <?php endif; ?>
    </a>
    <div class="dh-nav-links" id="dhNavLinks">
        <a class="<?php echo nav_active('/index.php', $currentPath); ?>" href="/index.php">Home</a>
        <a class="<?php echo nav_active('/listings.php', $currentPath); ?>" href="/listings.php">Listings</a>
        <a class="<?php echo nav_active('/about.php', $currentPath); ?>" href="/about.php">About Us</a>
        <a class="<?php echo nav_active('/contact.php', $currentPath); ?>" href="/contact.php">Contact</a>
    </div>
    <button class="dh-nav-toggle" type="button" aria-expanded="false" aria-controls="dhNavLinks" aria-label="Toggle menu">
        <span class="dh-burger">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>
</nav>
<div class="dh-nav-overlay" id="dhNavOverlay"></div>

<script>
    (function () {
        const toggle = document.querySelector('.dh-nav-toggle');
        const links = document.getElementById('dhNavLinks');
        const overlay = document.getElementById('dhNavOverlay');
        const body = document.body;
        if (!toggle || !links) {
            return;
        }
        function closeMenu() {
            links.classList.remove('is-open');
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            body.classList.remove('dh-nav-open');
            if (overlay) overlay.classList.remove('is-visible');
        }
        function openMenu() {
            links.classList.add('is-open');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            body.classList.add('dh-nav-open');
            if (overlay) overlay.classList.add('is-visible');
        }
        toggle.addEventListener('click', () => {
            const isOpen = links.classList.contains('is-open');
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        links.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    })();
</script>
