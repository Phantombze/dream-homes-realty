<?php
require_once __DIR__ . '/includes/site-settings.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = 'Dream Homes Realty | Belize Real Estate';
$metaDescription = 'Belize real estate listings, luxury homes, and curated collections from Dream Homes Realty.';
$canonicalUrl = $baseUrl . '/index.php';
$defaultHeroImage = 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1920&q=80';

$homePhone = dh_site_setting('contact.phone_primary', '+501 672 8009');
$homeWhatsAppDigits = dh_digits(dh_site_setting('contact.whatsapp_number', '5016728009'));
$homeTelHref = dh_tel_href($homePhone);
$homeWhatsAppUrl = $homeWhatsAppDigits !== '' ? 'https://wa.me/' . $homeWhatsAppDigits : '';
$heroImage1 = dh_site_setting('home.hero_image_1', $defaultHeroImage);
$heroImage2 = dh_site_setting('home.hero_image_2', 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1920&q=80');
$heroImage3 = dh_site_setting('home.hero_image_3', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1920&q=80');
$heroSocials = [
    [
        'name' => dh_site_setting('home.social_1_name', 'Instagram'),
        'url' => dh_site_setting('home.social_1_url', ''),
    ],
    [
        'name' => dh_site_setting('home.social_2_name', 'Facebook'),
        'url' => dh_site_setting('home.social_2_url', ''),
    ],
    [
        'name' => dh_site_setting('home.social_3_name', 'LinkedIn'),
        'url' => dh_site_setting('home.social_3_url', ''),
    ],
];
$heroSocials = array_values(array_filter($heroSocials, function ($item) {
    return !empty($item['name']) && !empty($item['url']);
}));

$facebookUrl = trim(dh_site_setting('social.facebook_url', ''));
$youtubeUrl = trim(dh_site_setting('social.youtube_url', ''));
$sameAs = array_values(array_filter([$facebookUrl, $youtubeUrl]));

$seoGraph = [
    [
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => $canonicalUrl,
        'logo' => $baseUrl . '/assets/img/logo.png',
        'sameAs' => $sameAs,
    ],
    [
        '@type' => 'WebSite',
        'name' => $siteName,
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
    <meta property="og:image" content="<?php echo htmlspecialchars($heroImage1, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($heroImage1, ENT_QUOTES, 'UTF-8'); ?>">
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
    <link rel="dns-prefetch" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <script type="application/ld+json"><?php echo $seoJson; ?></script>
</head>
<body class="dh-home">
    <?php include __DIR__ . '/includes/site-header.php'; ?>
    <main id="main-content">
    <div class="dh-hero-viewport">
        <?php if (!empty($heroSocials)): ?>
        <div class="dh-hero-socials">
            <?php foreach ($heroSocials as $social): ?>
                <a href="<?php echo dh_esc($social['url']); ?>" target="_blank" rel="noopener"><?php echo dh_esc($social['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="dh-hero-track" id="dhHeroTrack">
            <div class="dh-hero-panel">
                <div class="dh-hero-bg" style="background-image: url('<?php echo dh_esc($heroImage1); ?>');"></div>
                <div class="dh-hero-content">
                    <span class="dh-hero-tag">Exclusively Belize</span>
                    <h1>Find Your Piece <br>of <span style="color:var(--dh-gold)">Paradise.</span></h1>
                    <p>Discover the most prestigious properties in Belize, from private island estates to modern beachfront villas.</p>
                </div>
            </div>

            <div class="dh-hero-panel">
                <div class="dh-hero-bg" style="background-image: url('<?php echo dh_esc($heroImage2); ?>');"></div>
                <div class="dh-hero-content">
                    <span class="dh-hero-tag">Luxury Living</span>
                    <h1>Modern Design <br>Meet <span style="color:var(--dh-gold)">Island Soul.</span></h1>
                    <p>Experience architectural excellence designed to blend seamlessly with the natural beauty of the Caribbean coast.</p>
                </div>
            </div>

            <div class="dh-hero-panel">
                <div class="dh-hero-bg" style="background-image: url('<?php echo dh_esc($heroImage3); ?>');"></div>
                <div class="dh-hero-content">
                    <span class="dh-hero-tag">Investment Opportunity</span>
                    <h1>Secure Your <br><span style="color:var(--dh-gold)">Legacy Today.</span></h1>
                    <p>Belize is one of the fastest-growing markets in the region. Let our experts guide your next high-yield investment.</p>
                </div>
            </div>
        </div>

        <div class="dh-hero-search">
            <form class="dh-search-wrap" action="#">
                <input class="dh-search-input" type="text" placeholder="Search by neighborhood, city, zip, or keyword...">
                <button type="submit" class="dh-search-btn">Search</button>
            </form>
        </div>

        <div class="dh-hero-nav">
            <span class="dh-nav-num" id="dhNavNum">01</span>
            <div class="dh-hero-nav-dots">
                <button class="dh-dot is-active" onclick="dhJump(0)"></button>
                <button class="dh-dot" onclick="dhJump(1)"></button>
                <button class="dh-dot" onclick="dhJump(2)"></button>
            </div>
        </div>
    </div>

    <section class="dh-module-wrapper" id="dhListings">
        <div class="dh-header-row">
            <div>
                <span class="dh-subtitle">Fresh on the Market</span>
                <h2 class="dh-title">Exclusive Listings</h2>
            </div>
            <div style="display:flex; gap:10px;">
                <span class="dh-subtitle" style="color:#999; cursor:default;">Swipe to Explore &rarr;</span>
            </div>
        </div>

        <div class="dh-scroll-wrapper">
            <div class="dh-scroll-track" id="dhListingTrack"></div>

            <div class="dh-no-results" id="dhNoResults">No listings match your search.</div>
        </div>

        <div class="dh-header-row" style="margin-top: 60px;">
            <div>
                <span class="dh-subtitle">Find Your Match</span>
                <h2 class="dh-title">Curated Collections</h2>
            </div>
            <a href="/listings.php#categories" class="dh-view-all">View All Collections</a>
        </div>

        <div class="dh-cat-container" id="dhCategoryTrack"></div>
        <div class="dh-no-results" id="dhNoCategories">No categories available yet.</div>

        <div class="dh-lifestyle-section">
            <div class="dh-lifestyle-overlay"></div>
            <div class="dh-lifestyle-content">
                <div class="dh-subtitle" style="margin-bottom:20px;">Client Stories</div>
                <div class="dh-quote">"They didn't just find us a house; they found us the exact sunset view we had been dreaming of for ten years."</div>

                <div class="dh-quote-author">
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" class="dh-quote-avatar" alt="Elena" loading="lazy" width="50" height="50">
                    <div class="dh-quote-details">
                        <h4>Elena & Marcus</h4>
                        <span>Relocated to Ambergris Caye</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dh-contact-section">
            <div class="dh-cta-text">
                <span class="dh-subtitle" style="color: rgba(255,255,255,0.7); margin-bottom:10px;">Concierge Service</span>
                <h2>Selling or Buying?</h2>
                <p>Experience the gold standard in Belize real estate. Get a private valuation or speak to our agents about off-market opportunities.</p>
            </div>
            <div class="dh-cta-buttons">
                <?php if ($homeTelHref !== ''): ?>
                    <a href="<?php echo dh_esc($homeTelHref); ?>" class="dh-btn-primary">Speak to Agent</a>
                <?php endif; ?>
                <?php if ($homeWhatsAppUrl !== ''): ?>
                    <a href="<?php echo dh_esc($homeWhatsAppUrl); ?>" class="dh-btn-outline">WhatsApp Us</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>

    <script>
        const dhTrack = document.getElementById('dhHeroTrack');
        const dhDots = document.querySelectorAll('.dh-dot');
        const dhPanels = document.querySelectorAll('.dh-hero-panel');
        const dhNum = document.getElementById('dhNavNum');
        let dhIdx = 0;
        const dhInterval = 6000;

        function dhJump(n) {
            dhIdx = n;
            dhUpdate();
            dhResetAuto();
        }

        function dhUpdate() {
            dhTrack.style.transform = `translateX(-${dhIdx * 33.333}%)`;
            dhDots.forEach((d, i) => d.classList.toggle('is-active', i === dhIdx));
            dhPanels.forEach((p, i) => p.classList.toggle('is-visible', i === dhIdx));
            dhNum.innerText = `0${dhIdx + 1}`;
        }

        function startLoop() {
            dhLoop = setInterval(() => {
                dhIdx = (dhIdx + 1) % 3;
                dhUpdate();
            }, dhInterval);
        }

        let dhLoop = null;
        requestAnimationFrame(() => {
            dhUpdate();
        });
        startLoop();

        function dhResetAuto() {
            clearInterval(dhLoop);
            startLoop();
        }

        let categoryData = [];
        let listingData = [];

        const searchInputs = document.querySelectorAll('.dh-search-input');
        const dhCategoryTrack = document.getElementById('dhCategoryTrack');
        const noCategories = document.getElementById('dhNoCategories');
        const dhListingTrack = document.getElementById('dhListingTrack');
        let listingCards = [];
        const noResults = document.getElementById('dhNoResults');
        let currentQuery = '';

        function formatListingCountLabel(count) {
            const safeCount = Number.isFinite(count) ? count : 0;
            const word = safeCount === 1 ? 'Listing' : 'Listings';
            return `${safeCount} ${word}`;
        }

        function buildCategoryUrl(category) {
            const id = category && (category.id ?? category.category_id);
            if (id === undefined || id === null || String(id).trim() === '') {
                return category.url || '/listings.php';
            }
            const numericId = Number(id);
            if (!Number.isFinite(numericId) || numericId <= 0) {
                return category.url || '/listings.php';
            }
            return `/listings.php?category=${encodeURIComponent(String(numericId))}`;
        }

        function updateCategoryCountsFromListings() {
            if (!Array.isArray(categoryData) || categoryData.length === 0) {
                return;
            }
            if (!Array.isArray(listingData) || listingData.length === 0) {
                // Still update URLs so category click deep-links correctly.
                categoryData = categoryData.map((category) => ({
                    ...category,
                    url: buildCategoryUrl(category),
                }));
                renderCategories();
                return;
            }

            const hasCategoryAssignments = listingData.some((listing) => Array.isArray(listing && listing.categories) && listing.categories.length > 0);
            if (!hasCategoryAssignments) {
                // Listings don't include categories (or API failed). Keep existing count labels.
                categoryData = categoryData.map((category) => ({
                    ...category,
                    url: buildCategoryUrl(category),
                }));
                renderCategories();
                return;
            }

            const counts = new Map();
            listingData.forEach((listing) => {
                const cats = listing && Array.isArray(listing.categories) ? listing.categories : [];
                cats.forEach((cat) => {
                    const id = cat && (cat.id ?? cat.category_id);
                    if (id === undefined || id === null) {
                        return;
                    }
                    const key = String(id);
                    counts.set(key, (counts.get(key) || 0) + 1);
                });
            });

            categoryData = categoryData.map((category) => {
                const id = category && (category.id ?? category.category_id);
                const key = id === undefined || id === null ? '' : String(id);
                const count = key !== '' ? (counts.get(key) || 0) : 0;
                return {
                    ...category,
                    count,
                    countLabel: formatListingCountLabel(count),
                    url: buildCategoryUrl(category),
                };
            });

            renderCategories();
        }

        const MAX_HOME_CATEGORIES = 3;

        function renderCategories() {
            if (!dhCategoryTrack) {
                return;
            }

            const visibleCategories = categoryData.slice(0, MAX_HOME_CATEGORIES);

            dhCategoryTrack.innerHTML = visibleCategories.map((category) => {
                const label = category.countLabel || 'View Collection';
                const url = buildCategoryUrl(category);
                return `
                    <div class="dh-cat-card" onclick="location.href='${url}'">
                        <img src="${category.image}" class="dh-cat-bg" alt="${category.name}">
                        <div class="dh-cat-content">
                            <h3 class="dh-cat-name">${category.name}</h3>
                            <div class="dh-cat-meta">
                                <span>${label}</span>
                                <span>&rarr;</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            if (noCategories) {
                noCategories.style.display = categoryData.length === 0 ? 'block' : 'none';
            }
        }

        function buildSearchText(listing) {
            const parts = [
                listing.title,
                listing.location,
                listing.price,
                listing.badge,
                ...(listing.stats || []),
                ...(listing.keywords || [])
            ];
            return parts.join(' ').toLowerCase();
        }

        function resolveListingUrl(listing) {
            if (listing.url && listing.url !== '#') {
                return listing.url;
            }
            return listing.slug ? `/listing.php?slug=${encodeURIComponent(listing.slug)}` : '#';
        }

        function resolveListingImage(listing) {
            if (listing.image) {
                return listing.image;
            }
            if (Array.isArray(listing.images) && listing.images.length > 0) {
                return listing.images[0];
            }
            return 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=80';
        }

        function renderListingCard(listing) {
            const icon = '<svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
            const badgeClass = listing.badgeStyle === 'gold' ? 'dh-badge gold' : 'dh-badge';
            const statsHtml = (listing.stats || []).slice(0, 3).map(stat => {
                const pieces = stat.split(' ');
                const value = pieces.shift() || '';
                const label = pieces.join(' ');
                return `<div class="dh-stat"><strong>${value}</strong> <span>${label}</span></div>`;
            }).join('');
            
            const searchText = buildSearchText(listing);
            const image = resolveListingImage(listing);
            const url = resolveListingUrl(listing);
            
            // Extract keywords for display
            const keywords = (listing.keywords || []).slice(0, 3).map(k => 
                `<span class="dh-card-keyword">${k}</span>`
            ).join('');

            return `
                <a href="${url}" class="dh-prop-card" data-search="${searchText}" style="text-decoration: none; color: inherit;">
                    <div class="dh-prop-media">
                        <span class="${badgeClass}">${listing.badge}</span>
                        <img src="${image}" class="dh-prop-img" alt="${listing.title}" loading="lazy">
                        <div class="dh-card-overlay">
                            <div class="dh-card-cta">View Property →</div>
                        </div>
                    </div>
                    <div class="dh-prop-body">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div class="dh-price">${listing.price}</div>
                            <div style="text-align: right;">
                                ${listing.featured ? '<span style="font-size: 0.7rem; background: var(--dh-gold); color: white; padding: 4px 8px; border-radius: 999px; font-weight: 600;">⭐ FEATURED</span>' : ''}
                            </div>
                        </div>
                        <h3 class="dh-address">${listing.title}</h3>
                        <div class="dh-location">${icon}${listing.location}</div>
                        <div class="dh-card-stats">${statsHtml}</div>
                        ${keywords ? `<div class="dh-card-keywords">${keywords}</div>` : ''}
                    </div>
                </a>
            `;
        }

        function renderListings() {
            dhListingTrack.innerHTML = listingData.map(renderListingCard).join('');
            listingCards = document.querySelectorAll('.dh-prop-card');
            if (noResults) {
                noResults.style.display = listingData.length === 0 ? 'block' : 'none';
            }
        }

        async function loadCategories() {
            try {
                const response = await fetch('/api/categories.php', { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Failed to fetch categories');
                }
                const data = await response.json();
                if (Array.isArray(data) && data.length > 0) {
                    categoryData = data;
                }
            } catch (error) {
                categoryData = [];
            }
            // Ensure category cards deep-link correctly even before listings load.
            updateCategoryCountsFromListings();
        }

        async function loadListings() {
            try {
                const response = await fetch('/api/listings.php', { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('Failed to fetch listings');
                }
                const data = await response.json();
                if (Array.isArray(data) && data.length > 0) {
                    listingData = data;
                }
            } catch (error) {
                listingData = [];
            }
            renderListings();
            filterListings(currentQuery);
            // Now that listings are loaded, update the category counts.
            updateCategoryCountsFromListings();
        }

        function normalizeQuery(value) {
            return value.toLowerCase().trim();
        }

        function syncInputs(value) {
            searchInputs.forEach((input) => {
                if (input.value !== value) {
                    input.value = value;
                }
            });
        }

        function filterListings(query) {
            currentQuery = query;
            const normalized = normalizeQuery(query);
            let matches = 0;

            listingCards.forEach((card) => {
                const haystack = (card.getAttribute('data-search') || card.textContent).toLowerCase();
                const isMatch = normalized === '' || haystack.includes(normalized);
                card.style.display = isMatch ? '' : 'none';
                if (isMatch) { matches += 1; }
            });

            noResults.style.display = matches === 0 ? 'block' : 'none';
        }

        document.querySelectorAll('.dh-search-wrap').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('.dh-search-input');
                const query = input ? input.value : '';
                syncInputs(query);
                filterListings(query);
                document.getElementById('dhListings').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        searchInputs.forEach((input) => {
            input.addEventListener('input', (event) => {
                const query = event.target.value;
                syncInputs(query);
                filterListings(query);
            });
        });

        renderCategories();
        loadCategories();
        loadListings();
    </script>
</body>
</html>
