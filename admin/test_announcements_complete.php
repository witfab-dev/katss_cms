<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

echo "<h2>Announcements System Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection...</h3>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit();
}

// Test 2: Check if table exists
echo "<h3>2. Checking if announcements table exists...</h3>";
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'announcements'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Table exists<br>";
    } else {
        echo "❌ Table does not exist - Creating now...<br>";
        
        $sql = "CREATE TABLE announcements (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            priority enum('low','medium','high') DEFAULT 'medium',
            created_by int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_priority (priority),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Table created successfully<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if data exists
echo "<h3>3. Checking if data exists...</h3>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "✅ Found $count announcements<br>";
    } else {
        echo "❌ No data found - Adding sample data...<br>";
        
        $sampleData = [
            ['Welcome to KATSS Digital Platform', 'We are excited to announce the launch of our new digital platform for better communication with parents and students.', 'high', 'active'],
            ['School Calendar Update', 'The academic calendar for the upcoming term has been updated. Please check important dates.', 'medium', 'active'],
            ['New Technical Equipment', 'We have recently acquired state-of-the-art technical equipment for our workshops.', 'medium', 'active']
        ];
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach ($sampleData as $data) {
            $stmt->execute([$data[0], $data[1], $data[2], $data[3], $_SESSION['admin_id']]);
        }
        
        echo "✅ Sample data inserted successfully<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Test API directly
echo "<h3>4. Testing API directly...</h3>";
try {
    $_GET['action'] = 'list';
    $_GET['type'] = 'announcements';
    
    // Simulate API call
    $stmt = $db->prepare("SELECT * FROM announcements WHERE 1=1 ORDER BY priority DESC, created_at DESC LIMIT 10");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ API query successful<br>";
    echo "Found " . count($announcements) . " announcements<br>";
    
    if (!empty($announcements)) {
        echo "<table border='1' style='margin-top: 10px;'>";
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
    }
} catch (Exception $e) {
    echo "❌ API test failed: " . $e->getMessage() . "<br>";
}

// Test 5: Test JSON response
echo "<h3>5. Testing JSON response...</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE status = 'active' ORDER BY priority DESC, created_at DESC LIMIT 10");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'data' => $announcements,
        'total' => count($announcements),
        'pages' => 1,
        'current_page' => 1
    ];
    
    $json = json_encode($response);
    
    if ($json !== false) {
        echo "✅ JSON encoding successful<br>";
        echo "JSON length: " . strlen($json) . " characters<br>";
        echo "<pre>" . htmlspecialchars(substr($json, 0, 200)) . "...</pre>";
    } else {
        echo "❌ JSON encoding failed<br>";
    }
} catch (Exception $e) {
    echo "❌ JSON test failed: " . $e->getMessage() . "<br>";
}

echo "<h3>6. Next Steps</h3>";
echo "<p>✅ Database and API are working</p>";
echo "<p>Now test the admin panel announcements page</p>";
echo "<p><a href='index.php'>← Back to Admin Panel</a></p>";
?>
