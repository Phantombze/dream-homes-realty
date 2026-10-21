<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = 'Listings | Dream Homes Realty';
$metaDescription = 'Browse Belize property listings, featured homes, and curated collections with Dream Homes Realty.';
$canonicalUrl = $baseUrl . '/listings.php';
$ogImage = 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80';

$seoGraph = [
    [
        '@type' => 'WebPage',
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
    <script type="application/ld+json"><?php echo $seoJson; ?></script>
</head>
<body class="dh-listings-page">
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main id="main-content">
    <section class="dh-listings-hero">
        <div class="dh-listings-hero-inner">
            <div class="dh-listings-hero-main">
                <nav class="dh-listings-breadcrumb">Belize Real Estate &rsaquo; Listings</nav>
                <h1 id="heroTitle">Listings & Featured Properties</h1>
                <p id="heroDesc">Browse the latest properties, explore curated categories, and discover featured opportunities handpicked by our team.</p>
            </div>
            <div class="dh-listings-hero-meta">
                <div class="dh-listings-count" id="dhListingsCount">0 Listings</div>
                <div class="dh-listings-tags">
                    <span class="dh-tag">Trusted Service</span>
                    <span class="dh-tag is-muted">Belize Best</span>
                </div>
            </div>
        </div>
        <div class="dh-listings-toolbar">
            <form class="dh-search-wrap dh-search-wrap--light" action="#">
                <input class="dh-search-input" type="text" placeholder="Search listings by location, price, or keyword...">
                <button type="submit" class="dh-search-btn">Search</button>
            </form>
            <div class="dh-filter-bar">
                <span class="dh-filter-label">Filter by Category</span>
                <div class="dh-filter-group">
                    <button class="dh-category-filter active" data-category="0" onclick="filterByCategory('0')">
                        All Properties
                    </button>
                    <div id="dhCategoryFilters" class="dh-filter-scroll"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Two Column Layout: Main Listings + Featured Sidebar -->
    <section class="dh-listings-section">
        <div class="dh-listings-shell">
            <div class="dh-listings-layout">
                
                <!-- Main Listings Column -->
                <div>
                    <div class="dh-listings-subhead">
                        <span class="dh-listings-subtitle" id="allListingsSubtitle">All Properties</span>
                        <h2 class="dh-listings-title" id="allListingsTitle">Browse All Listings</h2>
                    </div>
                    <div class="dh-listing-grid" id="dhAllListings"></div>
                    <div class="dh-no-results" id="dhAllEmpty">No listings match your search.</div>
                    
                    <!-- Pagination Controls -->
                    <div id="dhPaginationContainer" class="dh-pagination" style="display: none;">
                        <button id="btnPrevPage" class="dh-page-btn" onclick="changePage(-1)">Previous</button>
                        <div id="dhPageInfo" class="dh-page-info">Page 1</div>
                        <button id="btnNextPage" class="dh-page-btn" onclick="changePage(1)">Next</button>
                    </div>
                </div>

                <!-- Featured Sidebar -->
                <div class="dh-featured-sidebar">
                    <div class="dh-sidebar-panel">
                        <div class="dh-sidebar-header">
                            <span class="dh-subtitle">Featured</span>
                            <h3 class="dh-sidebar-title">Must-See Properties</h3>
                        </div>
                        <div id="dhFeaturedSidebar" class="dh-featured-list"></div>
                        <div class="dh-no-results" id="dhFeaturedEmpty" style="display: none; padding: 20px; text-align: center;">No featured listings yet.</div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>

    <script>
        console.log('=== LISTINGS PAGE SCRIPT STARTING ===');

        let categoryData = [
            {
                id: 0,
                name: 'Luxury Homes',
                image: 'https://images.unsplash.com/photo-1600596542815-e328701102b9?auto=format&fit=crop&w=800&q=80',
                url: '/listings.php',
                countLabel: '12 Listings'
            },
            {
                id: 0,
                name: 'Beachfront Condos',
                image: 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
                url: '/listings.php',
                countLabel: '8 Listings'
            },
            {
                id: 0,
                name: 'Private Islands & Land',
                image: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',
                url: '/listings.php',
                countLabel: '15 Listings'
            }
        ];

        // Add or edit listings here (used as fallback if API is unavailable).
        let listingData = [
            {
                title: 'Oceanview Villa',
                slug: 'oceanview-villa',
                location: 'Vista Del Mar, Belize',
                price: '$450,000',
                badge: 'Featured',
                badgeStyle: 'gold',
                featured: 1,
                image: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=600&q=80',
                images: [],
                url: '#',
                mapQuery: 'Vista Del Mar, Belize',
                stats: ['4 Bed', '3 Bath', '2,500 SqFt'],
                keywords: ['oceanview', 'villa', 'belize', 'featured']
            },
            {
                title: 'The Royal Estate',
                slug: 'the-royal-estate',
                location: 'San Pedro, Belize',
                price: '$1,200,000',
                badge: 'Exclusive',
                badgeStyle: 'navy',
                featured: 0,
                image: 'https://images.unsplash.com/photo-1580587771525-78b9dba3b91d?auto=format&fit=crop&w=600&q=80',
                images: [],
                url: '#',
                mapQuery: 'San Pedro, Belize',
                stats: ['3 Bed', '2 Bath', 'Infinity Pool'],
                keywords: ['estate', 'san pedro', 'belize', 'luxury']
            },
            {
                title: 'Jungle Sanctuary',
                slug: 'jungle-sanctuary',
                location: 'Cayo District',
                price: '$89,000',
                badge: 'Investment',
                badgeStyle: 'navy',
                featured: 0,
                image: 'https://images.unsplash.com/photo-1592595896551-12b371d546d5?auto=format&fit=crop&w=600&q=80',
                images: [],
                url: '#',
                mapQuery: 'Cayo District, Belize',
                stats: ['10 Acres', 'Raw Land', 'Road Access'],
                keywords: ['jungle', 'land', 'cayo', 'investment']
            },
            {
                title: 'Azure Condo',
                slug: 'azure-condo',
                location: 'Placencia Peninsula',
                price: '$220,000',
                badge: 'Turnkey',
                badgeStyle: 'navy',
                featured: 0,
                image: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=600&q=80',
                images: [],
                url: '#',
                mapQuery: 'Placencia Peninsula, Belize',
                stats: ['2 Bed', '2 Bath', 'Beach Front'],
                keywords: ['condo', 'placencia', 'beachfront']
            }
        ];

        const searchInputs = document.querySelectorAll('.dh-search-input');
        const dhCategoryFilters = document.getElementById('dhCategoryFilters');
        const dhFeaturedSidebar = document.getElementById('dhFeaturedSidebar');
        const dhFeaturedEmpty = document.getElementById('dhFeaturedEmpty');
        const dhAllListings = document.getElementById('dhAllListings');
        const dhAllEmpty = document.getElementById('dhAllEmpty');
        let currentQuery = '';
        let currentCategoryId = new URLSearchParams(window.location.search).get('category') || '0';
        let allListingsData = []; // Store all listings
        let currentPage = 1;
        const LISTINGS_PER_PAGE = 6;
        
        console.log('DOM Elements found:');
        console.log('- dhCategoryFilters:', dhCategoryFilters ? 'YES' : 'NO');
        console.log('- dhFeaturedSidebar:', dhFeaturedSidebar ? 'YES' : 'NO');
        console.log('- dhAllListings:', dhAllListings ? 'YES' : 'NO');
        console.log('- Current Category ID:', currentCategoryId);

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

        function renderStat(stat) {
            const pieces = stat.split(' ');
            const value = pieces.shift() || '';
            const label = pieces.join(' ');
            return `<div class="dh-stat"><strong>${value}</strong> ${label}</div>`;
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

        function isFeatured(listing) {
            if (listing.featured) {
                return true;
            }
            const badge = (listing.badge || '').toLowerCase();
            const keywords = (listing.keywords || []).join(' ').toLowerCase();
            return badge.includes('featured') || keywords.includes('featured');
        }

        // Render compact sidebar featured card
        function renderFeaturedSidebarCard(listing) {
            const image = resolveListingImage(listing);
            const url = resolveListingUrl(listing);
            const badge = listing.badge || 'Featured';
            
            return `
                <a href="${url}" class="dh-featured-card">
                    <div class="dh-featured-card-media">
                        <img src="${image}" alt="${listing.title}" loading="lazy">
                        <span class="dh-featured-card-badge">${badge}</span>
                    </div>
                    <div class="dh-featured-card-body">
                        <div class="dh-featured-card-price">${listing.price}</div>
                        <h4 class="dh-featured-card-title">${listing.title}</h4>
                        <div class="dh-featured-card-location">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                            <span>${listing.location}</span>
                        </div>
                    </div>
                </a>
            `;
        }

        // Render compact category filter buttons
        function renderCategoryFilters() {
            console.log('renderCategoryFilters() called with', categoryData.length, 'categories');
            if (!dhCategoryFilters) {
                console.warn('ERROR: dhCategoryFilters element not found!');
                return;
            }

            const html = categoryData.map((category) => {
                const isActive = String(category.id) === String(currentCategoryId);
                return `
                    <button class="dh-category-filter ${isActive ? 'active' : ''}" 
                            data-category="${category.id}" 
                            onclick="filterByCategory('${category.id}')">
                        ${category.name}
                    </button>
                `;
            }).join('');
            
            console.log('Generated category filter HTML length:', html.length, 'bytes');
            dhCategoryFilters.innerHTML = html;
            console.log('Category filters inserted into DOM');
            
            // Update "All Properties" button active state
            const allButton = document.querySelector('[data-category="0"]');
            if (allButton) {
                if (currentCategoryId === '0') {
                    allButton.classList.add('active');
                } else {
                    allButton.classList.remove('active');
                }
            }
        }

        function renderCategories() {
            // Legacy function - now using renderCategoryFilters instead
            console.log('renderCategories() - skipping (using renderCategoryFilters)');
        }

        function renderFeatured() {
            console.log('renderFeatured() called with', allListingsData.length, 'total listings');
            const featured = allListingsData.filter(isFeatured);
            const featuredList = featured.length > 0 ? featured : allListingsData.slice(0, 3);
            console.log('- Showing', featuredList.length, 'featured listings in sidebar');
            const html = featuredList.map(renderFeaturedSidebarCard).join('');
            console.log('Generated featured HTML length:', html.length, 'bytes');
            dhFeaturedSidebar.innerHTML = html;
            console.log('Featured HTML inserted into DOM, dhFeaturedSidebar children:', dhFeaturedSidebar.children.length);
            dhFeaturedEmpty.style.display = featuredList.length === 0 ? 'block' : 'none';
        }

        function renderAllListings() {
            console.log('renderAllListings() called - rendering', allListingsData.length, 'listings');
            
            // Filter by current category
            let displayListings = allListingsData;
            if (currentCategoryId && currentCategoryId !== '0') {
                displayListings = allListingsData.filter(listing => {
                    if (!listing.categories || !Array.isArray(listing.categories)) return false;
                    return listing.categories.some(cat => String(cat.id) === String(currentCategoryId));
                });
                console.log('Filtered to', displayListings.length, 'listings for category', currentCategoryId);
            }
            
            // Apply search filter if active
            if (currentQuery && currentQuery.trim() !== '') {
                const normalized = normalizeQuery(currentQuery);
                displayListings = displayListings.filter(listing => {
                    const searchText = buildSearchText(listing);
                    return searchText.includes(normalized);
                });
                console.log('Search filtered to', displayListings.length, 'listings for query:', currentQuery);
            }
            
            // Calculate pagination
            const totalPages = Math.ceil(displayListings.length / LISTINGS_PER_PAGE);
            const startIndex = (currentPage - 1) * LISTINGS_PER_PAGE;
            const endIndex = startIndex + LISTINGS_PER_PAGE;
            const paginatedListings = displayListings.slice(startIndex, endIndex);
            
            console.log(`Pagination: Page ${currentPage} of ${totalPages}, showing ${paginatedListings.length} of ${displayListings.length} total`);
            
            // Render the paginated listings
            const html = paginatedListings.map(renderListingCard).join('');
            console.log('Generated listings HTML length:', html.length, 'bytes');
            dhAllListings.innerHTML = html;
            console.log('Listings HTML inserted into DOM, dhAllListings children:', dhAllListings.children.length);
            dhAllEmpty.style.display = displayListings.length === 0 ? 'block' : 'none';

            const countEl = document.getElementById('dhListingsCount');
            if (countEl) {
                countEl.textContent = `${displayListings.length} Listings`;
            }
            
            // Update pagination controls
            updatePaginationControls(totalPages, displayListings.length);
            
            // Update section title
            if (currentCategoryId && currentCategoryId !== '0') {
                const category = categoryData.find(c => String(c.id) === String(currentCategoryId));
                if (category) {
                    const subtitleEl = document.getElementById('allListingsSubtitle');
                    const titleEl = document.getElementById('allListingsTitle');
                    if (subtitleEl) subtitleEl.textContent = category.name;
                    if (titleEl) titleEl.textContent = `${category.name} Properties (${displayListings.length})`;
                }
            } else {
                const subtitleEl = document.getElementById('allListingsSubtitle');
                const titleEl = document.getElementById('allListingsTitle');
                if (subtitleEl) subtitleEl.textContent = 'All Properties';
                if (titleEl) titleEl.textContent = `Browse All Listings (${displayListings.length})`;
            }
            
            // Scroll to top of listings on page change
            if (currentPage > 1) {
                dhAllListings.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        function updatePaginationControls(totalPages, totalListings) {
            const paginationContainer = document.getElementById('dhPaginationContainer');
            const pageInfo = document.getElementById('dhPageInfo');
            const btnPrev = document.getElementById('btnPrevPage');
            const btnNext = document.getElementById('btnNextPage');
            
            // Show/hide pagination based on whether we have multiple pages
            if (totalPages > 1) {
                paginationContainer.style.display = 'flex';
                pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                
                // Disable/enable buttons
                btnPrev.disabled = currentPage === 1;
                btnPrev.style.opacity = currentPage === 1 ? '0.5' : '1';
                btnPrev.style.cursor = currentPage === 1 ? 'not-allowed' : 'pointer';
                
                btnNext.disabled = currentPage === totalPages;
                btnNext.style.opacity = currentPage === totalPages ? '0.5' : '1';
                btnNext.style.cursor = currentPage === totalPages ? 'not-allowed' : 'pointer';
            } else {
                paginationContainer.style.display = 'none';
            }
        }
        
        function changePage(direction) {
            currentPage += direction;
            if (currentPage < 1) currentPage = 1;
            renderAllListings();
        }

        async function loadCategories() {
            console.log('loadCategories() started');
            try {
                const response = await fetch('/api/categories.php', { cache: 'no-store' });
                console.log('Categories API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Failed to fetch categories');
                }
                const data = await response.json();
                console.log('Categories API returned:', data);
                if (Array.isArray(data) && data.length > 0) {
                    categoryData = data;
                    console.log('Loading', data.length, 'categories from API');
                }
            } catch (error) {
                console.error('Error loading categories:', error);
                console.log('Using fallback categoryData:', categoryData.length, 'categories');
                // Keep fallback categoryData if API is not available.
            }
            console.log('Calling renderCategories with', categoryData.length, 'categories');
            renderCategories();
            renderCategoryFilters();
        }

        async function loadListings() {
            console.log('loadListings() started');
            try {
                // Always fetch ALL listings, we'll filter client-side
                const url = '/api/listings.php';
                console.log('Fetching listings from:', url);
                const response = await fetch(url, { cache: 'no-store' });
                console.log('Listings API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Failed to fetch listings');
                }
                const data = await response.json();
                console.log('Listings API returned:', data.length, 'listings');
                if (Array.isArray(data) && data.length > 0) {
                    allListingsData = data;
                    console.log('Updated allListingsData with', data.length, 'listings from API');
                } else {
                    console.log('API returned empty or non-array data, keeping fallback');
                    allListingsData = listingData; // Use fallback
                }
            } catch (error) {
                console.error('Error loading listings:', error);
                console.log('Using fallback listingData:', listingData.length, 'listings');
                allListingsData = listingData; // Use fallback
            }
            console.log('Calling renderFeatured and renderAllListings');
            renderFeatured();
            renderAllListings();
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
            currentPage = 1; // Reset to first page when searching
            renderAllListings(); // Re-render with search filter
        }

        document.querySelectorAll('.dh-search-wrap').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('.dh-search-input');
                const query = input ? input.value : '';
                syncInputs(query);
                filterListings(query);
            });
        });

        searchInputs.forEach((input) => {
            input.addEventListener('input', (event) => {
                const query = event.target.value;
                syncInputs(query);
                filterListings(query);
            });
        });

        // Filter by category without page reload
        function filterByCategory(categoryId) {
            console.log('Filtering by category:', categoryId);
            currentCategoryId = String(categoryId);
            currentPage = 1; // Reset to first page when changing category
            
            // Update URL without reload
            const newUrl = categoryId === '0' ? '/listings.php' : `/listings.php?category=${categoryId}`;
            window.history.pushState({category: categoryId}, '', newUrl);
            
            // Re-render with new filter
            renderAllListings();
            renderCategoryFilters(); // Update active button states
        }

        // Update page layout based on category selection
        async function updateCategoryHeader() {
            // This function is no longer needed for hiding sections
            // Category filtering is handled client-side now
        }

        updateCategoryHeader();
        console.log('=== NOW CALLING loadCategories() ===');
        loadCategories();
        console.log('=== NOW CALLING loadListings() ===');
        loadListings();
        console.log('=== SCRIPT INITIALIZATION COMPLETE ===');
    </script>
