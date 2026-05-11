<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Final Image Test - All Sections</h1>";

// Test the exact same functions used in index.php
function resolve_image(string $raw, string $fallback = ''): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    
    // External URLs pass through unchanged
    if (preg_match('#^https?://#i', $raw)) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    
    // Normalize path - remove leading slashes and dots
    $path = ltrim($raw, '/');
    $path = preg_replace('#^(\.\./|\./)+#', '', $path);
    
    // If path already has admin/, keep it as is
    if (strpos($path, 'admin/') === 0) {
        $finalPath = $path;
    } else {
        $finalPath = 'admin/' . $path;
    }
    
    return htmlspecialchars($finalPath, ENT_QUOTES, 'UTF-8');
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
    
    // Check file_path FIRST since that's what's actually populated in the database
    foreach (['file_path', 'media_url', 'thumbnail_path'] as $col) {
        if (!empty($item[$col])) {
            $resolved = resolve_image($item[$col], $fallback);
            return $resolved;
        }
    }
    return $fallback;
}

// Test Events
echo "<h2>📸 Events Section Test</h2>";
try {
    $stmt = $db->prepare("SELECT id, title, image_url FROM events WHERE status = 'published' AND image_url IS NOT NULL AND image_url != ''");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($events) . " published events with images</p>";
    
    foreach ($events as $event) {
        echo "<div style='border:2px solid #003366; padding:15px; margin:10px; border-radius:8px;'>";
        echo "<h3 style='color:#003366;'>{$event['title']}</h3>";
        echo "<p><strong>Database:</strong> {$event['image_url']}</p>";
        
        $resolved = event_image($event);
        echo "<p><strong>Resolved:</strong> $resolved</p>";
        
        $fullPath = __DIR__ . '/' . $resolved;
        $exists = file_exists($fullPath);
        echo "<p><strong>File exists:</strong> " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        
        if ($exists) {
            echo "<img src='$resolved' width='250' style='border:3px solid green; margin:10px; border-radius:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold; padding:10px; background:#ffe6e6; border-radius:5px;'>❌ IMAGE FILE NOT FOUND</div>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Events error: " . $e->getMessage() . "</p>";
}

// Test Gallery
echo "<h2>🖼️ Gallery Section Test</h2>";
try {
    $stmt = $db->prepare("SELECT id, title, file_path, media_url FROM gallery_items WHERE status = 'active' AND media_type = 'image'");
    $stmt->execute();
    $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($gallery) . " active gallery images</p>";
    
    foreach ($gallery as $item) {
        echo "<div style='border:2px solid #003366; padding:15px; margin:10px; border-radius:8px;'>";
        echo "<h3 style='color:#003366;'>{$item['title']}</h3>";
        echo "<p><strong>File Path:</strong> " . ($item['file_path'] ?? 'NULL') . "</p>";
        echo "<p><strong>Media URL:</strong> " . ($item['media_url'] ?? 'NULL') . "</p>";
        
        $resolved = gallery_image($item);
        echo "<p><strong>Resolved:</strong> $resolved</p>";
        
        $fullPath = __DIR__ . '/' . $resolved;
        $exists = file_exists($fullPath);
        echo "<p><strong>File exists:</strong> " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        
        if ($exists) {
            echo "<img src='$resolved' width='250' style='border:3px solid green; margin:10px; border-radius:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold; padding:10px; background:#ffe6e6; border-radius:5px;'>❌ IMAGE FILE NOT FOUND</div>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Gallery error: " . $e->getMessage() . "</p>";
}

// Summary
echo "<h2>📋 Summary</h2>";
echo "<p>If all images show ✅ YES and display correctly, the issue is fixed!</p>";
echo "<p>If images show ❌ NO, check the file paths in the database vs actual file locations.</p>";
echo "<p><a href='index.php'>← Back to Website</a></p>";
?>
