<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/site-settings.php';

$agentPhone = dh_site_setting('agent.phone', dh_site_setting('contact.phone_primary', '+501 672 8009'));
$agentEmail = dh_site_setting('agent.email', dh_site_setting('contact.email_main', 'info@dreamhomes-realty.com'));
$agentName = dh_site_setting('agent.name', 'Dream Homes Realty');
$agentPhoto = dh_site_setting('agent.photo', 'https://i.pravatar.cc/150?img=11');

$slug = trim($_GET['slug'] ?? '');
$listing = null;
$images = [];

function dh_youtube_id(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return '';
    }
    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    if (strpos($host, 'youtu.be') !== false) {
        return trim($path, '/');
    }
    if (strpos($host, 'youtube.com') === false) {
        return '';
    }
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if (!empty($query['v'])) {
            return (string) $query['v'];
        }
    }
    if (strpos($path, '/embed/') === 0) {
        return trim(substr($path, strlen('/embed/')));
    }
    if (strpos($path, '/shorts/') === 0) {
        return trim(substr($path, strlen('/shorts/')));
    }
    return '';
}

function dh_map_query_from_url(string $value): string {
    $value = trim($value);
    if ($value === '' || stripos($value, 'http') !== 0) {
        return $value;
    }
    $parts = parse_url($value);
    if (!$parts) {
        return $value;
    }
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if (!empty($query['q'])) {
            return (string) $query['q'];
        }
    }
    $path = $parts['path'] ?? '';
    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $path, $matches)) {
        return $matches[1] . ',' . $matches[2];
    }
    if (strpos($path, '/place/') === 0) {
        $place = trim(substr($path, strlen('/place/')));
        return urldecode(str_replace('+', ' ', $place));
    }
    return $value;
}

function dh_amenity_icon_svg(string $icon): string {
    switch ($icon) {
        case 'gate':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>';
        case 'canal':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>';
        case 'sun':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
        case 'rooftop':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>';
        case 'utilities':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>';
        case 'ready':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        case 'pool':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 16c2.5 2 5.5 2 8 0s5.5-2 8 0 5.5 2 8 0"/></svg>';
        case 'parking':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3h8a4 4 0 010 8H5V3zm0 8v10"/></svg>';
        case 'wifi':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12a10 10 0 0114 0M8.5 15.5a5 5 0 017 0M12 19h.01"/></svg>';
        case 'ac':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 12h16M4 16h16"/></svg>';
        case 'security':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 4v5c0 5-3.5 9-7 9s-7-4-7-9V7l7-4z"/></svg>';
        case 'view':
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="3"/></svg>';
        default:
            return '<svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>';
    }
}

