<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

require_once __DIR__ . '/../includes/site-settings.php';
dh_ensure_site_settings_table();

$message = '';
$messageType = 'success';

function normalize_setting_value($value): ?string {
    if (!is_string($value) && $value !== null) {
        return null;
    }
    $value = $value === null ? null : trim($value);
    if ($value === '') {
        return null;
    }
    return $value;
}

function post_setting_value(string $key): ?string {
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }
    $normalized = str_replace('.', '_', $key);
    return $_POST[$normalized] ?? null;
}

$fields = [
    // Contact
    'contact.email_main',
    'contact.form_to_email',
    'contact.phone_primary',
    'contact.phone_secondary',
    'contact.whatsapp_number',
    'contact.address',
    'contact.maps_query',

    // Social
    'social.facebook_url',
    'social.instagram_url',

    // Homepage socials
    'home.social_1_name',
    'home.social_1_url',
    'home.social_2_name',
    'home.social_2_url',
    'home.social_3_name',
    'home.social_3_url',

    // Homepage hero images
    'home.hero_image_1',
    'home.hero_image_2',
    'home.hero_image_3',

    // About page
    'about.community_image',
    'about.youth_image',

    // Agent
    'agent.name',
    'agent.phone',
    'agent.email',
    'agent.photo',

    // Security - Cloudflare Turnstile
    'security.turnstile_site_key',
    'security.turnstile_enabled',

    // Brevo email delivery
    'brevo.from_email',
    'brevo.from_name',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/bootstrap.php';
    require_csrf();

    try {
        // Handle logo upload
        if (isset($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
            $logoPath = __DIR__ . '/../assets/img/logo.png';
            if (file_exists($logoPath)) {
                unlink($logoPath);
                $message = 'Logo deleted successfully.';
                $messageType = 'success';
            }
        }

        // Handle favicon upload/delete
        if (isset($_POST['delete_favicon']) && $_POST['delete_favicon'] === '1') {
            $faviconIco = __DIR__ . '/../assets/img/favicon.ico';
            $faviconPng = __DIR__ . '/../assets/img/favicon.png';
            if (file_exists($faviconIco)) {
                unlink($faviconIco);
            }
            if (file_exists($faviconPng)) {
                unlink($faviconPng);
            }
            if ($message === '') {
                $message = 'Favicon deleted successfully.';
                $messageType = 'success';
            }
        } elseif (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Please upload PNG, JPG, GIF, or SVG.');
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum size is 2MB.');
            }
            
            $uploadDir = __DIR__ . '/../assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Always save as logo.png (overwrite existing)
            $destination = $uploadDir . 'logo.png';
            
            // Delete old logo if exists
            if (file_exists($destination)) {
                unlink($destination);
            }
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $message = 'Logo uploaded successfully.';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to save logo file.');
            }
        } elseif (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_favicon'];
            $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];
            $maxSize = 512 * 1024; // 512KB
            $originalName = strtolower((string) ($file['name'] ?? ''));
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);

            if (!in_array($extension, ['ico', 'png'], true)) {
                throw new Exception('Invalid favicon format. Please upload .ico or .png.');
            }

            if (!in_array($file['type'], $allowedTypes, true)) {
                throw new Exception('Invalid favicon file type. Please upload .ico or .png.');
            }

            if ($file['size'] > $maxSize) {
                throw new Exception('Favicon file too large. Maximum size is 512KB.');
            }

            $uploadDir = __DIR__ . '/../assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destination = $uploadDir . 'favicon.' . $extension;
            if (file_exists($destination)) {
                unlink($destination);
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if ($message === '') {
                    $message = 'Favicon uploaded successfully.';
                    $messageType = 'success';
                }
            } else {
                throw new Exception('Failed to save favicon file.');
            }
        }
        
        // Handle regular settings
        $postedValues = [];
        foreach ($fields as $key) {
            $val = post_setting_value($key);
            $normalized = normalize_setting_value($val);
            $postedValues[$key] = $normalized;
            dh_set_site_setting($key, $normalized);
        }

        $verificationErrors = [];
        if ($postedValues !== []) {
            $placeholders = implode(',', array_fill(0, count($postedValues), '?'));
            $stmt = db()->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
            $stmt->execute(array_keys($postedValues));
            $rows = $stmt->fetchAll();
            $savedMap = [];
            foreach ($rows as $row) {
                $savedMap[$row['setting_key']] = $row['setting_value'] ?? null;
            }

            foreach ($postedValues as $key => $expected) {
                $saved = $savedMap[$key] ?? null;
                $expectedNormalized = $expected === null ? null : (string) $expected;
                $savedNormalized = $saved === null ? null : (string) $saved;
                if ($expectedNormalized !== $savedNormalized) {
                    $verificationErrors[] = $key;
                }
            }
        }
        
        // Clear cache to reflect newly saved values
        $GLOBALS['dh_site_settings_cache'] = [];
        
        if ($message === '') {
            if ($verificationErrors) {
                $message = 'Settings saved, but some values could not be verified. Check DB permissions for site_settings. Keys: ' . implode(', ', $verificationErrors);
                $messageType = 'error';
            } else {
                $message = 'Settings saved.';
                $messageType = 'success';
            }
        }
    } catch (Throwable $e) {
        $message = 'Failed to save settings: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function s(string $key, string $default = ''): string {
    return dh_site_setting($key, $default);
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="admin-header">
    <div>
        <h1 class="admin-title">Settings</h1>
        <p class="admin-subtitle">Configure your website settings</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="admin-notice <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form admin-settings-form">
    <?php echo csrf_field(); ?>
    
    <div class="admin-settings-grid">
            
        <!-- Branding Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
                    Branding
                </h2>
            </div>
            <div class="admin-card-body">
                <?php
                $logoPath = __DIR__ . '/../assets/img/logo.png';
                $logoUrl = '/assets/img/logo.png?v=' . time();
                $hasLogo = file_exists($logoPath);
                $faviconIcoPath = __DIR__ . '/../assets/img/favicon.ico';
                $faviconPngPath = __DIR__ . '/../assets/img/favicon.png';
                $hasFaviconIco = file_exists($faviconIcoPath);
                $hasFaviconPng = file_exists($faviconPngPath);
                $faviconIcoUrl = '/assets/img/favicon.ico?v=' . time();
                $faviconPngUrl = '/assets/img/favicon.png?v=' . time();
                ?>
                
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Site Logo</label>
                        <?php if ($hasLogo): ?>
                            <div class="admin-media-preview">
                                <img src="<?php echo $logoUrl; ?>" alt="Site Logo" style="max-width: 180px; max-height: 50px; background: var(--admin-navy); padding: 10px; border-radius: 6px;">
                            </div>
                            <label class="admin-checkbox-label">
                                <input type="checkbox" name="delete_logo" value="1"> Delete current logo
                            </label>
                        <?php endif; ?>
                        <input type="file" name="site_logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml">
                        <p class="admin-hint">PNG, JPG, GIF, or SVG. Max 2MB.</p>
                    </div>
                    <div class="admin-field">
                        <label>Favicon</label>
                        <?php if ($hasFaviconIco || $hasFaviconPng): ?>
                            <div class="admin-media-preview" style="display: flex; gap: 8px; align-items: center;">
                                <?php if ($hasFaviconIco): ?>
                                    <img src="<?php echo $faviconIcoUrl; ?>" alt="Favicon" style="width: 32px; height: 32px; background: #fff; padding: 4px; border-radius: 4px; border: 1px solid var(--admin-border);">
                                <?php endif; ?>
                                <?php if ($hasFaviconPng): ?>
                                    <img src="<?php echo $faviconPngUrl; ?>" alt="Favicon" style="width: 32px; height: 32px; background: #fff; padding: 4px; border-radius: 4px; border: 1px solid var(--admin-border);">
                                <?php endif; ?>
                            </div>
                            <label class="admin-checkbox-label">
                                <input type="checkbox" name="delete_favicon" value="1"> Delete favicon
                            </label>
                        <?php endif; ?>
                        <input type="file" name="site_favicon" accept="image/x-icon,image/vnd.microsoft.icon,image/png">
                        <p class="admin-hint">.ico or .png. Max 512KB.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Details Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Contact Details
                </h2>
            </div>
            <div class="admin-card-body">
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Primary Email</label>
                        <input type="email" name="contact.email_main" value="<?php echo htmlspecialchars(s('contact.email_main', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="info@example.com">
                    </div>
                    <div class="admin-field">
                        <label>Contact Form Recipient</label>
                        <input type="email" name="contact.form_to_email" value="<?php echo htmlspecialchars(s('contact.form_to_email', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="inquiries@example.com">
                    </div>
                </div>
                
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Primary Phone</label>
                        <input type="text" name="contact.phone_primary" value="<?php echo htmlspecialchars(s('contact.phone_primary', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+1 234 567 8900">
                    </div>
                    <div class="admin-field">
                        <label>Secondary Phone</label>
                        <input type="text" name="contact.phone_secondary" value="<?php echo htmlspecialchars(s('contact.phone_secondary', ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>WhatsApp Number</label>
                        <input type="text" name="contact.whatsapp_number" value="<?php echo htmlspecialchars(s('contact.whatsapp_number', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Digits only">
                    </div>
                    <div class="admin-field">
                        <label>Maps Query</label>
                        <input type="text" name="contact.maps_query" value="<?php echo htmlspecialchars(s('contact.maps_query', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Address for map embed">
                    </div>
                </div>
                
                <div class="admin-field">
                    <label>Office Address</label>
                    <textarea name="contact.address" rows="2" placeholder="Street, City, Country"><?php echo htmlspecialchars(s('contact.address', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Agent & Social Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
                    Agent & Social
                </h2>
            </div>
            <div class="admin-card-body">
                <h4 class="admin-subsection-title">Default Agent Info</h4>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Agent Name</label>
                        <input type="text" name="agent.name" value="<?php echo htmlspecialchars(s('agent.name', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Agent or Company Name">
                    </div>
                    <div class="admin-field">
                        <label>Agent Phone</label>
                        <input type="text" name="agent.phone" value="<?php echo htmlspecialchars(s('agent.phone', ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="admin-field">
                    <label>Agent Email</label>
                    <input type="email" name="agent.email" value="<?php echo htmlspecialchars(s('agent.email', ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="admin-field">
                    <label>Agent Profile Photo URL</label>
                    <input type="url" name="agent.photo" value="<?php echo htmlspecialchars(s('agent.photo', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    <p class="admin-hint">Profile picture shown on listing pages. Leave blank for default avatar.</p>
                </div>

                <h4 class="admin-subsection-title">Social Media Links</h4>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Facebook URL</label>
                        <input type="url" name="social.facebook_url" value="<?php echo htmlspecialchars(s('social.facebook_url', 'https://www.facebook.com/dreamhomesrealty.bze'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="admin-field">
                        <label>Instagram URL</label>
                        <input type="url" name="social.instagram_url" value="<?php echo htmlspecialchars(s('social.instagram_url', 'https://www.instagram.com/dreamhomesrealty_bz/'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://instagram.com/...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Homepage Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Homepage
                </h2>
            </div>
            <div class="admin-card-body">
                <h4 class="admin-subsection-title">Hero Social Links</h4>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Social 1</label>
                        <input type="text" name="home.social_1_name" value="<?php echo htmlspecialchars(s('home.social_1_name', 'Instagram'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name">
                    </div>
                    <div class="admin-field">
                        <label>URL</label>
                        <input type="url" name="home.social_1_url" value="<?php echo htmlspecialchars(s('home.social_1_url', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    </div>
                </div>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Social 2</label>
                        <input type="text" name="home.social_2_name" value="<?php echo htmlspecialchars(s('home.social_2_name', 'Facebook'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name">
                    </div>
                    <div class="admin-field">
                        <label>URL</label>
                        <input type="url" name="home.social_2_url" value="<?php echo htmlspecialchars(s('home.social_2_url', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    </div>
                </div>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Social 3</label>
                        <input type="text" name="home.social_3_name" value="<?php echo htmlspecialchars(s('home.social_3_name', 'LinkedIn'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name">
                    </div>
                    <div class="admin-field">
                        <label>URL</label>
                        <input type="url" name="home.social_3_url" value="<?php echo htmlspecialchars(s('home.social_3_url', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    </div>
                </div>
                
                <h4 class="admin-subsection-title">Hero Images</h4>
                <div class="admin-field">
                    <label>Hero Image 1</label>
                    <input type="url" name="home.hero_image_1" value="<?php echo htmlspecialchars(s('home.hero_image_1', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                </div>
                <div class="admin-field">
                    <label>Hero Image 2</label>
                    <input type="url" name="home.hero_image_2" value="<?php echo htmlspecialchars(s('home.hero_image_2', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                </div>
                <div class="admin-field">
                    <label>Hero Image 3</label>
                    <input type="url" name="home.hero_image_3" value="<?php echo htmlspecialchars(s('home.hero_image_3', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                </div>
                <p class="admin-hint">Leave blank to use default images.</p>

                <h4 class="admin-subsection-title">About Page</h4>
                <div class="admin-field">
                    <label>Community Support Image URL</label>
                    <input type="url" name="about.community_image" value="<?php echo htmlspecialchars(s('about.community_image', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    <p class="admin-hint">Image shown in the About page community section (Ladyville Children's Home).</p>
                </div>
                <div class="admin-field">
                    <label>Youth Programs Image URL</label>
                    <input type="url" name="about.youth_image" value="<?php echo htmlspecialchars(s('about.youth_image', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://...">
                    <p class="admin-hint">Image shown in the About page youth empowerment section.</p>
                </div>
            </div>
        </div>
        
        <!-- Security Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Security
                </h2>
            </div>
            <div class="admin-card-body">
                <h4 class="admin-subsection-title">Cloudflare Turnstile</h4>
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>Status</label>
                        <?php $turnstileEnabled = s('security.turnstile_enabled', ''); ?>
                        <select name="security.turnstile_enabled">
                            <option value="" <?php echo $turnstileEnabled === '' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo $turnstileEnabled === '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label>Site Key</label>
                        <input type="text" name="security.turnstile_site_key" value="<?php echo htmlspecialchars(s('security.turnstile_site_key', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="0x4AAAAAAA...">
                    </div>
                </div>
                <div class="admin-info-box">
                    Secret key is stored in backend for security.
                </div>
            </div>
        </div>
        
        <!-- Email Delivery Card -->
        <div class="admin-card admin-settings-card">
            <div class="admin-card-header">
                <h2>
                    <svg viewBox="0 0 24 24" class="admin-card-icon"><path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6zm-2 0l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/></svg>
                    Email Delivery
                </h2>
                <span class="admin-badge admin-badge-success">Brevo</span>
            </div>
            <div class="admin-card-body">
                <div class="admin-grid-2">
                    <div class="admin-field">
                        <label>From Email</label>
                        <input type="email" name="brevo.from_email" value="<?php echo htmlspecialchars(s('brevo.from_email', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="info@dreamhomes-realty.com">
                        <p class="admin-hint">Must match a verified sender in your Brevo account.</p>
                    </div>
                    <div class="admin-field">
                        <label>From Name</label>
                        <input type="text" name="brevo.from_name" value="<?php echo htmlspecialchars(s('brevo.from_name', 'Dream Homes Realty'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Dream Homes Realty">
                    </div>
                </div>
                <div class="admin-info-box">
                    Contact form replies go to the <strong>Contact Form Recipient</strong> email set above.
                </div>
            </div>
        </div>
            
    </div>
    
    <!-- Sticky Save Bar -->
    <div class="admin-save-bar">
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; margin-right: 8px;">
                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            Save All Settings
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
