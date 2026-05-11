<?php
// Script to insert sample management team data
$host = 'localhost';
$dbname = 'kats_cms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'management_team'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "Table management_team does not exist. Please create it first.";
        exit;
    }
    
    // Clear existing data
    $pdo->exec("DELETE FROM management_team");
    
    // Insert sample data
    $sample_data = [
        ['Director of Study', '0781234567', 'Director of Study', 'active', 1],
        ['Discipline Master', '0788853705', 'Discipline Master', 'active', 2],
        ['Accountant', '07899999999', 'Accountant', 'active', 3],
        ['Head Teacher', '0787654321', 'Head Teacher', 'active', 0],
        ['Academic Secretary', '0785555555', 'Academic Secretary', 'active', 4]
    ];
    
    $sql = "INSERT INTO management_team (name, telephone, post, status, sort_order) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($sample_data as $data) {
        $stmt->execute($data);
    }
    
    echo "Sample management team data inserted successfully!";
    echo "<br>Inserted " . count($sample_data) . " team members.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
