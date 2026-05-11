<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Verify Image Resolution</h1>";

// Test events
echo "<h2>Events</h2>";
$stmt = $db->query("SELECT id, title, image_url FROM events WHERE status = 'published' AND image_url IS NOT NULL");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    echo "<h3>{$event['title']}</h3>";
    echo "<p>Original: {$event['image_url']}</p>";
    
    $resolved = event_image($event);
    echo "<p>Resolved: $resolved</p>";
    
    echo "<img src='$resolved' width='200' style='border:1px solid #ccc; margin:5px;' />";
    echo "<hr>";
}

// Test gallery
echo "<h2>Gallery</h2>";
$stmt = $db->query("SELECT id, title, file_path, media_url FROM gallery_items WHERE status = 'active' AND media_type = 'image'");
$gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($gallery as $item) {
    echo "<h3>{$item['title']}</h3>";
    echo "<p>File Path: " . ($item['file_path'] ?? 'NULL') . "</p>";
    echo "<p>Media URL: " . ($item['media_url'] ?? 'NULL') . "</p>";
    
    $resolved = gallery_image($item);
    echo "<p>Resolved: $resolved</p>";
    
    echo "<img src='$resolved' width='200' style='border:1px solid #ccc; margin:5px;' />";
    echo "<hr>";
}

// Include the functions
function resolve_image(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    
    // External URLs pass through unchanged
    if (preg_match('#^https?://#i', $raw)) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    
    // Ensure proper base path for images - same logic as admin
    if (strpos($raw, 'admin/') !== 0) {
        $raw = 'admin/' . $raw;
    }
    
    return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
}

function event_image(array $event): string {
    $fallback = 'https://placehold.co/800x450/003366/D4AF37?text=KATSS+Event';
    foreach (['image_url', 'image_path', 'thumbnail_path'] as $col) {
        if (!empty($event[$col])) return resolve_image($event[$col], $fallback);
    }
    return $fallback;
}

function gallery_image(array $item): string {
    $title = urlencode($item['title'] ?? 'Gallery');
    $fallback = "https://placehold.co/600x400/003366/D4AF37?text={$title}";
    
    // Check file_path FIRST since that's what's actually populated in database
    foreach (['file_path', 'media_url', 'thumbnail_path'] as $col) {
        if (!empty($item[$col])) {
            $resolved = resolve_image($item[$col], $fallback);
            return $resolved;
        }
    }
    return $fallback;
}
?>
