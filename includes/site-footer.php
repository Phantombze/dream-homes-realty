<?php
require_once __DIR__ . '/site-settings.php';

$phonePrimary = dh_site_setting('contact.phone_primary', '+501 672 8009');
$phoneSecondary = dh_site_setting('contact.phone_secondary', '+501 225 2525');
$emailMain = dh_site_setting('contact.email_main', 'info@dreamhomes-realty.com');
$address = dh_site_setting('contact.address', "9.5 Phillip Goldson Hwy,\nLadyville, Belize");

$whatsAppDigits = dh_digits(dh_site_setting('contact.whatsapp_number', '5016728009'));
$whatsAppUrl = $whatsAppDigits !== '' ? 'https://wa.me/' . $whatsAppDigits : '';

$facebookUrl = trim(dh_site_setting('social.facebook_url', 'https://www.facebook.com/dreamhomesrealty.bze'));
$instagramUrl = trim(dh_site_setting('social.instagram_url', 'https://www.instagram.com/dreamhomesrealty_bz/'));

// Footer link configuration - add, remove, or rename anything freely here
$footerLinksGeneral = [
    ['label' => 'Belize Living Blog',    'url' => '/blog'],
    ['label' => 'Join the Team',         'url' => '/careers'],
    ['label' => 'Commercial Properties', 'url' => 'https://dreamhomes-realty.com/listings.php?category=4'],
];

$footerLinksQuick = [
    ['label' => 'Homes for Sale',      'url' => 'https://dreamhomes-realty.com/listings.php?category=1'],
    ['label' => 'Appartments for Rent','url' => 'https://dreamhomes-realty.com/listings.php?category=2'],
    ['label' => 'Land for Sale',       'url' => 'https://dreamhomes-realty.com/listings.php?category=3'],
];
?>

