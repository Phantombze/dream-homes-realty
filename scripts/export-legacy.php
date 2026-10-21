<?php
/**
 * One-off: dumps the old MySQL content to JSON so the rebuild never needs PHP
 * or MySQL again. Run against a restored copy of www_dreamhomes.sql:
 *
 *   php scripts/export-legacy.php 127.0.0.1 3307 dreamhomes root "" > scripts/legacy-export.json
 *
 * Settings whose key starts with smtp. / brevo. / security. are left out on
 * purpose: the old site stored a mail password in plain text there.
 */
[$_, $host, $port, $name, $user, $pass] = array_pad($argv, 6, '');
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$q = fn (string $sql) => $pdo->query($sql)->fetchAll();

$settings = [];
foreach ($q('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key') as $row) {
    if (preg_match('/^(smtp|brevo|security|email)\./', $row['setting_key'])) {
        continue;
    }
    $settings[$row['setting_key']] = $row['setting_value'];
}

echo json_encode([
    'listings' => $q('SELECT * FROM listings ORDER BY id'),
    'listing_images' => $q('SELECT * FROM listing_images ORDER BY listing_id, is_primary DESC, sort_order, id'),
    'categories' => $q('SELECT * FROM categories ORDER BY id'),
    'listing_categories' => $q('SELECT listing_id, category_id FROM listing_categories ORDER BY listing_id'),
    'settings' => $settings,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
