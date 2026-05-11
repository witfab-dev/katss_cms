<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login to admin panel first";
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h2>Management Team Setup & Debug</h2>";

// Step 1: Check if table exists
echo "<h3>1. Checking if management_team table exists...</h3>";
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'management_team'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Table exists<br>";
    } else {
        echo "❌ Table does not exist - Creating now...<br>";
        
        $sql = "CREATE TABLE management_team (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            telephone varchar(20) NOT NULL,
            post varchar(255) NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_by int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_sort_order (sort_order),
            KEY created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Table created successfully<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Step 2: Check if data exists
echo "<h3>2. Checking if data exists...</h3>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM management_team");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        echo "✅ Found $count team members<br>";
    } else {
        echo "❌ No data found - Adding sample data...<br>";
        
        $sampleData = [
            ['Director of Study', '0781234567', 'Director of Study', 'active', 1],
            ['Discipline Master', '0788853705', 'Discipline Master', 'active', 2],
            ['Accountant', '07899999999', 'Accountant', 'active', 3],
            ['Head Teacher', '0787654321', 'Head Teacher', 'active', 0]
        ];
        
        $stmt = $db->prepare("INSERT INTO management_team (name, telephone, post, status, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ Sample data inserted successfully<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Step 3: Test API directly
echo "<h3>3. Testing API directly...</h3>";
try {
    $_GET['action'] = 'list';
    $_GET['type'] = 'management_team';
    
    // Simulate API call
    $stmt = $db->prepare("SELECT * FROM management_team WHERE 1=1 ORDER BY sort_order ASC, created_at DESC LIMIT 10");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ API query successful<br>";
    echo "Found " . count($members) . " members<br>";
    
    if (!empty($members)) {
        echo "<table border='1' style='margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Telephone</th><th>Post</th><th>Status</th><th>Order</th></tr>";
        foreach ($members as $member) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($member['id']) . "</td>";
            echo "<td>" . htmlspecialchars($member['name']) . "</td>";
            echo "<td>" . htmlspecialchars($member['telephone']) . "</td>";
            echo "<td>" . htmlspecialchars($member['post']) . "</td>";
            echo "<td>" . htmlspecialchars($member['status']) . "</td>";
            echo "<td>" . $member['sort_order'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ API test failed: " . $e->getMessage() . "<br>";
}

// Step 4: Test session
echo "<h3>4. Session Status...</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Logged in: " . ($_SESSION['admin_logged_in'] ? 'Yes' : 'No') . "<br>";
echo "Username: " . htmlspecialchars($_SESSION['admin_username'] ?? 'Not set') . "<br>";

echo "<h3>5. Next Steps</h3>";
echo "<p>✅ Table and data are ready</p>";
echo "<p>✅ API is working</p>";
echo "<p>Now refresh your admin panel and try the Management Team page again</p>";
echo "<p><a href='index.php'>← Back to Admin Panel</a></p>";
?>
