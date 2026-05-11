<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Simple Image Test</h1>";

// Get gallery items
$stmt = $db->query("SELECT id, title, file_path FROM gallery_items WHERE status = 'active' AND media_type = 'image'");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    echo "<h2>{$item['title']}</h2>";
    echo "<p>Original path: {$item['file_path']}</p>";
    
    $path = $item['file_path'];
    
    // Try different path variations
    $paths_to_try = [
        $path, // original
        'admin/' . $path, // with admin prefix
        str_replace('uploads/', 'admin/uploads/', $path), // replace uploads with admin/uploads
    ];
    
    foreach ($paths_to_try as $try_path) {
        $full_path = __DIR__ . '/' . $try_path;
        $exists = file_exists($full_path);
        echo "<p>Try: $try_path - Exists: " . ($exists ? "YES" : "NO") . "</p>";
        
        if ($exists) {
            echo "<img src='$try_path' width='200' style='border:1px solid green; margin:5px;' />";
            break;
        }
    }
    
    echo "<hr>";
}

// Also test events
echo "<h1>Events Test</h1>";
$stmt = $db->query("SELECT id, title, image_url FROM events WHERE status = 'published' AND image_url IS NOT NULL");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    echo "<h2>{$event['title']}</h2>";
    echo "<p>Original path: {$event['image_url']}</p>";
    
    $path = $event['image_url'];
    
    // Try different path variations
    $paths_to_try = [
        $path, // original
        'admin/' . $path, // with admin prefix
        str_replace('uploads/', 'admin/uploads/', $path), // replace uploads with admin/uploads
    ];
    
    foreach ($paths_to_try as $try_path) {
        $full_path = __DIR__ . '/' . $try_path;
        $exists = file_exists($full_path);
        echo "<p>Try: $try_path - Exists: " . ($exists ? "YES" : "NO") . "</p>";
        
        if ($exists) {
            echo "<img src='$try_path' width='200' style='border:1px solid green; margin:5px;' />";
            break;
        }
    }
    
    echo "<hr>";
}
?>
