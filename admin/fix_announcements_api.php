<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

echo "<h2>Fixing Announcements API Issues</h2>";

// Step 1: Create table if it doesn't exist
echo "<h3>Step 1: Creating announcements table...</h3>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS announcements (
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
    echo "✅ Table created/verified<br>";
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "<br>";
    exit();
}

// Step 2: Add sample data if empty
echo "<h3>Step 2: Adding sample data...</h3>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $sampleData = [
            ['Welcome to KATSS Digital Platform', 'We are excited to announce the launch of our new digital platform for better communication with parents and students.', 'high', 'active'],
            ['School Calendar Update', 'The academic calendar for the upcoming term has been updated. Please check important dates.', 'medium', 'active'],
            ['New Technical Equipment', 'We have recently acquired state-of-the-art technical equipment for our workshops.', 'medium', 'active'],
            ['Parent-Teacher Meetings', 'Regular parent-teacher meetings will be held every last Friday of the month.', 'low', 'active']
        ];
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach ($sampleData as $data) {
            $stmt->execute([$data[0], $data[1], $data[2], $data[3], $_SESSION['admin_id']]);
        }
        
        echo "✅ Sample data inserted<br>";
    } else {
        echo "✅ Table already has $count records<br>";
    }
} catch (Exception $e) {
    echo "❌ Error adding sample data: " . $e->getMessage() . "<br>";
}

// Step 3: Test API response
echo "<h3>Step 3: Testing API response...</h3>";
try {
    // Simulate the exact API call that's failing
    $_GET['action'] = 'list';
    $_GET['type'] = 'announcements';
    
    $stmt = $db->prepare("SELECT * FROM announcements WHERE 1=1 ORDER BY priority DESC, created_at DESC LIMIT 10");
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
        echo "✅ JSON response test passed<br>";
        echo "Response length: " . strlen($json) . " characters<br>";
        
        // Test parsing
        $parsed = json_decode($json, true);
        if ($parsed !== null) {
            echo "✅ JSON parsing test passed<br>";
            echo "Found " . count($parsed['data']) . " announcements<br>";
        } else {
            echo "❌ JSON parsing test failed<br>";
        }
    } else {
        echo "❌ JSON encoding failed<br>";
    }
} catch (Exception $e) {
    echo "❌ API test failed: " . $e->getMessage() . "<br>";
}

// Step 4: Create a simple API test endpoint
echo "<h3>Step 4: Creating API test endpoint...</h3>";
$testCode = '<?php
session_start();
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

require_once "../config/database.php";
$database = new Database();
$db = $database->getConnection();

header("Content-Type: application/json");

try {
    $stmt = $db->prepare("SELECT * FROM announcements WHERE status = \"active\" ORDER BY priority DESC, created_at DESC LIMIT 10");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "data" => $announcements,
        "total" => count($announcements)
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>';

file_put_contents('test_announcements_api_simple.php', $testCode);
echo "✅ Created test API endpoint<br>";

echo "<h3>Next Steps:</h3>";
echo "<p>1. <a href='test_announcements_api_simple.php' target='_blank'>Test the simple API endpoint</a></p>";
echo "<p>2. <a href='index.php' onclick=\"window.location.hash='#announcements'\">Go to Announcements page</a></p>";
echo "<p>3. If still getting JSON errors, check browser console for specific error details</p>";
?>
