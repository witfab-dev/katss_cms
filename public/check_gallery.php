<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Gallery Debug</h1>";

// Check if gallery_items table exists and has data
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM gallery_items");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total gallery items: {$count['count']}</p>";
    
    if ($count['count'] > 0) {
        $stmt = $db->query("SELECT * FROM gallery_items ORDER BY created_at DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>All Gallery Items:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Media URL</th><th>File Path</th><th>Status</th><th>Image Test</th></tr>";
        
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['title']}</td>";
            echo "<td>{$item['media_type']}</td>";
            echo "<td>" . ($item['media_url'] ?? 'NULL') . "</td>";
            echo "<td>" . ($item['file_path'] ?? 'NULL') . "</td>";
            echo "<td>{$item['status']}</td>";
            
            // Test image resolution
            $raw = !empty($item['media_url']) ? $item['media_url'] : $item['file_path'];
            if ($raw) {
                $resolved = resolve_image($raw);
                echo "<td><img src='$resolved' width='80' height='60' onerror=\"this.style.backgroundColor='red'\" /></td>";
            } else {
                echo "<td>No path</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test the actual query used in index.php
echo "<h2>Query Test:</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC LIMIT 12");
    $stmt->execute();
    $gallery_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Active items found: " . count($gallery_items) . "</p>";
    
    if (!empty($gallery_items)) {
        echo "<h3>Active Items:</h3>";
        foreach ($gallery_items as $item) {
            echo "<p>{$item['title']} - {$item['status']}</p>";
        }
    } else {
        echo "<p>No active items found - this is why showing static images!</p>";
    }
} catch (PDOException $e) {
    echo "<p>Query error: " . $e->getMessage() . "</p>";
}

// Include resolve_image function
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
