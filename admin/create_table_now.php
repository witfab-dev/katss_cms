<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Create management_team table
    $sql = "CREATE TABLE IF NOT EXISTS management_team (
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
    
    // Insert sample data if table is empty
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM management_team");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $sampleData = [
            ['Director of Study', '0781234567', 'Director of Study', 'active', 1],
            ['Discipline Master', '0788853705', 'Discipline Master', 'active', 2],
            ['Accountant', '07899999999', 'Accountant', 'active', 3]
        ];
        
        $stmt = $db->prepare("INSERT INTO management_team (name, telephone, post, status, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "Table created and sample data inserted successfully!";
    } else {
        echo "Table already exists with $count records.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
