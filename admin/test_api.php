<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Test if management_team table exists
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'management_team'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode(['error' => 'management_team table does not exist']);
        exit();
    }
    
    // Test if we can fetch data
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM management_team");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'record_count' => $count,
        'test_query' => "SELECT * FROM management_team WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
