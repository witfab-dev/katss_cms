<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get statistics
$eventCount = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$publishedEvents = $db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
$galleryCount = $db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$activeGallery = $db->query("SELECT COUNT(*) FROM gallery_items WHERE status = 'active'")->fetchColumn();
$featuredEvents = $db->query("SELECT COUNT(*) FROM events WHERE is_featured = 1 AND status = 'published'")->fetchColumn();

// Get recent events
$recentEvents = $db->query("SELECT id, title, event_date, status, views FROM events ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get recent gallery items
$recentGallery = $db->query("SELECT id, title, media_type, status FROM gallery_items ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fb;
        }
        .admin-header {
            background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: white;
            padding: 0 30px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .admin-header h1 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .admin-header h1 i {
            font-size: 28px;
        }
        .admin-nav {
            display: flex;
            gap: 5px;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logout-btn {
            background: rgba(220, 53, 69, 0.9);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #dc3545;
            transform: translateY(-2px);
        }
        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .welcome-card h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #003366;
        }
        .stat-icon {
            font-size: 45px;
            color: #003366;
            margin-bottom: 15px;
        }
        .stat-card h3 {
            font-size: 36px;
            color: #003366;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        .stat-small {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .recent-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .recent-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .recent-card h3 {
            margin-bottom: 20px;
            color: #003366;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .recent-table {
            width: 100%;
        }
        .recent-table td {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .recent-table tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-published, .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-draft, .status-inactive {
            background: #fff3cd;
            color: #856404;
        }
        .media-badge {
            background: #e7f3ff;
            color: #003366;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
        }
        @media (max-width: 768px) {
            .admin-nav a span {
                display: none;
            }
            .admin-nav a i {
                font-size: 20px;
            }
            .recent-section {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><i class="bi bi-speedometer2"></i> KATSS CMS</h1>
        <div class="admin-nav">
            <a href="dashboard.php" class="active"><i class="bi bi-house-door"></i><span> Dashboard</span></a>
            <a href="events.php"><i class="bi bi-megaphone"></i><span> Events</span></a>
            <a href="gallery.php"><i class="bi bi-images"></i><span> Gallery</span></a>
            <a href="management-team.php"><i class="bi bi-person-badge"></i><span> Management Team</span></a>
        </div>
        <div class="user-info">
            <span><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin'); ?>! 👋</h2>
            <p>Here's what's happening with your website today.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-megaphone-fill"></i></div>
                <h3><?php echo $eventCount; ?></h3>
                <p>Total Events/News</p>
                <div class="stat-small"><?php echo $publishedEvents; ?> published</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                <h3><?php echo $featuredEvents; ?></h3>
                <p>Featured Events</p>
                <div class="stat-small">Displayed on homepage</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-images"></i></div>
                <h3><?php echo $galleryCount; ?></h3>
                <p>Gallery Items</p>
                <div class="stat-small"><?php echo $activeGallery; ?> active</div>
            </div>
        </div>
        
        <div class="recent-section">
            <div class="recent-card">
                <h3><i class="bi bi-clock-history"></i> Recent Events</h3>
                <table class="recent-table">
                    <?php foreach ($recentEvents as $event): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(substr($event['title'], 0, 40)); ?></strong><br>
                            <small><?php echo $event['event_date']; ?></small>
                        </td>
                        <td style="text-align: right;">
                            <span class="status-badge status-<?php echo $event['status']; ?>"><?php echo $event['status']; ?></span>
                            <br>
                            <small><i class="bi bi-eye"></i> <?php echo $event['views']; ?> views</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="recent-card">
                <h3><i class="bi bi-images"></i> Recent Gallery Items</h3>
                <table class="recent-table">
                    <?php foreach ($recentGallery as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars(substr($item['title'], 0, 40)); ?></strong>
                            <br>
                            <span class="media-badge"><i class="bi bi-<?php echo $item['media_type'] == 'image' ? 'image' : 'film'; ?>"></i> <?php echo ucfirst($item['media_type']); ?></span>
                        </td>
                        <td style="text-align: right;">
                            <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo $item['status']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>