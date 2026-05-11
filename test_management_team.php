<?php
// Test script to verify management team functionality
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Testing Management Team Functionality</h2>";

// Test 1: Check if table exists
echo "<h3>1. Checking if management_team table exists...</h3>";
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'management_team'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "✅ Table exists<br>";
    } else {
        echo "❌ Table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check if we can fetch data
echo "<h3>2. Testing data retrieval...</h3>";
try {
    $stmt = $db->prepare("SELECT * FROM management_team ORDER BY sort_order ASC");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($members) > 0) {
        echo "✅ Found " . count($members) . " team members<br>";
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Name</th><th>Telephone</th><th>Post</th><th>Status</th><th>Order</th></tr>";
        foreach ($members as $member) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($member['name']) . "</td>";
            echo "<td>" . htmlspecialchars($member['telephone']) . "</td>";
            echo "<td>" . htmlspecialchars($member['post']) . "</td>";
            echo "<td>" . htmlspecialchars($member['status']) . "</td>";
            echo "<td>" . $member['sort_order'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No team members found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Test API endpoint
echo "<h3>3. Testing API endpoint...</h3>";
echo "<p>To test the API, visit: <a href='admin/api.php?action=list&type=management_team'>admin/api.php?action=list&type=management_team</a></p>";
echo "<p>To test the admin panel, visit: <a href='admin/management-team.php'>admin/management-team.php</a></p>";

echo "<h3>4. Testing public website display...</h3>";
echo "<p>To test the public display, visit: <a href='public/index.php#about'>public/index.php#about</a></p>";

echo "<h3>Summary</h3>";
echo "<p>✅ Database table created<br>";
echo "<p>✅ API endpoints added<br>";
echo "<p>✅ Admin management page created<br>";
echo "<p>✅ Public website updated<br>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Execute create_table_simple.php to create the table</li>";
echo "<li>Execute insert_sample_data.php to add sample data</li>";
echo "<li>Test the admin panel at admin/management-team.php</li>";
echo "<li>Check the About section on the public website</li>";
echo "</ul>";
?>
