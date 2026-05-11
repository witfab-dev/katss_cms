<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

echo "<h2>Quick Dashboard Test</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Test stats API directly
    $eventsStmt = $db->prepare("SELECT COUNT(*) as total, SUM(is_featured) as featured FROM events");
    $eventsStmt->execute();
    $eventsStats = $eventsStmt->fetch(PDO::FETCH_ASSOC);
    
    $galleryStmt = $db->prepare("SELECT COUNT(*) as total, SUM(status = 'active') as active FROM gallery_items");
    $galleryStmt->execute();
    $galleryStats = $galleryStmt->fetch(PDO::FETCH_ASSOC);
    
    $statsResponse = [
        'success' => true,
        'data' => [
            'total_events' => (int)$eventsStats['total'],
            'featured_events' => (int)$eventsStats['featured'],
            'total_gallery' => (int)$galleryStats['total'],
            'active_gallery' => (int)$galleryStats['active']
        ]
    ];
    
    $json = json_encode($statsResponse);
    echo "✅ Stats API working: " . strlen($json) . " chars<br>";
    echo "<pre>" . json_encode($statsResponse, JSON_PRETTY_PRINT) . "</pre>";
    
    // Test announcements
    $stmt = $db->prepare("SELECT * FROM announcements ORDER BY priority DESC, created_at DESC LIMIT 5");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Announcements Data:</h3>";
    echo "Found " . count($announcements) . " announcements<br>";
    
    if (count($announcements) === 0) {
        echo "⚠️ Adding sample announcements...<br>";
        
        $sampleData = [
            ['Welcome to KATSS', 'We are excited to announce our new digital platform', 'high', 'active'],
            ['School Calendar', 'The academic calendar has been updated', 'medium', 'active'],
            ['New Equipment', 'Technical equipment has been installed', 'medium', 'active']
        ];
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ Added 3 sample announcements<br>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Title</th><th>Priority</th><th>Status</th></tr>";
        foreach ($announcements as $a) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($a['title']) . "</td>";
            echo "<td>" . htmlspecialchars($a['priority']) . "</td>";
            echo "<td>" . htmlspecialchars($a['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🎯 Test Results:</h3>";
    echo "<p><a href='index.php' target='_blank'>→ Test Admin Dashboard</a></p>";
    echo "<p><a href='api_simple.php?action=stats' target='_blank'>→ Test Stats API</a></p>";
    echo "<p><a href='api_simple.php?action=list&type=announcements' target='_blank'>→ Test Announcements API</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
