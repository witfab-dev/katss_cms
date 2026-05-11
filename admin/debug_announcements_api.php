<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Test database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Test if announcements table exists
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'announcements'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode(['error' => 'announcements table does not exist']);
        exit();
    }
    
    // Test if we can fetch data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM announcements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'record_count' => $count,
        'test_query' => "SELECT * FROM announcements WHERE status = 'active' ORDER BY priority DESC, created_at DESC LIMIT 10"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
