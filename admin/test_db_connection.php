<?php
// Test database connection and tables
require_once '../config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check tables
    $tables = ['admin_users', 'events', 'gallery_items', 'management_team', 'announcements'];
    
    echo "<h3>Table Status:</h3>";
    foreach ($tables as $table) {
        try {
            $result = $db->query("SELECT COUNT(*) FROM $table");
            $count = $result->fetchColumn();
            echo "<p style='color: green;'>✓ Table '$table' exists ($count records)</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Table '$table' missing: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test admin user
    echo "<h3>Admin Users:</h3>";
    $stmt = $db->query("SELECT id, username, full_name FROM admin_users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<p style='color: green;'>✓ Admin users found:</p>";
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']}, Username: {$user['username']}, Name: {$user['full_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No admin users found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?>