if ($slug !== '') {
    $stmt = db()->prepare('SELECT * FROM listings WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $listing = $stmt->fetch();
    if ($listing) {
        $stmt = db()->prepare('SELECT file_path, is_primary FROM listing_images WHERE listing_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['id' => $listing['id']]);
        $images = $stmt->fetchAll();
    }
}

if (!$listing) {
    http_response_code(404);
}

$stats = $listing ? json_decode($listing['stats_json'] ?? '[]', true) : [];
$keywords = $listing ? json_decode($listing['keywords_json'] ?? '[]', true) : [];
$amenities = $listing ? json_decode($listing['amenities_json'] ?? '[]', true) : [];
if (!is_array($amenities)) {
    $amenities = [];
}
$mapQueryRaw = $listing ? trim($listing['map_query'] ?? '') : '';
$mapQuery = dh_map_query_from_url($mapQueryRaw);
if ($mapQuery === '' && $listing) {
    $mapQuery = $listing['location'] ?? '';
}

$gallery = [];
foreach ($images as $image) {
    $gallery[] = $image['file_path'];
}

$primaryVideoId = '';
if ($listing) {
    $primaryVideoId = dh_youtube_id((string) ($listing['video_url'] ?? ''));
    if ($primaryVideoId === '') {
        $primaryVideoId = dh_youtube_id((string) ($listing['image_url'] ?? ''));
    }
}

if ($listing && empty($gallery) && !empty($listing['image_url']) && $primaryVideoId === '') {
    $gallery[] = $listing['image_url'];
}

$featuredListings = [];
if ($listing) {
    $stmt = db()->prepare('SELECT id, title, slug, location, price, image_url FROM listings WHERE featured = 1 AND id != :id ORDER BY id DESC LIMIT 2');
    $stmt->execute(['id' => $listing['id']]);
    $featuredListings = $stmt->fetchAll();
    if (empty($featuredListings)) {
        $stmt = db()->prepare('SELECT id, title, slug, location, price, image_url FROM listings WHERE id != :id ORDER BY id DESC LIMIT 2');
        $stmt->execute(['id' => $listing['id']]);
        $featuredListings = $stmt->fetchAll();
    }
}

// Create gallery data for JavaScript
$galleryJson = json_encode(array_values($gallery));

$primaryImage = $gallery[0] ?? 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80';
$mediaItems = [];
if ($primaryVideoId !== '') {
    $mediaItems[] = ['type' => 'video', 'id' => $primaryVideoId];
}
foreach ($gallery as $img) {
    $mediaItems[] = ['type' => 'image', 'src' => $img];
}
$mediaJson = json_encode($mediaItems);

$priceValue = $listing['price'] ?? '';
$showCurrency = $priceValue !== '' && stripos($priceValue, 'BZD') === false;
$primaryTag = 'For Sale';
$secondaryTag = trim((string) ($listing['badge'] ?? ''));
$amenitiesToRender = [];
if (!empty($amenities)) {
    foreach ($amenities as $amenity) {
        if (!is_array($amenity)) {
            continue;
        }
        $label = trim((string) ($amenity['label'] ?? ''));
        if ($label === '') {
            continue;
        }
        $amenitiesToRender[] = [
            'label' => $label,
            'icon' => (string) ($amenity['icon'] ?? 'default'),
        ];
    }
} elseif (!empty($keywords)) {
    foreach ($keywords as $keyword) {
        $label = trim((string) $keyword);
        if ($label === '') {
            continue;
        }
        $amenitiesToRender[] = [
            'label' => $label,
            'icon' => 'default',
        ];
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim($scheme . '://' . $host, '/');

$siteName = 'Dream Homes Realty';
$metaTitle = $listing ? ($listing['title'] . ' | ' . $siteName) : 'Listing | ' . $siteName;
$metaDescription = $listing
    ? trim($listing['title'] . ' in ' . ($listing['location'] ?? 'Belize') . '. ' . ($listing['price'] ?? ''))
    : 'Dream Homes Realty listing details.';
$canonicalUrl = $listing && !empty($listing['slug'])
    ? $baseUrl . '/listing.php?slug=' . rawurlencode($listing['slug'])
    : $baseUrl . '/listing.php';
$ogImage = $primaryImage;
$robotsTag = $listing ? 'index,follow' : 'noindex,nofollow';

$listingSchema = null;
if ($listing) {
    $listingSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $listing['title'],
        'url' => $canonicalUrl,
        'image' => [$ogImage],
        'description' => $metaDescription,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $listing['location'] ?? 'Belize',
            'addressCountry' => 'BZ',
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robotsTag, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="assets/css/tailwind.css">
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
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
    <?php if ($listingSchema): ?>
        <script type="application/ld+json"><?php echo json_encode($listingSchema, JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
</head>
<body class="dh-listing-detail bg-gray-50 text-gray-800">
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main id="main-content" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if (!$listing): ?>
        <section class="py-20 text-center">
            <p class="text-sm uppercase tracking-widest text-brand-gold font-bold">Not Found</p>
            <h1 class="text-3xl md:text-4xl font-serif font-bold text-brand-dark mt-3">Listing Not Found</h1>
            <p class="text-gray-500 mt-4">The listing you are looking for no longer exists or has been moved.</p>
            <a href="/listings.php" class="inline-block mt-8 bg-brand-dark text-white px-6 py-3 rounded-lg text-sm font-semibold">Back to Listings</a>
        </section>
    <?php else: ?>
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6 border-b border-gray-200 pb-6">
            <div>
                <nav class="text-xs text-brand-gold font-bold uppercase tracking-widest mb-2">
                    Belize Real Estate &rsaquo; <?php echo htmlspecialchars($listing['location']); ?> &rsaquo; <?php echo htmlspecialchars($listing['title']); ?>
                </nav>
                <h1 class="text-3xl md:text-5xl font-serif font-bold text-brand-dark">
                    <?php echo htmlspecialchars($listing['title']); ?>
                </h1>
                <p class="text-gray-500 mt-1 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <?php echo htmlspecialchars($listing['location']); ?>
                </p>
            </div>

            <div class="text-left md:text-right">
                <p class="text-4xl text-brand-gold font-serif font-bold">
                    <?php echo htmlspecialchars($priceValue); ?>
                    <?php if ($showCurrency): ?>
                        <span class="text-lg text-gray-400 font-sans font-normal">BZD</span>
                    <?php endif; ?>
                </p>
                <div class="flex gap-4 mt-2 text-sm font-semibold text-gray-600 justify-start md:justify-end">
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded"><?php echo htmlspecialchars($primaryTag); ?></span>
                    <?php if ($secondaryTag !== ''): ?>
                        <span class="bg-brand-gold/10 text-brand-gold px-2 py-1 rounded"><?php echo htmlspecialchars($secondaryTag); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-12 gap-4 h-[500px] mb-12">
            <?php if (!empty($mediaItems)): ?>
                <div class="hidden md:flex md:col-span-2 flex-col gap-4 h-full overflow-y-auto pr-2 scrollbar-hide">
                    <?php
                    $thumbItems = array_slice($mediaItems, 0, 4);
                    $shownImages = 0;
                    foreach ($thumbItems as $item) {
                        if ($item['type'] === 'image') {
                            $shownImages += 1;
                        }
                    }
                    $remainingImages = max(0, count($gallery) - $shownImages);
                    $nextImage = $gallery[$shownImages] ?? $primaryImage;
                    ?>
                    <?php foreach ($thumbItems as $index => $item): ?>
                        <img
                            src="<?php echo $item['type'] === 'video' ? 'https://img.youtube.com/vi/' . htmlspecialchars($item['id']) . '/hqdefault.jpg' : htmlspecialchars($item['src']); ?>"
                            class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-80 border-2 <?php echo $index === 0 ? 'border-brand-gold' : 'border-transparent'; ?>"
                            alt="Thumbnail"
                            onclick="setMainMedia(<?php echo (int) $index; ?>)">
                    <?php endforeach; ?>
                    <?php if ($remainingImages > 0): ?>
                        <div class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500 cursor-pointer hover:bg-gray-300" onclick="openGalleryModal(0)">
                            +<?php echo $remainingImages; ?> more
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="md:col-span-10 relative h-full rounded-2xl overflow-hidden shadow-xl group" id="dhGalleryMain" onclick="handleMainClick()">
                <img src="<?php echo htmlspecialchars($primaryImage); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Main View" id="mainGalleryImg" style="<?php echo $primaryVideoId !== '' ? 'display:none;' : ''; ?>">
                <div class="absolute inset-0" id="mainGalleryVideo" style="<?php echo $primaryVideoId !== '' ? '' : 'display:none;'; ?>">
                    <?php if ($primaryVideoId !== ''): ?>
                        <iframe
                            id="mainGalleryIframe"
                            class="w-full h-full"
                            src="https://www.youtube-nocookie.com/embed/<?php echo htmlspecialchars($primaryVideoId); ?>?rel=0&modestbranding=1"
                            title="Listing video"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen>
                        </iframe>
                    <?php endif; ?>
                </div>
                <div class="absolute inset-0 flex items-center justify-center bg-black/35 text-white text-sm opacity-0 transition" id="mainGalleryOverlay" style="<?php echo ($primaryVideoId !== '' || empty($gallery)) ? 'display:none;' : ''; ?>">
                    Click to view gallery
                </div>
                <div class="absolute bottom-4 right-4">
                    <button class="bg-white/90 backdrop-blur text-brand-dark px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-white transition" onclick="openGalleryModal(0); event.stopPropagation();">
                        View Gallery
                    </button>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <div class="lg:col-span-2 space-y-10">
                <?php if (!empty($stats)): ?>
                    <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <?php foreach (array_slice($stats, 0, 4) as $index => $stat): ?>
                            <?php $parts = explode(' ', $stat, 2); $value = array_shift($parts); $label = implode(' ', $parts); ?>
                            <div class="text-center <?php echo $index === 3 ? 'hidden sm:block' : ''; ?>">
                                <span class="block text-3xl font-serif font-bold text-brand-dark"><?php echo htmlspecialchars($value); ?></span>
                                <span class="text-xs uppercase text-gray-400 font-bold tracking-wider"><?php echo htmlspecialchars($label); ?></span>
                            </div>
                            <?php if ($index < min(3, count($stats) - 1)): ?>
                                <div class="h-10 w-px bg-gray-200"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <h2 class="text-2xl font-serif font-bold text-brand-dark mb-4">Property Details</h2>
                    <div class="prose prose-blue text-gray-600 max-w-none">
                        <p class="mb-4">
                            <strong><?php echo htmlspecialchars($listing['title']); ?></strong> is a premium property located in the heart of <?php echo htmlspecialchars($listing['location']); ?>.
                        </p>
                        <p>
                            Designed with modern living in mind, this listing highlights the lifestyle and amenities Belize real estate is known for.
                        </p>
                    </div>
                </div>

                <?php if (!empty($amenitiesToRender)): ?>
                    <div>
                        <h2 class="text-2xl font-serif font-bold text-brand-dark mb-6">Features & Amenities</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($amenitiesToRender as $amenity): ?>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <?php echo dh_amenity_icon_svg((string) $amenity['icon']); ?>
                                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($amenity['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($mapQuery !== ''): ?>
                    <div>
                        <h2 class="text-2xl font-serif font-bold text-brand-dark mb-4">Location</h2>
                        <div class="w-full h-80 bg-gray-200 rounded-xl overflow-hidden shadow-inner">
                            <iframe
                                class="w-full h-full"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.google.com/maps?q=<?php echo urlencode($mapQuery); ?>&output=embed">
                            </iframe>
                        </div>
                        <p class="text-sm text-gray-500 mt-2"><?php echo htmlspecialchars($mapQuery); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    <div class="bg-brand-dark text-white p-6 rounded-xl shadow-2xl relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>

                        <h3 class="text-xl font-serif font-bold mb-4">Schedule a Tour</h3>

                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-full border-2 border-brand-gold overflow-hidden">
                                <img src="<?php echo htmlspecialchars($agentPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($agentName); ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p class="font-bold text-lg leading-none"><?php echo htmlspecialchars($agentName); ?></p>
                                <p class="text-xs text-brand-gold uppercase tracking-widest mt-1">Listing Agent</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <?php $agentTelHref = dh_tel_href($agentPhone); ?>
                            <?php if ($agentTelHref !== ''): ?>
                                <a href="<?php echo dh_esc($agentTelHref); ?>" class="w-full bg-brand-gold hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all transform hover:-translate-y-0.5 flex justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    Call Agent
                                </a>
                            <?php endif; ?>
                            <?php if (trim($agentEmail) !== ''): ?>
                                <a href="mailto:<?php echo dh_esc(trim($agentEmail)); ?>" class="w-full bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    Email Inquiry
                                </a>
                            <?php endif; ?>
                            <a href="/contact.php" class="w-full bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path></svg>
                                Contact Form
                            </a>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide">Similar Listings</h4>
                        <?php if (!empty($featuredListings)): ?>
                            <div class="space-y-4">
                                <?php foreach ($featuredListings as $featured): ?>
                                    <?php
                                    $featuredSlug = $featured['slug'] ?? '';
                                    $featuredUrl = $featuredSlug !== ''
                                        ? '/listing.php?slug=' . rawurlencode($featuredSlug)
                                        : '/listings.php';
                                    $featuredImage = $featured['image_url'] ?: 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=80';
                                    ?>
                                    <a class="flex gap-3 items-center group" href="<?php echo htmlspecialchars($featuredUrl); ?>">
                                        <div class="w-16 h-16 rounded-md overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($featuredImage); ?>" class="w-full h-full object-cover group-hover:scale-110 transition" alt="<?php echo htmlspecialchars($featured['title']); ?>">
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800 group-hover:text-brand-gold transition"><?php echo htmlspecialchars($featured['title']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($featured['location']); ?></p>
                                            <p class="text-xs font-bold text-brand-gold mt-1"><?php echo htmlspecialchars($featured['price']); ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No similar listings yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <div id="galleryModal" class="dh-modal">
            <div class="dh-modal-content">
                <button class="dh-modal-close" onclick="closeGalleryModal()" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="dh-modal-gallery">
                    <button class="dh-modal-nav dh-modal-prev" onclick="prevGalleryImage()" aria-label="Previous">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="dh-modal-image-container">
                        <img id="modalImage" src="<?php echo htmlspecialchars($primaryImage); ?>" alt="Full size image">
                    </div>
                    <button class="dh-modal-nav dh-modal-next" onclick="nextGalleryImage()" aria-label="Next">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
                <div class="dh-modal-thumbnails">
                    <?php foreach ($gallery as $index => $img): ?>
                        <div class="dh-modal-thumb" onclick="setModalImage(<?php echo $index; ?>)">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="dh-modal-counter">
                    <span id="modalCounter">1</span> / <span><?php echo count($gallery); ?></span>
                </div>
            </div>
        </div>

        <script>
            const galleryImages = <?php echo $galleryJson; ?>;
            const galleryItems = <?php echo $mediaJson; ?>;
            const mainImg = document.getElementById('mainGalleryImg');
            const mainVideo = document.getElementById('mainGalleryVideo');
            const mainIframe = document.getElementById('mainGalleryIframe');
            const mainOverlay = document.getElementById('mainGalleryOverlay');
            const imageIndexByMedia = [];
            let currentMediaIndex = 0;
            let currentImageIndex = 0;
            let currentModalIndex = 0;

            (function buildMediaIndexMap() {
                let imageCounter = 0;
                galleryItems.forEach((item, idx) => {
                    if (item && item.type === 'image') {
                        imageIndexByMedia[idx] = imageCounter;
                        imageCounter += 1;
                    } else {
                        imageIndexByMedia[idx] = -1;
                    }
                });
                currentImageIndex = imageIndexByMedia[0] ?? -1;
            })();

            function handleMainClick() {
                if (currentImageIndex >= 0) {
                    openGalleryModal(currentImageIndex);
                }
            }

            function setMainMedia(index) {
                if (!Array.isArray(galleryItems) || galleryItems.length === 0) {
                    if (mainOverlay) {
                        mainOverlay.style.display = galleryImages.length > 0 ? 'flex' : 'none';
                    }
                    return;
                }
                const item = galleryItems[index];
                if (!item) {
                    return;
                }
                currentMediaIndex = index;
                currentImageIndex = imageIndexByMedia[index] ?? -1;

                if (item.type === 'video') {
                    if (mainVideo) {
                        mainVideo.style.display = 'block';
                    }
                    if (mainImg) {
                        mainImg.style.display = 'none';
                    }
                    if (mainOverlay) {
                        mainOverlay.style.display = 'none';
                    }
                    if (mainIframe && item.id) {
                        const src = `https://www.youtube-nocookie.com/embed/${item.id}?rel=0&modestbranding=1`;
                        if (mainIframe.getAttribute('src') !== src) {
                            mainIframe.setAttribute('src', src);
                        }
                    }
                    return;
                }

                if (mainImg) {
                    mainImg.src = item.src;
                    mainImg.style.display = 'block';
                }
                if (mainVideo) {
                    mainVideo.style.display = 'none';
                }
                if (mainOverlay) {
                    mainOverlay.style.display = galleryImages.length > 0 ? 'flex' : 'none';
                }
            }

            if (Array.isArray(galleryItems) && galleryItems.length > 0) {
                setMainMedia(0);
            }

            function openGalleryModal(index = 0) {
                if (!Array.isArray(galleryImages) || galleryImages.length === 0) {
                    return;
                }
                currentModalIndex = index;
                setModalImage(index);
                document.getElementById('galleryModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeGalleryModal() {
                document.getElementById('galleryModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            function setModalImage(index) {
                if (index >= 0 && index < galleryImages.length) {
                    currentModalIndex = index;
                    document.getElementById('modalImage').src = galleryImages[index];
                    document.getElementById('modalCounter').textContent = index + 1;
                    
                    // Update thumbnail highlights
                    document.querySelectorAll('.dh-modal-thumb').forEach((thumb, i) => {
                        thumb.classList.toggle('active', i === index);
                    });
                }
            }

            function nextGalleryImage() {
                let next = (currentModalIndex + 1) % galleryImages.length;
                setModalImage(next);
            }

            function prevGalleryImage() {
                let prev = (currentModalIndex - 1 + galleryImages.length) % galleryImages.length;
                setModalImage(prev);
            }

            function setMainImage(src, index) {
                setMainMedia(index);
            }

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (document.getElementById('galleryModal').style.display === 'flex') {
                    if (e.key === 'ArrowRight') nextGalleryImage();
                    if (e.key === 'ArrowLeft') prevGalleryImage();
                    if (e.key === 'Escape') closeGalleryModal();
                }
            });
        </script>
    <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
