<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/site-settings.php';

if (empty($_SESSION['public_csrf_token'])) {
    $_SESSION['public_csrf_token'] = bin2hex(random_bytes(32));
}

function public_csrf_token(): string {
    if (empty($_SESSION['public_csrf_token'])) {
        $_SESSION['public_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['public_csrf_token'];
}

function public_require_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $token = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['public_csrf_token'] ?? '');
    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        die('Invalid form submission. Please refresh and try again.');
    }
}

$errors = [];
$successMessage = '';

$formValues = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'topic' => 'Buying',
    'message' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    public_require_csrf();

    $formValues['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $formValues['email'] = trim((string)($_POST['email'] ?? ''));
    $formValues['phone'] = trim((string)($_POST['phone'] ?? ''));
    $formValues['topic'] = trim((string)($_POST['topic'] ?? 'Buying'));
    $formValues['message'] = trim((string)($_POST['message'] ?? ''));

    if ($formValues['full_name'] === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($formValues['email'] === '' || !filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($formValues['message'] === '') {
        $errors[] = 'Please enter a message.';
    }
    if (!in_array($formValues['topic'], ['Buying', 'Selling', 'Renting', 'Investing', 'General'], true)) {
        $formValues['topic'] = 'General';
    }

    if (!$errors) {
        $toEmail = dh_site_setting('contact.form_to_email', '') ?: dh_site_setting('contact.email_main', 'info@dreamhomes-realty.com');
        $topic = $formValues['topic'];
        $siteHost = (string)($_SERVER['HTTP_HOST'] ?? 'dreamhomes-realty.com');
        $fromEmail = 'no-reply@' . preg_replace('/^www\./i', '', $siteHost);

        $subject = 'Website Contact: ' . $topic;
        $bodyLines = [
            'New website contact submission',
            '',
            'Name: ' . $formValues['full_name'],
            'Email: ' . $formValues['email'],
            'Phone: ' . ($formValues['phone'] !== '' ? $formValues['phone'] : '(not provided)'),
            'Topic: ' . $topic,
            '',
            'Message:',
            $formValues['message'],
            '',
            'IP: ' . (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ];
        $body = implode("\n", $bodyLines);

        $headers = [];
        $headers[] = 'From: Dream Homes Realty <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $formValues['full_name'] . ' <' . $formValues['email'] . '>';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        $sent = @mail($toEmail, $subject, $body, implode("\r\n", $headers));
        if ($sent) {
            $successMessage = 'Thanks — your message has been sent. We’ll get back to you soon.';
            $formValues['message'] = '';
        } else {
            $errors[] = 'We could not send your message right now. Please call or WhatsApp us instead.';
        }
    }
}

$phonePrimary = dh_site_setting('contact.phone_primary', '+501 672 8009');
$phoneSecondary = dh_site_setting('contact.phone_secondary', '+501 225 2525');
$emailMain = dh_site_setting('contact.email_main', 'info@dreamhomes-realty.com');
$address = dh_site_setting('contact.address', "9.5 Phillip Goldson Hwy,\nLadyville, Belize");

$whatsAppNumber = dh_site_setting('contact.whatsapp_number', '5016728009');
$whatsAppUrl = 'https://wa.me/' . dh_digits($whatsAppNumber);
$mapsQuery = dh_site_setting('contact.maps_query', 'https://maps.app.goo.gl/7LZmcT6MHBVzDbaH8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = 'Contact Us | Dream Homes Realty';
$metaDescription = 'Contact Dream Homes Realty for Belize property inquiries, listings, and concierge support.';
$canonicalUrl = $baseUrl . '/contact.php';
$ogImage = 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80';

$seoGraph = [
    [
        '@type' => 'ContactPage',
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <section class="dh-page-hero">
        <span class="dh-subtitle">Get in Touch</span>
        <h1>Contact Dream Homes Realty</h1>
        <p>Tell us what you’re looking for and our team will respond with clear next steps.</p>
    </section>

    <section class="dh-module-wrapper">
        <div class="dh-header-row">
            <div>
                <span class="dh-subtitle">Contact</span>
                <h2 class="dh-title">We’re Here to Help</h2>
            </div>
            <a href="/listings.php" class="dh-view-all">Browse Listings</a>
        </div>

        <div class="dh-grid-2">
            <div class="dh-surface">
                <?php if ($successMessage !== ''): ?>
                    <div class="dh-alert dh-alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="dh-alert dh-alert-error">
                        <?php echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <h3 style="margin-bottom: 10px;">Send a Message</h3>
                <p class="dh-help" style="margin-top: 0;">Share the basics and we’ll route you to the right agent.</p>

                <form class="dh-form" method="post" action="/contact.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(public_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                    <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                        <label>Company</label>
                        <input class="dh-input" type="text" name="company" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="dh-field-row">
                        <div>
                            <label class="dh-label" for="full_name">Full Name</label>
                            <input class="dh-input" id="full_name" name="full_name" type="text" autocomplete="name" value="<?php echo htmlspecialchars($formValues['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div>
                            <label class="dh-label" for="email">Email</label>
                            <input class="dh-input" id="email" name="email" type="email" autocomplete="email" value="<?php echo htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>

                    <div class="dh-field-row">
                        <div>
                            <label class="dh-label" for="phone">Phone (optional)</label>
                            <input class="dh-input" id="phone" name="phone" type="tel" autocomplete="tel" value="<?php echo htmlspecialchars($formValues['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label class="dh-label" for="topic">Topic</label>
                            <select class="dh-select" id="topic" name="topic">
                                <?php
                                $topics = ['Buying', 'Selling', 'Renting', 'Investing', 'General'];
                                foreach ($topics as $topicOption) {
                                    $selected = $formValues['topic'] === $topicOption ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($topicOption, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($topicOption, ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="dh-label" for="message">Message</label>
                        <textarea class="dh-textarea" id="message" name="message" rows="6" required><?php echo htmlspecialchars($formValues['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <button class="dh-btn-primary" type="submit">Send Message</button>
                </form>
            </div>

            <div style="display:flex; flex-direction:column; gap: 18px; justify-content: flex-end; height: 100%;">
                <div class="dh-surface">
                    <h3 style="margin-bottom: 10px;">Direct Contact</h3>
                    <div class="dh-mini-card" style="margin-bottom: 12px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7 12.8 12.8 0 0 0 .7 2.8 2 2 0 0 1-.5 2.1l-1.3 1.3a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5 12.8 12.8 0 0 0 2.8.7 2 2 0 0 1 1.7 2z"/></svg>
                        <div>
                            <strong>Phone</strong><br>
                            <a href="tel:<?php echo preg_replace('/\D+/', '', $phonePrimary); ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($phonePrimary, ENT_QUOTES, 'UTF-8'); ?></a><br>
                            <a href="tel:<?php echo preg_replace('/\D+/', '', $phoneSecondary); ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($phoneSecondary, ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                    <div class="dh-mini-card" style="margin-bottom: 12px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 2v.5l-8 5-8-5V6h16zm0 12H4V9l8 5 8-5v9z"/></svg>
                        <div>
                            <strong>Email</strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($emailMain, ENT_QUOTES, 'UTF-8'); ?>" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars($emailMain, ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                    <div class="dh-mini-card">
                        <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a8 8 0 0 0-8 8c0 5.2 6.5 11.4 7.4 12.2a1 1 0 0 0 1.3 0C13.5 21.4 20 15.2 20 10a8 8 0 0 0-8-8zm0 11.3a3.3 3.3 0 1 1 0-6.6 3.3 3.3 0 0 1 0 6.6z"/></svg>
                        <div>
                            <strong>Office</strong><br>
                            <?php echo nl2br(htmlspecialchars($address, ENT_QUOTES, 'UTF-8')); ?><br>
                            <a href="https://www.google.com/maps?q=<?php echo urlencode($mapsQuery); ?>" target="_blank" rel="noopener" style="color:var(--dh-navy); text-decoration:none; font-weight:600;">Open in Maps →</a>
                        </div>
                    </div>
                </div>

                <div class="dh-surface">
                    <h3 style="margin-bottom: 10px;">Office Hours</h3>
                    <p class="dh-help" style="margin: 0;">
                        Monday–Friday: 9:00am–5:00pm<br>
                        Saturday: By appointment<br>
                        Sunday: Closed
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="dh-module-wrapper" style="padding-top: 0;">
        <div class="dh-header-row">
            <div>
                <span class="dh-subtitle">Location</span>
                <h2 class="dh-title">Visit Our Office</h2>
            </div>
        </div>
        <iframe
            class="dh-map-frame"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4101.2908777493!2d-88.30295149999999!3d17.5506209!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f5c5b698a4e6855%3A0x426f577ee6f57e9!2sMedina%27s%20Material!5e1!3m2!1sen!2sbz!4v1770929476725!5m2!1sen!2sbz"
            width="600"
            height="450"
            style="border:0;"
            allowfullscreen="">
        </iframe>
    </section>

    <section class="dh-module-wrapper" style="padding-top: 0;">
        <div class="dh-contact-section">
            <div class="dh-cta-text">
                <span class="dh-subtitle" style="color: rgba(255,255,255,0.7); margin-bottom:10px;">Concierge Service</span>
                <h2>Prefer to Talk?</h2>
                <p>Call, WhatsApp, or send a message. We’ll help you get clarity quickly.</p>
            </div>
            <div class="dh-cta-buttons">
                <a href="tel:<?php echo preg_replace('/\D+/', '', $phonePrimary); ?>" class="dh-btn-primary">Call Now</a>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
