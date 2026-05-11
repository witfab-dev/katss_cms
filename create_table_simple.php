<?php
// Simple script to create management_team table
$host = 'localhost';
$dbname = 'kats_cms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    
    $pdo->exec($sql);
    echo "Management team table created successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
