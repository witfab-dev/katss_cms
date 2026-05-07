<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Check which columns exist in gallery_items table
$columns = [];
try {
    $colQuery = $db->query("SHOW COLUMNS FROM gallery_items");
    while ($row = $colQuery->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
} catch (PDOException $e) {
    $columns = ['id', 'title', 'media_type', 'file_path', 'description', 'status', 'created_at'];
}

$hasSortOrder = in_array('sort_order', $columns);
$hasCategory = in_array('category', $columns);
$hasMediaUrl = in_array('media_url', $columns);

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM gallery_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: gallery.php?msg=deleted");
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current = $db->prepare("SELECT status FROM gallery_items WHERE id = ?");
    $current->execute([$id]);
    $newStatus = $current->fetchColumn() == 'active' ? 'inactive' : 'active';
    $stmt = $db->prepare("UPDATE gallery_items SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    header("Location: gallery.php");
    exit();
}

// Handle sort order update (only if column exists)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order']) && $hasSortOrder) {
    $orders = $_POST['order'] ?? [];
    foreach ($orders as $id => $order) {
        $stmt = $db->prepare("UPDATE gallery_items SET sort_order = ? WHERE id = ?");
        $stmt->execute([intval($order), intval($id)]);
    }
    header("Location: gallery.php?msg=updated");
    exit();
}

