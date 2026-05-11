<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Sample announcement data
    $sampleAnnouncements = [
        [
            'title' => 'Welcome to KATSS Digital Platform',
            'content' => 'We are excited to announce the launch of our new digital platform for better communication with parents and students. This platform will provide real-time updates on school activities, academic progress, and important announcements.',
            'priority' => 'high',
            'status' => 'active'
        ],
        [
            'title' => 'School Calendar Update',
            'content' => 'The academic calendar for the upcoming term has been updated. Please check the admissions page for important dates including registration deadlines, exam schedules, and holiday periods.',
            'priority' => 'medium',
            'status' => 'active'
        ],
        [
            'title' => 'Enrollment Now Open for 2025-2026',
            'content' => 'Applications for the 2025-2026 academic year are now being accepted. Limited spaces available in our technical programs including Software Development, Accounting, Multimedia, and Automotive Technology.',
            'priority' => 'high',
            'status' => 'active'
        ],
        [
            'title' => 'New Technical Equipment Arrived',
            'content' => 'We have recently acquired state-of-the-art technical equipment for our workshops and laboratories. This includes new computers, engineering tools, and multimedia equipment to enhance practical learning.',
            'priority' => 'medium',
            'status' => 'active'
        ],
        [
            'title' => 'Parent-Teacher Meeting Schedule',
            'content' => 'Regular parent-teacher meetings will be held every last Friday of the month. This is an opportunity to discuss your child\'s progress and address any concerns with teachers and administration.',
            'priority' => 'low',
            'status' => 'active'
        ]
    ];
    
    // Insert sample data
    $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    
    $adminId = 1; // Assuming admin ID 1 exists
    
    foreach ($sampleAnnouncements as $announcement) {
        $stmt->execute([
            $announcement['title'],
            $announcement['content'],
            $announcement['priority'],
            $announcement['status'],
            $adminId
        ]);
    }
    
    echo "Sample announcements inserted successfully!";
    echo "<br>";
    echo "<a href='admin/index.php'>Go to Admin Panel</a> | ";
    echo "<a href='public/index.php#news-events'>View Public Website</a>";
    
} catch (Exception $e) {
    echo "Error inserting sample announcements: " . $e->getMessage();
}
?>
