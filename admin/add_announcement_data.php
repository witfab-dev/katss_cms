<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

echo "<h2>Adding Sample Announcement Data</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if announcements table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'announcements'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table
        $sql = "CREATE TABLE announcements (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            priority enum('low','medium','high') DEFAULT 'medium',
            created_by int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Announcements table created<br>";
    }
    
    // Check current data count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Current announcements count: $count<br>";
    
    if ($count == 0) {
        // Add sample announcements
        $sampleAnnouncements = [
            [
                'title' => 'Welcome to KATSS Digital Platform',
                'content' => 'We are excited to announce the launch of our new digital platform for better communication with parents and students. This platform provides real-time updates on school activities, academic progress, and important announcements.',
                'priority' => 'high',
                'status' => 'active'
            ],
            [
                'title' => 'School Calendar Update',
                'content' => 'The academic calendar for the upcoming term has been updated. Please check the admissions page for important dates including registration deadlines, exam schedules, and holiday periods.',
                'priority' => 'medium',
                'status' => 'active'
            ],
            [
                'title' => 'Enrollment Now Open for 2025-2026',
                'content' => 'Applications for the 2025-2026 academic year are now being accepted. Limited spaces available in our technical programs including Software Development, Accounting, Multimedia, and Automotive Technology.',
                'priority' => 'high',
                'status' => 'active'
            ],
            [
                'title' => 'New Technical Equipment Arrived',
                'content' => 'We have recently acquired state-of-the-art technical equipment for our workshops and laboratories. This includes new computers, engineering tools, and multimedia equipment to enhance practical learning.',
                'priority' => 'medium',
                'status' => 'active'
            ],
            [
                'title' => 'Parent-Teacher Meeting Schedule',
                'content' => 'Regular parent-teacher meetings will be held every last Friday of the month. This is an opportunity to discuss your child\'s progress and address any concerns with teachers and administration.',
                'priority' => 'low',
                'status' => 'active'
            ]
        ];
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($sampleAnnouncements as $announcement) {
            $stmt->execute([
                $announcement['title'],
                $announcement['content'],
                $announcement['priority'],
                $announcement['status'],
                $_SESSION['admin_id']
            ]);
        }
        
        echo "✅ Added " . count($sampleAnnouncements) . " sample announcements<br>";
    } else {
        echo "ℹ️ Announcements already exist<br>";
    }
    
    // Verify data
    $stmt = $db->prepare("SELECT * FROM announcements ORDER BY priority DESC, created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Announcements:</h3>";
    echo "<table border='1' style='width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($announcements as $announcement) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($announcement['id']) . "</td>";
        echo "<td>" . htmlspecialchars($announcement['title']) . "</td>";
        echo "<td>" . htmlspecialchars($announcement['priority']) . "</td>";
        echo "<td>" . htmlspecialchars($announcement['status']) . "</td>";
        echo "<td>" . $announcement['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>🎯 Next Steps</h3>";
    echo "<p><a href='index.php#announcements'>→ Go to Announcements in Admin Panel</a></p>";
    echo "<p><a href='../public/index.php#news-events'>→ View Public Website</a></p>";
    echo "<p><strong>The golden announcement icon should now show data!</strong></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
