<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

echo "<h2>🔍 API Response Debug Tool</h2>";

// Test the exact API call that's failing
echo "<h3>Testing Management Team API</h3>";

// Simulate the exact request
$_GET['action'] = 'list';
$_GET['type'] = 'management_team';
$_GET['page'] = '1';
$_GET['search'] = '';
$_GET['status'] = '';

echo "<p><strong>Request parameters:</strong></p>";
echo "<pre>";
echo "action: " . $_GET['action'] . "\n";
echo "type: " . $_GET['type'] . "\n";
echo "page: " . $_GET['page'] . "\n";
echo "search: '" . $_GET['search'] . "'\n";
echo "status: '" . $_GET['status'] . "'\n";
echo "</pre>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit();
}

// Test table existence
echo "<h3>Table Existence Test</h3>";
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'management_team'");
    $stmt->execute();
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "✅ management_team table exists<br>";
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE management_team");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Table structure:</strong></p>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ management_team table does not exist<br>";
        
        // Create table
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
            KEY idx_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Table created<br>";
    }
} catch (Exception $e) {
    echo "❌ Table error: " . $e->getMessage() . "<br>";
}

// Test data existence
echo "<h3>Data Test</h3>";
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM management_team");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Found $count records in management_team<br>";
    
    if ($count == 0) {
        // Add sample data
        $sampleData = [
            ['Dr. John Smith', '0781234567', 'Director of Study', 'active', 1],
            ['Mr. Robert Johnson', '0788853705', 'Discipline Master', 'active', 2],
            ['Ms. Mary Williams', '07899999999', 'Accountant', 'active', 3]
        ];
        
        $stmt = $db->prepare("INSERT INTO management_team (name, telephone, post, status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "✅ Sample data added<br>";
    }
} catch (Exception $e) {
    echo "❌ Data error: " . $e->getMessage() . "<br>";
}

// Test the exact query
echo "<h3>Query Test</h3>";
try {
    $search = $_GET['search'];
    $status = $_GET['status'];
    $page = max(1, (int)$_GET['page']);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $params = [];
    
    $sql = "SELECT * FROM management_team WHERE 1=1";
    $countSql = "SELECT COUNT(*) FROM management_team WHERE 1=1";
    
    if ($search) {
        $sql .= " AND (name LIKE :search OR post LIKE :search2)";
        $countSql .= " AND (name LIKE :search OR post LIKE :search2)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
    }
    if ($status) {
        $sql .= " AND status = :status";
        $countSql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    $sql .= " ORDER BY sort_order ASC, created_at DESC LIMIT $limit OFFSET $offset";
    
    echo "<p><strong>SQL Query:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    if (!empty($params)) {
        echo "<p><strong>Parameters:</strong></p>";
        echo "<pre>";
        print_r($params);
        echo "</pre>";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Query Results:</strong></p>";
    echo "Found " . count($teamMembers) . " team members<br>";
    
    if (!empty($teamMembers)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Telephone</th><th>Post</th><th>Status</th><th>Order</th></tr>";
        foreach ($teamMembers as $member) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($member['id']) . "</td>";
            echo "<td>" . htmlspecialchars($member['name']) . "</td>";
            echo "<td>" . htmlspecialchars($member['telephone']) . "</td>";
            echo "<td>" . htmlspecialchars($member['post']) . "</td>";
            echo "<td>" . htmlspecialchars($member['status']) . "</td>";
            echo "<td>" . htmlspecialchars($member['sort_order']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test JSON response
    $response = [
        'success' => true,
        'data' => $teamMembers,
        'total' => count($teamMembers),
        'pages' => 1,
        'current_page' => $page
    ];
    
    $json = json_encode($response);
    
    echo "<h3>JSON Response Test</h3>";
    echo "<p><strong>JSON Length:</strong> " . strlen($json) . " characters</p>";
    
    if ($json !== false) {
        echo "✅ JSON encoding successful<br>";
        
        // Test parsing
        $parsed = json_decode($json, true);
        if ($parsed !== null) {
            echo "✅ JSON parsing successful<br>";
            echo "<p><strong>Response structure:</strong></p>";
            echo "<pre>";
            echo json_encode($parsed, JSON_PRETTY_PRINT);
            echo "</pre>";
        } else {
            echo "❌ JSON parsing failed<br>";
        }
    } else {
        echo "❌ JSON encoding failed<br>";
        echo "Last JSON error: " . json_last_error_msg() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
    echo "Error details: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>🎯 Next Steps</h3>";
echo "<p>If all tests pass above, the API should work. If there are still issues:</p>";
echo "<ol>";
echo "<li>Check PHP error logs</li>";
echo "<li>Verify file permissions</li>";
echo "<li>Test the simple API: <a href='test_api_simple.php?type=management_team' target='_blank'>Test Simple API</a></li>";
echo "<li>Go back to admin panel: <a href='index.php#management-team'>Admin Panel</a></li>";
echo "</ol>";
?>
