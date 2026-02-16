<?php
require_once __DIR__ . '/../admin/includes/db.php';

header('Content-Type: application/json');

$categories = db()->query('SELECT * FROM categories ORDER BY created_at DESC')->fetchAll();

$output = [];
foreach ($categories as $category) {
    $output[] = [
        'id' => (int) $category['id'],
        'name' => $category['name'],
        'image' => $category['image_url'],
        'url' => $category['url'],
        'countLabel' => $category['count_label'],
    ];
}

echo json_encode($output);
