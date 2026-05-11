<?php
// Test script to verify image paths
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get events with images
$stmt = $db->query("SELECT id, title, image_url FROM events WHERE image_url IS NOT NULL AND image_url != ''");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Image Path Test</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Original Path</th><th>Fixed Path</th><th>Image</th></tr>";

foreach ($events as $event) {
    $originalPath = $event['image_url'];
    
    // Apply the same fix logic
    $imagePath = $event['image_url'];
    if (strpos($imagePath, 'admin/') !== 0) {
        $imagePath = 'admin/' . $imagePath;
    }
    
    echo "<tr>";
    echo "<td>{$event['id']}</td>";
    echo "<td>{$event['title']}</td>";
    echo "<td>$originalPath</td>";
    echo "<td>$imagePath</td>";
    echo "<td><img src='$imagePath' width='60' height='50' onerror=\"this.style.backgroundColor='red'\" /></td>";
    echo "</tr>";
}

echo "</table>";

// Check if files actually exist
echo "<h2>File Existence Check</h2>";
foreach ($events as $event) {
    $filePath = '../' . $event['image_url'];
    if (!file_exists($filePath)) {
        // Try with admin prefix
        $filePath = '../admin/' . $event['image_url'];
    }
    
    echo "<p>{$event['title']}: " . (file_exists($filePath) ? "EXISTS" : "NOT FOUND") . " - $filePath</p>";
}
?>
