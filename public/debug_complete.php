<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Complete Image Debug</h1>";

// Test database connections and data
echo "<h2>Database Connection Test</h2>";
try {
    $db->query("SELECT 1");
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    die();
}

// Test events data
echo "<h2>Events Data</h2>";
try {
    $stmt = $db->prepare("SELECT id, title, image_url FROM events WHERE status = 'published' AND image_url IS NOT NULL AND image_url != ''");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Found " . count($events) . " events with images</p>";
    
    foreach ($events as $event) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
        echo "<h4>{$event['title']} (ID: {$event['id']})</h4>";
        echo "<p><strong>DB Path:</strong> {$event['image_url']}</p>";
        
        // Test path resolution step by step
        $original = $event['image_url'];
        echo "<p><strong>Step 1 - Original:</strong> $original</p>";
        
        // Clean path
        $clean = ltrim($original, '/');
        $clean = preg_replace('#^(\.\./|\./)+#', '', $clean);
        echo "<p><strong>Step 2 - Cleaned:</strong> $clean</p>";
        
        // Add admin prefix if needed
        if (strpos($clean, 'admin/') !== 0) {
            $final = 'admin/' . $clean;
            echo "<p><strong>Step 3 - Added admin/:</strong> $final</p>";
        } else {
            $final = $clean;
            echo "<p><strong>Step 3 - Already has admin/:</strong> $final</p>";
        }
        
        // Check if file exists
        $fullPath = __DIR__ . '/' . $final;
        $exists = file_exists($fullPath);
        echo "<p><strong>Step 4 - File exists:</strong> " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p><strong>Full path:</strong> $fullPath</p>";
        
        // Test image display
        if ($exists) {
            echo "<img src='$final' width='150' style='border:2px solid green; margin:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold;'>❌ IMAGE NOT FOUND</div>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>❌ Events query failed: " . $e->getMessage() . "</p>";
}

// Test gallery data
echo "<h2>Gallery Data</h2>";
try {
    $stmt = $db->prepare("SELECT id, title, file_path, media_url FROM gallery_items WHERE status = 'active' AND media_type = 'image'");
    $stmt->execute();
    $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Found " . count($gallery) . " gallery images</p>";
    
    foreach ($gallery as $item) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
        echo "<h4>{$item['title']} (ID: {$item['id']})</h4>";
        echo "<p><strong>File Path:</strong> " . ($item['file_path'] ?? 'NULL') . "</p>";
        echo "<p><strong>Media URL:</strong> " . ($item['media_url'] ?? 'NULL') . "</p>";
        
        // Determine which field to use
        $raw = '';
        if (!empty($item['file_path'])) {
            $raw = $item['file_path'];
            echo "<p><strong>Using:</strong> file_path</p>";
        } elseif (!empty($item['media_url'])) {
            $raw = $item['media_url'];
            echo "<p><strong>Using:</strong> media_url</p>";
        } else {
            echo "<p><strong>❌ No image path found!</strong></p>";
            echo "</div>";
            continue;
        }
        
        // Test path resolution step by step
        echo "<p><strong>Step 1 - Original:</strong> $raw</p>";
        
        // Clean path
        $clean = ltrim($raw, '/');
        $clean = preg_replace('#^(\.\./|\./)+#', '', $clean);
        echo "<p><strong>Step 2 - Cleaned:</strong> $clean</p>";
        
        // Add admin prefix if needed
        if (strpos($clean, 'admin/') !== 0) {
            $final = 'admin/' . $clean;
            echo "<p><strong>Step 3 - Added admin/:</strong> $final</p>";
        } else {
            $final = $clean;
            echo "<p><strong>Step 3 - Already has admin/:</strong> $final</p>";
        }
        
        // Check if file exists
        $fullPath = __DIR__ . '/' . $final;
        $exists = file_exists($fullPath);
        echo "<p><strong>Step 4 - File exists:</strong> " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p><strong>Full path:</strong> $fullPath</p>";
        
        // Test image display
        if ($exists) {
            echo "<img src='$final' width='150' style='border:2px solid green; margin:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold;'>❌ IMAGE NOT FOUND</div>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>❌ Gallery query failed: " . $e->getMessage() . "</p>";
}

// Check actual file structure
echo "<h2>File Structure Check</h2>";
$paths_to_check = [
    'admin/uploads/events',
    'admin/uploads/gallery',
    'uploads/events',
    'uploads/gallery'
];

foreach ($paths_to_check as $path) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = is_dir($fullPath);
    echo "<p><strong>$path:</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "</p>";
    
    if ($exists) {
        $files = scandir($fullPath);
        $imageFiles = array_filter($files, function($file) {
            return preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
        });
        echo "<p>→ Image files: " . count($imageFiles) . "</p>";
    }
}
?>
