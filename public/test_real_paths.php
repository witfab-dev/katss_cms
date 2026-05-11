<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Testing Real Database Paths</h1>";

// Test with exact paths from your database
$test_paths = [
    'admin/uploads/events/1778157577_lab.jpg',    // Already has admin/
    'admin/uploads/events/1778164619_1000005151.jpg', // Already has admin/
    'uploads/events/1778173435_f7362f3c.jpeg',     // No admin/ prefix
];

echo "<h2>Testing resolve_image function with real paths:</h2>";

foreach ($test_paths as $path) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
    echo "<h4>Input: $path</h4>";
    
    // Apply current resolve_image function
    $raw = trim($path);
    if ($raw === '') {
        echo "<p>Empty path</p>";
        continue;
    }
    
    // External URLs pass through unchanged
    if (preg_match('#^https?://#i', $raw)) {
        $result = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
        echo "<p>External URL: $result</p>";
    } else {
        // Normalize path - remove leading slashes and dots
        $clean_path = ltrim($raw, '/');
        $clean_path = preg_replace('#^(\.\./|\./)+#', '', $clean_path);
        
        // If path already has admin/, keep it as is
        if (strpos($clean_path, 'admin/') === 0) {
            $finalPath = $clean_path;
            echo "<p>Already has admin/ - keeping as is: $finalPath</p>";
        } else {
            $finalPath = 'admin/' . $clean_path;
            echo "<p>No admin/ prefix - adding: $finalPath</p>";
        }
        
        $result = htmlspecialchars($finalPath, ENT_QUOTES, 'UTF-8');
        echo "<p>Final result: $result</p>";
        
        // Check if file exists
        $fullPath = __DIR__ . '/' . $finalPath;
        $exists = file_exists($fullPath);
        echo "<p>File exists: " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        echo "<p>Full path: $fullPath</p>";
        
        if ($exists) {
            echo "<img src='$result' width='200' style='border:2px solid green; margin:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold;'>❌ FILE NOT FOUND</div>";
        }
    }
    
    echo "</div>";
}

// Test with actual database data
echo "<h2>Testing with actual database events:</h2>";
try {
    $stmt = $db->prepare("SELECT id, title, image_url FROM events WHERE image_url IS NOT NULL AND image_url != ''");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($events as $event) {
        echo "<div style='border:1px solid #003366; padding:10px; margin:10px;'>";
        echo "<h4>{$event['title']} (ID: {$event['id']})</h4>";
        echo "<p><strong>DB image_url:</strong> {$event['image_url']}</p>";
        
        // Use the same resolve_image function from index.php
        $resolved = resolve_image($event['image_url']);
        echo "<p><strong>Resolved to:</strong> $resolved</p>";
        
        $fullPath = __DIR__ . '/' . $resolved;
        $exists = file_exists($fullPath);
        echo "<p><strong>File exists:</strong> " . ($exists ? "✅ YES" : "❌ NO") . "</p>";
        
        if ($exists) {
            echo "<img src='$resolved' width='200' style='border:2px solid green; margin:5px;' />";
        } else {
            echo "<div style='color:red; font-weight:bold;'>❌ IMAGE NOT FOUND</div>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . $e->getMessage() . "</p>";
}

// Copy the resolve_image function for testing
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
?>