// Get all gallery items with safe ORDER BY
try {
    if ($hasSortOrder) {
        $gallery = $db->query("SELECT * FROM gallery_items ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $gallery = $db->query("SELECT * FROM gallery_items ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $gallery = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
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
        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h2 {
            color: #003366;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .gallery-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .media-preview {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .no-media {
            width: 100%;
            height: 220px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 48px;
        }
        .video-preview {
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 220px;
            cursor: pointer;
        }
        .video-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-preview .play-icon {
            font-size: 48px;
            color: white;
            position: absolute;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 15px;
            transition: all 0.3s;
        }
        .video-preview:hover .play-icon {
            background: rgba(0,0,0,0.7);
            transform: scale(1.1);
        }
        .card-info {
            padding: 20px;
        }
        .card-info h4 {
            margin-bottom: 8px;
            color: #003366;
            font-size: 18px;
        }
        .card-info p {
            color: #666;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .media-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-image {
            background: #e7f3ff;
            color: #0056b3;
        }
        .badge-video {
            background: #fce4ec;
            color: #c62828;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn-icon {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-icon:hover {
            transform: translateY(-1px);
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-toggle {
            background: #17a2b8;
            color: white;
        }
        .btn-toggle:hover {
            background: #138496;
        }
        .sort-control {
            width: 60px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .sort-form-header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .no-items {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .no-items i {
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
        }
        .no-items h3 {
            margin-bottom: 10px;
            color: #666;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            .gallery-grid {
                grid-template-columns: 1fr;
            }
            .admin-nav a span {
                display: none;
            }
            .admin-nav a i {
                font-size: 20px;
            }
        }
        @media (max-width: 480px) {
            .admin-header {
                padding: 0 15px;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><i class="bi bi-images"></i> KATSS CMS</h1>
        <div class="admin-nav">
            <a href="dashboard.php"><i class="bi bi-house-door"></i><span> Dashboard</span></a>
            <a href="events.php"><i class="bi bi-megaphone"></i><span> Events</span></a>
            <a href="gallery.php" class="active"><i class="bi bi-images"></i><span> Gallery</span></a>
        </div>
        <div class="user-info">
            <span><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php 
                    $msg = $_GET['msg'];
                    switch($msg) {
                        case 'deleted': echo 'Gallery item deleted successfully!'; break;
                        case 'updated': echo 'Sort order updated successfully!'; break;
                        case 'added': echo 'Gallery item added successfully!'; break;
                        default: echo 'Operation completed successfully!';
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class="bi bi-images"></i> Manage Gallery</h2>
            <div style="display: flex; gap: 10px;">
                <?php if ($hasSortOrder): ?>
                <form method="POST" action="" id="sortFormHeader" style="display: inline;">
                    <input type="hidden" name="update_order" value="1">
                    <button type="button" onclick="submitSortForm()" class="btn-secondary">
                        <i class="bi bi-arrow-repeat"></i> Update Sort Order
                    </button>
                </form>
                <?php endif; ?>
                <a href="gallery-form.php" class="btn-primary"><i class="bi bi-plus-circle"></i> Add Gallery Item</a>
            </div>
        </div>
        
        <?php if (empty($gallery)): ?>
            <div class="no-items">
                <i class="bi bi-images"></i>
                <h3>No Gallery Items Yet</h3>
                <p>Start by adding your first image or video to the gallery.</p>
                <a href="gallery-form.php" class="btn-primary" style="margin-top: 20px;">
                    <i class="bi bi-plus-circle"></i> Add First Gallery Item
                </a>
            </div>
        <?php else: ?>
        
        <!-- Hidden form for sort order submission -->
        <form method="POST" id="sortForm" style="display: none;">
            <input type="hidden" name="update_order" value="1">
        </form>
        
        <div class="gallery-grid">
            <?php foreach ($gallery as $item): ?>
            <div class="gallery-card">
                <?php if (($item['media_type'] ?? 'image') == 'video'): ?>
                    <div class="video-preview">
                        <?php 
                        $mediaUrl = '';
                        if ($hasMediaUrl && !empty($item['media_url'])) {
                            $mediaUrl = $item['media_url'];
                        } elseif (isset($item['file_path']) && !empty($item['file_path'])) {
                            $mediaUrl = $item['file_path'];
                        }
                        ?>
                        <?php if ($mediaUrl): ?>
                            <video muted preload="metadata">
                                <source src="<?php echo htmlspecialchars($mediaUrl); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                        <div class="play-icon"><i class="bi bi-play-circle-fill"></i></div>
                    </div>
                <?php else: ?>
                    <?php 
                    $imageUrl = '';
                    if ($hasMediaUrl && !empty($item['media_url'])) {
                        $imageUrl = $item['media_url'];
                    } elseif (isset($item['file_path']) && !empty($item['file_path'])) {
                        $imageUrl = $item['file_path'];
                    } elseif (isset($item['thumbnail_path']) && !empty($item['thumbnail_path'])) {
                        $imageUrl = $item['thumbnail_path'];
                    }
                    ?>
                    <?php if ($imageUrl): ?>
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="media-preview" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             onerror="this.onerror=null; this.parentElement.querySelector('.no-media').style.display='flex'; this.style.display='none';">
                        <div class="no-media" style="display:none;"><i class="bi bi-image"></i></div>
                    <?php else: ?>
                        <div class="no-media"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="card-info">
                    <div class="media-badges">
                        <span class="badge badge-<?php echo ($item['media_type'] ?? 'image') == 'video' ? 'video' : 'image'; ?>">
                            <i class="bi bi-<?php echo ($item['media_type'] ?? 'image') == 'video' ? 'film' : 'image'; ?>"></i>
                            <?php echo ucfirst($item['media_type'] ?? 'Image'); ?>
                        </span>
                        <span class="badge badge-<?php echo ($item['status'] ?? 'inactive'); ?>">
                            <i class="bi bi-<?php echo ($item['status'] ?? 'inactive') == 'active' ? 'check-circle' : 'x-circle'; ?>"></i>
                            <?php echo ucfirst($item['status'] ?? 'Inactive'); ?>
                        </span>
                    </div>
                    
                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    
                    <?php if (!empty($item['description'])): ?>
                        <p><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></p>
                    <?php endif; ?>
                    
                    <?php if ($hasCategory && !empty($item['category'])): ?>
                        <small style="color:#999;"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($item['category']); ?></small>
                    <?php endif; ?>
                    
                    <div class="card-actions">
                        <a href="gallery-form.php?id=<?php echo $item['id']; ?>" class="btn-icon btn-edit">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="?toggle_status=<?php echo $item['id']; ?>" class="btn-icon btn-toggle" 
                           onclick="return confirm('Toggle status of this item?')">
                            <i class="bi bi-<?php echo ($item['status'] ?? 'inactive') == 'active' ? 'eye-slash' : 'eye'; ?>"></i>
                            <?php echo ($item['status'] ?? 'inactive') == 'active' ? 'Hide' : 'Show'; ?>
                        </a>
                        <a href="?delete=<?php echo $item['id']; ?>" class="btn-icon btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this item permanently?\nThis action cannot be undone.')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                    
                    <?php if ($hasSortOrder): ?>
                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #eee;">
                        <label style="font-size: 12px; color: #666;">Sort Order:</label>
                        <input type="number" name="order[<?php echo $item['id']; ?>]" 
                               value="<?php echo $item['sort_order'] ?? 0; ?>" 
                               class="sort-control" form="sortForm">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function submitSortForm() {
            const form = document.getElementById('sortForm');
            const inputs = form.querySelectorAll('input[name^="order"]');
            
            if (inputs.length === 0) {
                alert('No sort order fields found.');
                return;
            }
            
            if (confirm('Update sort order for all items?')) {
                form.submit();
            }
        }
        
        // Auto-dismiss alerts
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>