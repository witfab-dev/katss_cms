<?php
// Simple debug script to check image paths
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Image Path Debug</h1>";

// Test events
echo "<h2>Events</h2>";
$stmt = $db->query("SELECT id, title, image_url FROM events WHERE image_url IS NOT NULL AND image_url != ''");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as $event) {
    echo "<h3>Event: {$event['title']} (ID: {$event['id']})</h3>";
    echo "<p>DB Path: {$event['image_url']}</p>";
    
    // Test the resolve_image function
    $resolved = resolve_image($event['image_url'], 'placeholder');
    echo "<p>Resolved: $resolved</p>";
    
    $fullPath = __DIR__ . '/' . $resolved;
    echo "<p>Full Path: $fullPath</p>";
    echo "<p>File Exists: " . (file_exists($fullPath) ? "YES" : "NO") . "</p>";
    
    if (file_exists($fullPath)) {
        echo "<img src='$resolved' width='200' style='border:1px solid #ccc;' />";
    } else {
        echo "<div style='color:red;'>Image not found!</div>";
    }
    echo "<hr>";
}

// Test gallery
echo "<h2>Gallery</h2>";
$stmt = $db->query("SELECT id, title, media_url, file_path FROM gallery_items WHERE status = 'active'");
$gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($gallery as $item) {
    echo "<h3>Gallery: {$item['title']} (ID: {$item['id']})</h3>";
    echo "<p>Media URL: " . ($item['media_url'] ?? 'NULL') . "</p>";
    echo "<p>File Path: " . ($item['file_path'] ?? 'NULL') . "</p>";
    
    $raw = !empty($item['media_url']) ? $item['media_url'] : $item['file_path'];
    if ($raw) {
        $resolved = resolve_image($raw, 'placeholder');
        echo "<p>Resolved: $resolved</p>";
        
        $fullPath = __DIR__ . '/' . $resolved;
        echo "<p>Full Path: $fullPath</p>";
        echo "<p>File Exists: " . (file_exists($fullPath) ? "YES" : "NO") . "</p>";
        
        if (file_exists($fullPath)) {
            echo "<img src='$resolved' width='200' style='border:1px solid #ccc;' />";
        } else {
            echo "<div style='color:red;'>Image not found!</div>";
        }
    } else {
        echo "<div style='color:orange;'>No image path found</div>";
    }
    echo "<hr>";
}

// Include the resolve_image function
function resolve_image(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    
    // External URLs pass through unchanged
    if (preg_match('#^https?://#i', $raw)) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    
    $path = ltrim($raw, '/');
    $path = preg_replace('#^(\.\./|\./)+#', '', $path);
    
    // List of possible paths to try in order
    $possiblePaths = [];
    
    // If path already has admin/, keep it as is
    if (strpos($path, 'admin/') === 0) {
        $possiblePaths[] = $path;
    } else {
        // Try with admin/ prefix first (most likely)
        $possiblePaths[] = 'admin/' . $path;
    }
    
    // Try the original path (in case it's already correct)
    $possiblePaths[] = $path;
    
    // Try specific variations for common patterns
    if (strpos($path, 'uploads/events/') === 0) {
        $possiblePaths[] = 'admin/' . $path; // admin/uploads/events/filename
        $possiblePaths[] = str_replace('uploads/events/', 'admin/uploads/events/', $path);
    } elseif (strpos($path, 'uploads/gallery/') === 0) {
        $possiblePaths[] = 'admin/' . $path; // admin/uploads/gallery/filename
        $possiblePaths[] = str_replace('uploads/gallery/', 'admin/uploads/gallery/', $path);
    }
    
    // Try just the filename with admin/uploads/
    $filename = basename($path);
    if (strpos($path, 'events') !== false) {
        $possiblePaths[] = 'admin/uploads/events/' . $filename;
    } elseif (strpos($path, 'gallery') !== false) {
        $possiblePaths[] = 'admin/uploads/gallery/' . $filename;
    }
    
    // Remove duplicates
    $possiblePaths = array_unique($possiblePaths);
    
    // Test each path
    foreach ($possiblePaths as $tryPath) {
        $fullPath = __DIR__ . '/' . $tryPath;
        if (file_exists($fullPath)) {
            return htmlspecialchars($tryPath, ENT_QUOTES, 'UTF-8');
        }
    }
    
    // If nothing works, return the first admin/ path as default
    if (strpos($path, 'admin/') !== 0) {
        return htmlspecialchars('admin/' . $path, ENT_QUOTES, 'UTF-8');
    }
    
    return $fallback;
}
?>
