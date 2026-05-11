<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login to admin panel first";
    exit();
}

echo "<h2>Quick Announcements System Test</h2>";

// Test 1: Database and table
echo "<h3>1. Database Connection & Table</h3>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Create table if needed
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id int(11) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        content text NOT NULL,
        status enum('active','inactive') DEFAULT 'active',
        priority enum('low','medium','high') DEFAULT 'medium',
        created_by int(11) DEFAULT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✅ Database and table ready<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit();
}

// Test 2: Add sample data if empty
echo "<h3>2. Sample Data</h3>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $sampleData = [
            ['Welcome to KATSS', 'We are excited to announce our new digital platform for better communication.', 'high', 'active'],
            ['School Calendar Updated', 'The academic calendar for next term is now available.', 'medium', 'active'],
            ['New Equipment Arrived', 'State-of-the-art technical equipment has been installed in our workshops.', 'medium', 'active']
        ];
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach ($sampleData as $data) {
            $stmt->execute([$data[0], $data[1], $data[2], $data[3], $_SESSION['admin_id']]);
        }
        
        echo "✅ Sample data added<br>";
    } else {
        echo "✅ Found $count existing announcements<br>";
    }
} catch (Exception $e) {
    echo "❌ Data error: " . $e->getMessage() . "<br>";
}

// Test 3: API functionality
echo "<h3>3. API Test</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE status = 'active' ORDER BY priority DESC, created_at DESC LIMIT 5");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'data' => $announcements,
        'total' => count($announcements)
    ];
    
    $json = json_encode($response);
    echo "✅ API working - JSON length: " . strlen($json) . " chars<br>";
    
    if (!empty($announcements)) {
        echo "✅ Sample announcements:<br>";
        foreach ($announcements as $a) {
            echo "- " . htmlspecialchars($a['title']) . " (" . $a['priority'] . ")<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ API error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Next Steps</h3>";
echo "<p><a href='index.php#announcements'>→ Go to Announcements in Admin Panel</a></p>";
echo "<p><a href='../public/index.php#news-events'>→ View Public Website</a></p>";
echo "<p><strong>The golden announcement icon should appear on the public website!</strong></p>";
?>
