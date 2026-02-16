<?php
require_once __DIR__ . '/site-settings.php';

$phonePrimary = dh_site_setting('contact.phone_primary', '+501 672 8009');
$phoneSecondary = dh_site_setting('contact.phone_secondary', '+501 225 2525');
$emailMain = dh_site_setting('contact.email_main', 'info@dreamhomes-realty.com');
$address = dh_site_setting('contact.address', "9.5 Phillip Goldson Hwy,\nLadyville, Belize");

$whatsAppDigits = dh_digits(dh_site_setting('contact.whatsapp_number', '5016728009'));
$whatsAppUrl = $whatsAppDigits !== '' ? 'https://wa.me/' . $whatsAppDigits : '';

$facebookUrl = trim(dh_site_setting('social.facebook_url', ''));
$youtubeUrl = trim(dh_site_setting('social.youtube_url', ''));

// Footer link configuration - managed in code
$footerLinks = [
    'blog' => [
        'label' => 'Belize Living Blog',
        'url' => '/blog',
    ],
    'careers' => [
        'label' => 'Join the Team',
        'url' => '/careers',
    ],
    'valuation' => [
        'label' => 'Free Valuation',
        'url' => '/valuation',
    ],
    'buyers_guide' => [
        'label' => 'Buyers Guide',
        'url' => '/buyers-guide',
    ],
    'sellers_guide' => [
        'label' => 'Sellers Guide',
        'url' => '/sellers-guide',
    ],
    'resources' => [
        'label' => 'Resources',
        'url' => '/resources',
    ],
];
?>

<footer class="dh-footer">
    <div class="dh-footer-container">
        <div class="dh-footer-brand">
            <h2>Dream Homes <br><span>Realty</span></h2>
            <p class="dh-footer-desc">
                We can find you the perfect property and help you locate the home of your dreams. Trusted by buyers and sellers across Belize.
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
                <?php if ($youtubeUrl !== ''): ?>
                <a href="<?php echo dh_esc($youtubeUrl); ?>" class="dh-social-btn" title="YouTube" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 7.4a2.7 2.7 0 0 0-1.9-1.9C18.5 5 12 5 12 5s-6.5 0-8.1.5a2.7 2.7 0 0 0-1.9 1.9A28.7 28.7 0 0 0 2 12a28.7 28.7 0 0 0 .5 4.6 2.7 2.7 0 0 0 1.9 1.9c1.6.5 8.1.5 8.1.5s6.5 0 8.1-.5a2.7 2.7 0 0 0 1.9-1.9A28.7 28.7 0 0 0 22 12a28.7 28.7 0 0 0-.5-4.6zM10 15.3V8.7L15.7 12 10 15.3z"/></svg>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h3 class="dh-footer-heading">General Info</h3>
            <ul class="dh-footer-links">
                <li><a href="/about.php">About Us</a></li>
                <li><a href="/listings.php">Our Properties</a></li>
                <?php if ($footerLinks['blog']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['blog']['url']); ?>"><?php echo dh_esc($footerLinks['blog']['label']); ?></a></li>
                <?php endif; ?>
                <?php if ($footerLinks['careers']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['careers']['url']); ?>"><?php echo dh_esc($footerLinks['careers']['label']); ?></a></li>
                <?php endif; ?>
                <?php if ($footerLinks['resources']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['resources']['url']); ?>"><?php echo dh_esc($footerLinks['resources']['label']); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div>
            <h3 class="dh-footer-heading">Quick Links</h3>
            <ul class="dh-footer-links">
                <?php if ($footerLinks['valuation']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['valuation']['url']); ?>"><?php echo dh_esc($footerLinks['valuation']['label']); ?></a></li>
                <?php endif; ?>
                <?php if ($footerLinks['buyers_guide']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['buyers_guide']['url']); ?>"><?php echo dh_esc($footerLinks['buyers_guide']['label']); ?></a></li>
                <?php endif; ?>
                <?php if ($footerLinks['sellers_guide']['url'] !== ''): ?>
                    <li><a href="<?php echo dh_esc($footerLinks['sellers_guide']['url']); ?>"><?php echo dh_esc($footerLinks['sellers_guide']['label']); ?></a></li>
                <?php endif; ?>
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
