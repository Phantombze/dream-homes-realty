<?php
require_once __DIR__ . '/includes/site-settings.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = 'About Us | Dream Homes Realty';
$metaDescription = 'Learn about Dream Homes Realty and our Belize real estate team. Local insight, clear guidance, and concierge service.';
$canonicalUrl = $baseUrl . '/about.php';
$ogImage = 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1200&q=80';
$communityImage = dh_site_setting('about.community_image', 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1200&q=80');

$seoGraph = [
    [
        '@type' => 'AboutPage',
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
    <link rel="stylesheet" href="/assets/css/site.css">
    <script type="application/ld+json"><?php echo $seoJson; ?></script>
</head>
<body>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main id="main-content">
    <section class="dh-page-hero">
        <span class="dh-subtitle">Welcome to Dream Homes</span>
        <h1>Who We Are</h1>
        <p>Dream Homes Realty helps clients find, buy, sell, and rent quality properties throughout Belize with a process that is clear, practical, and fully supported.</p>
    </section>

    <!-- Mission/Goals/Values Cards -->
    <section class="dh-about-cards dh-reveal">
        <div class="dh-module-wrapper">
            <div class="dh-cards-grid">
                <div class="dh-about-card dh-reveal">
                    <div class="dh-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </div>
                    <h3>Our Mission</h3>
                    <p>From lots to move-in-ready homes, we help clients identify the best-fit property with integrity, local expertise, and personalized guidance.</p>
                </div>

                <div class="dh-about-card dh-reveal">
                    <div class="dh-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6zm3.6 8h-3.36l-.4-2H7V6h5.36l.4 2H18v6z"/>
                        </svg>
                    </div>
                    <h3>Our Goals</h3>
                    <p>Straight answers, realistic timelines, and a structured process from start to finish. Clear communication at every stage of your journey.</p>
                </div>

                <div class="dh-about-card dh-reveal">
                    <div class="dh-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <h3>Our Values</h3>
                    <p>We help buyers and investors evaluate options with both present needs and future goals in mind. Smart decisions for long-term value.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Focus Section (Navy Background) -->
    <section class="dh-about-community dh-reveal">
        <div class="dh-module-wrapper">
            <div class="dh-community-header">
                <span class="dh-about-subtitle">Beyond Real Estate</span>
                <h2>Our Heart for the Community</h2>
                <div class="dh-divider"></div>
            </div>

            <div class="dh-community-row dh-reveal">
                <div class="dh-community-image">
                    <img src="<?php echo htmlspecialchars($communityImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Dream Homes Realty supporting the Ladyville Children's Home">
                </div>
                <div class="dh-community-text">
                    <span class="dh-about-subtitle">Giving Back</span>
                    <h3>Supporting the Ladyville Children's Home</h3>
                    <p>Our work is not only about property. We are committed to giving back, including ongoing support for the Ladyville Children's Home and community-centered initiatives.</p>
                    <p>By choosing Dream Homes Realty, clients partner with a team that believes business success should create positive social impact in Belize.</p>
                </div>
            </div>

            <div class="dh-community-row reverse dh-reveal">
                <div class="dh-community-image">
                    <img src="https://images.unsplash.com/photo-1526976668912-1a811878dd37?auto=format&fit=crop&q=80&w=1000" alt="Youth Programs and Opportunities">
                </div>
                <div class="dh-community-text">
                    <span class="dh-about-subtitle">Youth Empowerment</span>
                    <h3>Supporting Programs & Opportunities</h3>
                    <p>Support for programs and opportunities that invest in Belize's next generation. We actively support local initiatives that strengthen families and neighborhoods.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="dh-about-services dh-reveal">
        <div class="dh-module-wrapper">
            <div class="dh-services-header">
                <span class="dh-about-subtitle">What We Do</span>
                <h2>Services and Community Commitment</h2>
            </div>

            <div class="dh-services-grid">
                <div class="dh-service-item dh-reveal">
                    <div class="dh-service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                    </div>
                    <h4>Property Sales</h4>
                </div>

                <div class="dh-service-item dh-reveal">
                    <div class="dh-service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M21 10h-8.35C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H13l2 2 2-2 2 2 2-2v-2zm-14 4c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <h4>Home Rentals</h4>
                </div>

                <div class="dh-service-item dh-reveal">
                    <div class="dh-service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <h4>Community Support</h4>
                </div>

                <div class="dh-service-item dh-reveal">
                    <div class="dh-service-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </div>
                    <h4>Youth Engagement</h4>
                </div>
            </div>
        </div>
    </section>

    <section class="dh-module-wrapper dh-about-section">
        <div class="dh-contact-section">
            <div class="dh-cta-text">
                <span class="dh-subtitle" style="color: rgba(255,255,255,0.7); margin-bottom:10px;">Next Steps</span>
                <h2>Let’s Find the Right Property in Belize</h2>
                <p>Browse available listings or speak with our team for personalized guidance.</p>
            </div>
            <div class="dh-cta-buttons">
                <a href="/listings.php" class="dh-btn-primary">View Listings</a>
                <a href="/contact.php" class="dh-btn-outline">Contact Us</a>
            </div>
        </div>
    </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const revealElements = document.querySelectorAll('.dh-reveal');

        if ('IntersectionObserver' in window) {
            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('dh-active');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: "0px 0px -50px 0px"
            });

            revealElements.forEach(el => revealObserver.observe(el));
        } else {
            // Fallback for older browsers
            revealElements.forEach(el => el.classList.add('dh-active'));
        }
    });
    </script>
</body>
</html>