<footer class="dh-footer">
    <div class="dh-footer-container">
        <div class="dh-footer-brand">
            <h2>Dream Homes <br><span>Realty</span></h2>
            <p class="dh-footer-desc">
                We can find you the perfect property and help you locate the home of your dreams.
            </p>
            <div class="dh-social-row">
                <?php if ($facebookUrl !== ''): ?>
                <a href="<?php echo dh_esc($facebookUrl); ?>" class="dh-social-btn" title="Facebook" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 9.2V7.3c0-.9.6-1.2 1.1-1.2h2.1V3h-2.9C11.5 3 10 4.5 10 6.9v2.3H8v3.1h2v8.7h3.5v-8.7h2.6l.5-3.1h-3.1z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($whatsAppUrl !== ''): ?>
                <a href="<?php echo dh_esc($whatsAppUrl); ?>" class="dh-social-btn" title="WhatsApp" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a9.8 9.8 0 0 0-8.4 14.9L3 22l5.3-1.6A9.8 9.8 0 1 0 12 2zm0 2a7.8 7.8 0 0 1 6.6 12.1l-.3.5a7.7 7.7 0 0 1-8.9 3.1l-.4-.2-3.1.9.9-3-.2-.4A7.8 7.8 0 0 1 12 4zm4.6 10.7c-.3-.2-1.7-.9-2-.9s-.5-.1-.7.2-.8.9-1 1.1-.4.2-.7 0a6.4 6.4 0 0 1-1.9-1.2 7.1 7.1 0 0 1-1.3-1.6c-.1-.3 0-.5.2-.6l.5-.6c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.6-1.5c-.2-.5-.4-.4-.7-.4h-.6c-.2 0-.5.1-.7.3-.2.2-.9.8-.9 2s.9 2.4 1 2.6a9.6 9.6 0 0 0 3.5 3.4c.4.2.8.4 1.1.5.5.2.9.2 1.2.1.4-.1 1.2-.5 1.4-1s.2-.9.2-1c0-.1-.3-.2-.6-.4z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($instagramUrl !== ''): ?>
                <a href="<?php echo dh_esc($instagramUrl); ?>" class="dh-social-btn" title="Instagram" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.975.975 1.246 2.242 1.308 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.975.975-2.242 1.246-3.608 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.975-.975-1.246-2.242-1.308-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608.975-.975 2.242-1.246 3.608-1.308 1.266-.058 1.646-.07 4.85-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-1.615.074-3.033.46-4.153 1.58C1.78 2.773 1.395 4.19 1.32 5.806.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.074 1.615.46 3.033 1.58 4.152 1.12 1.12 2.538 1.506 4.153 1.58C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 1.615-.074 3.033-.46 4.152-1.58 1.12-1.119 1.506-2.537 1.58-4.152.058-1.28.072-1.689.072-4.948 0-3.259-.014-3.667-.072-4.947-.074-1.615-.46-3.033-1.58-4.153-1.119-1.12-2.537-1.506-4.152-1.58C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h3 class="dh-footer-heading">General Info</h3>
            <ul class="dh-footer-links">
                <li><a href="/about.php">About Us</a></li>
                <li><a href="/listings.php">Our Properties</a></li>
                <?php foreach ($footerLinksGeneral as $link): ?>
                    <?php if ($link['url'] !== ''): ?>
                        <li><a href="<?php echo dh_esc($link['url']); ?>"><?php echo dh_esc($link['label']); ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h3 class="dh-footer-heading">Quick Links</h3>
            <ul class="dh-footer-links">
                <?php foreach ($footerLinksQuick as $link): ?>
                    <?php if ($link['url'] !== ''): ?>
                        <li><a href="<?php echo dh_esc($link['url']); ?>"><?php echo dh_esc($link['label']); ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li><a href="/contact.php">Contact Support</a></li>
                <li><a href="/admin/login.php">Agent Login</a></li>
            </ul>
        </div>

        <div>
            <h3 class="dh-footer-heading">Contacts</h3>
            <div class="dh-contact-list">
                <div class="dh-contact-item">
                    <span class="dh-contact-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7 12.8 12.8 0 0 0 .7 2.8 2 2 0 0 1-.5 2.1l-1.3 1.3a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5 12.8 12.8 0 0 0 2.8.7 2 2 0 0 1 1.7 2z"/></svg>
                    </span>
                    <div>
                        <?php $tel1 = dh_tel_href($phonePrimary); ?>
                        <?php $tel2 = dh_tel_href($phoneSecondary); ?>
                        <?php if ($tel1 !== ''): ?>
                            <a href="<?php echo dh_esc($tel1); ?>" style="color:inherit; text-decoration:none;"><?php echo dh_esc($phonePrimary); ?></a><br>
                        <?php endif; ?>
                        <?php if ($tel2 !== ''): ?>
                            <a href="<?php echo dh_esc($tel2); ?>" style="color:inherit; text-decoration:none;"><?php echo dh_esc($phoneSecondary); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dh-contact-item">
                    <span class="dh-contact-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 2v.5l-8 5-8-5V6h16zm0 12H4V9l8 5 8-5v9z"/></svg>
                    </span>
                    <a href="mailto:<?php echo dh_esc($emailMain); ?>" style="color:inherit; text-decoration:none;"><?php echo dh_esc($emailMain); ?></a>
                </div>

                <div class="dh-contact-item">
                    <span class="dh-contact-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a8 8 0 0 0-8 8c0 5.2 6.5 11.4 7.4 12.2a1 1 0 0 0 1.3 0C13.5 21.4 20 15.2 20 10a8 8 0 0 0-8-8zm0 11.3a3.3 3.3 0 1 1 0-6.6 3.3 3.3 0 0 1 0 6.6z"/></svg>
                    </span>
                    <span><?php echo dh_nl2br_esc($address); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="dh-copyright-bar">
        <span>&copy; 2026 Dream Homes Realty. All rights reserved.</span>

        <div class="dh-sitemap-links">
            <a href="/sitemap.php">Sitemap</a>
            <span>Designed by Ruan</span>
        </div>
    </div>

    <button class="dh-scroll-top" id="scrollTopBtn" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
    </button>
</footer>

<script>
    const scrollBtn = document.getElementById('scrollTopBtn');
    window.addEventListener('scroll', () => {
        if (!scrollBtn) {
            return;
        }
        if (window.scrollY > 300) {
            scrollBtn.classList.add('is-visible');
        } else {
            scrollBtn.classList.remove('is-visible');
        }
    });
</script>
