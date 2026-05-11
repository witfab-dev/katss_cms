<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Test events
echo "<h2>Events Image Test</h2>";
$stmt = $db->query("SELECT id, title, image_url FROM events WHERE image_url IS NOT NULL AND image_url != '' LIMIT 5");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>DB Path</th><th>Resolved Path</th><th>Image</th><th>File Exists</th></tr>";

foreach ($events as $event) {
    // Use the exact same resolve_image logic from index.php
    $raw = trim($event['image_url']);
    if ($raw === '') continue;
    
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
        $possiblePaths[] = 'admin/' . $path;
        $possiblePaths[] = str_replace('uploads/events/', 'admin/uploads/events/', $path);
    }
    
    // Try just the filename with admin/uploads/
    $filename = basename($path);
    if (strpos($path, 'events') !== false) {
        $possiblePaths[] = 'admin/uploads/events/' . $filename;
    }
    
    // Remove duplicates
    $possiblePaths = array_unique($possiblePaths);
    
    $finalPath = '';
    $exists = false;
    
    // Test each path
    foreach ($possiblePaths as $tryPath) {
        $fullPath = __DIR__ . '/' . $tryPath;
        if (file_exists($fullPath)) {
            $finalPath = $tryPath;
            $exists = true;
            break;
        }
    }
    
    // If nothing works, use admin/ prefix as default
    if (!$finalPath && strpos($path, 'admin/') !== 0) {
        $finalPath = 'admin/' . $path;
    } elseif (!$finalPath) {
        $finalPath = $path;
    }
    
    echo "<tr>";
    echo "<td>{$event['id']}</td>";
    echo "<td>{$event['title']}</td>";
    echo "<td>{$event['image_url']}</td>";
    echo "<td>$finalPath</td>";
    echo "<td><img src='$finalPath' width='80' height='60' onerror=\"this.style.backgroundColor='red'\" /></td>";
    echo "<td>" . ($exists ? "YES" : "NO") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test gallery
echo "<h2>Gallery Image Test</h2>";
$stmt = $db->query("SELECT id, title, media_url, file_path FROM gallery_items WHERE status = 'active' LIMIT 5");
$gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Media URL</th><th>File Path</th><th>Resolved Path</th><th>Image</th><th>File Exists</th></tr>";

foreach ($gallery as $item) {
    $raw = !empty($item['media_url']) ? $item['media_url'] : $item['file_path'];
    
    if ($raw) {
        // Use the exact same resolve_image logic from index.php
        $raw = trim($raw);
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
        if (strpos($path, 'uploads/gallery/') === 0) {
            $possiblePaths[] = 'admin/' . $path;
            $possiblePaths[] = str_replace('uploads/gallery/', 'admin/uploads/gallery/', $path);
        }
        
        // Try just the filename with admin/uploads/
        $filename = basename($path);
        if (strpos($path, 'gallery') !== false) {
            $possiblePaths[] = 'admin/uploads/gallery/' . $filename;
        }
        
        // Remove duplicates
        $possiblePaths = array_unique($possiblePaths);
        
        $finalPath = '';
        $exists = false;
        
        // Test each path
        foreach ($possiblePaths as $tryPath) {
            $fullPath = __DIR__ . '/' . $tryPath;
            if (file_exists($fullPath)) {
                $finalPath = $tryPath;
                $exists = true;
                break;
            }
        }
        
        // If nothing works, use admin/ prefix as default
        if (!$finalPath && strpos($path, 'admin/') !== 0) {
            $finalPath = 'admin/' . $path;
        } elseif (!$finalPath) {
            $finalPath = $path;
        }
        
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['title']}</td>";
        echo "<td>" . ($item['media_url'] ?? '') . "</td>";
        echo "<td>" . ($item['file_path'] ?? '') . "</td>";
        echo "<td>$finalPath</td>";
        echo "<td><img src='$finalPath' width='80' height='60' onerror=\"this.style.backgroundColor='red'\" /></td>";
        echo "<td>" . ($exists ? "YES" : "NO") . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
?>
