<?php
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION[ADMIN_SESSION_KEY])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$csrfToken = csrf_token();
$signOut = isset($_GET['signout']);
$signOutReturn = $_GET['return'] ?? '/index.php';
if (!is_string($signOutReturn) || $signOutReturn === '' || $signOutReturn[0] !== '/') {
    $signOutReturn = '/index.php';
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$returnUrl = $scheme . '://' . $host . '/admin/login.php';
$clerkSignInUrl = clerk_hosted_sign_in_url() . '?redirect_url=' . rawurlencode($returnUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <?php
    $faviconIcoPath = __DIR__ . '/../assets/img/favicon.ico';
    $faviconPngPath = __DIR__ . '/../assets/img/favicon.png';
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
    <link rel="stylesheet" href="/assets/css/admin.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <div class="admin-login">
        <div class="admin-login-card">
            <h1>Admin Login</h1>
            <p>Access the Dream Homes control panel.</p>
            <div id="clerk-error" class="admin-error" style="display:none;"></div>
            <div class="admin-actions">
                <a href="<?php echo htmlspecialchars($clerkSignInUrl, ENT_QUOTES, 'UTF-8'); ?>">Continue with Clerk</a>
            </div>
        </div>
    </div>
    <script
        id="clerk-js"
        src="https://clerk.panelbz.cloud/npm/@clerk/clerk-js@5/dist/clerk.browser.js"
        data-clerk-publishable-key="<?php echo htmlspecialchars(CLERK_PUBLISHABLE_KEY, ENT_QUOTES, 'UTF-8'); ?>">
    </script>
    <script>
        const clerkScript = document.getElementById('clerk-js');

        function ensureClerkLoaded() {
            return new Promise((resolve, reject) => {
                if (window.Clerk) {
                    resolve();
                    return;
                }

                clerkScript.addEventListener('load', () => resolve());
                clerkScript.addEventListener('error', () => reject(new Error('Unable to load Clerk script.')));
            });
        }
    </script>
    <script>
        const signOut = <?php echo $signOut ? 'true' : 'false'; ?>;
        const clerkSignInUrl = <?php echo json_encode($clerkSignInUrl); ?>;
        const signOutReturn = <?php echo json_encode($signOutReturn); ?>;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const errorBox = document.getElementById('clerk-error');

        async function exchangeSession() {
            try {
                const token = await Clerk.session.getToken();
                const response = await fetch('/admin/clerk-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify({ token }),
                });

                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text || 'Unable to sign in.');
                }

                window.location.href = '/admin/dashboard.php';
            } catch (error) {
                errorBox.textContent = error.message || 'Unable to sign in.';
                errorBox.style.display = 'block';
            }
        }

        ensureClerkLoaded()
            .then(() => Clerk.load())
            .then(async () => {
                if (signOut) {
                    await Clerk.signOut();
                    window.location.href = signOutReturn;
                    return;
                }

                if (Clerk.user && Clerk.session) {
                    exchangeSession();
                    return;
                }

                window.location.href = clerkSignInUrl;
            })
            .catch((error) => {
                errorBox.textContent = error.message || 'Unable to load sign-in.';
                errorBox.style.display = 'block';
            });
    </script>
</body>
</html>
