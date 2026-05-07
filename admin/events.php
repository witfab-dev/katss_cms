<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM events WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = "deleted";
        } elseif ($action === 'publish') {
            $stmt = $db->prepare("UPDATE events SET status = 'published' WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = "published";
        } elseif ($action === 'draft') {
            $stmt = $db->prepare("UPDATE events SET status = 'draft' WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = "drafted";
        }
        header("Location: events.php?msg=$msg");
        exit();
    }
}

// Handle individual delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: events.php?msg=deleted");
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current = $db->prepare("SELECT status FROM events WHERE id = ?");
    $current->execute([$id]);
    $newStatus = $current->fetchColumn() == 'published' ? 'draft' : 'published';
    $stmt = $db->prepare("UPDATE events SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    header("Location: events.php");
    exit();
}

// Handle featured toggle
if (isset($_GET['toggle_featured'])) {
    $id = $_GET['toggle_featured'];
    $current = $db->prepare("SELECT is_featured FROM events WHERE id = ?");
    $current->execute([$id]);
    $newFeatured = $current->fetchColumn() == 1 ? 0 : 1;
    $stmt = $db->prepare("UPDATE events SET is_featured = ? WHERE id = ?");
    $stmt->execute([$newFeatured, $id]);
    header("Location: events.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT * FROM events WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM events WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (title LIKE ? OR content LIKE ?)";
    $countSql .= " AND (title LIKE ? OR content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}
if ($statusFilter) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories for filter
$categories = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - KATSS CMS</title>
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
        .logout-btn {
            background: rgba(220, 53, 69, 0.9);
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
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
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters-bar input, .filters-bar select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .filters-bar button {
            background: #003366;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .event-image {
            width: 60px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        .featured-badge {
            background: #ffc107;
            color: #333;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-icon {
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-toggle {
            background: #17a2b8;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-feature {
            background: #6c757d;
            color: white;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 15px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #003366;
        }
        .pagination .active {
            background: #003366;
            color: white;
        }
        .checkbox-col {
            width: 30px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .admin-nav a span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1><i class="bi bi-megaphone-fill"></i> KATSS CMS</h1>
        <div class="admin-nav">
            <a href="dashboard.php"><i class="bi bi-house-door"></i><span> Dashboard</span></a>
            <a href="events.php" class="active"><i class="bi bi-megaphone"></i><span> Events</span></a>
            <a href="gallery.php"><i class="bi bi-images"></i><span> Gallery</span></a>
        </div>
        <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php 
                    $msg = $_GET['msg'];
                    if ($msg == 'deleted') echo 'Event deleted successfully!';
                    elseif ($msg == 'published') echo 'Events published successfully!';
                    elseif ($msg == 'drafted') echo 'Events moved to draft!';
                    else echo 'Operation completed successfully!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class="bi bi-megaphone-fill"></i> Manage Events & News</h2>
            <a href="event-form.php" class="btn-primary"><i class="bi bi-plus-circle"></i> Add New Event</a>
        </div>
        
        <div class="filters-bar">
            <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $statusFilter == 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $statusFilter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter == $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><i class="bi bi-search"></i> Filter</button>
                <a href="events.php" style="padding: 8px 15px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
        </div>
        
        <form method="POST" action="" id="bulkForm">
            <div style="margin-bottom: 15px;">
                <select name="bulk_action" id="bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Move to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="button" onclick="executeBulkAction()" style="padding: 5px 15px; background: #003366; color: white; border: none; border-radius: 5px;">Apply</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $event['id']; ?>" class="item-checkbox"></td>
                        <td><?php echo $event['id']; ?></td>
                        <td>
                            <?php if ($event['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($event['image_url']); ?>" class="event-image" alt="">
                            <?php else: ?>
                                <span style="color:#999;">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars(substr($event['title'], 0, 50)); ?></strong><br><small><?php echo htmlspecialchars(substr($event['excerpt'] ?: $event['content'], 0, 60)); ?>...</small></td>
                        <td><?php echo htmlspecialchars($event['category'] ?: 'General'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $event['status']; ?>">
                                <?php echo $event['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($event['is_featured']): ?>
                                <span class="featured-badge"><i class="bi bi-star-fill"></i> Featured</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <a href="event-form.php?id=<?php echo $event['id']; ?>" class="btn-icon btn-edit"><i class="bi bi-pencil"></i> Edit</a>
                            <a href="?toggle_status=<?php echo $event['id']; ?>" class="btn-icon btn-toggle" onclick="return confirm('Toggle status?')">
                                <i class="bi bi-eye<?php echo $event['status'] == 'published' ? '-slash' : ''; ?>"></i>
                            </a>
                            <a href="?toggle_featured=<?php echo $event['id']; ?>" class="btn-icon btn-feature" onclick="return confirm('Toggle featured status?')">
                                <i class="bi bi-star<?php echo $event['is_featured'] ? '-fill' : ''; ?>"></i>
                            </a>
                            <a href="?delete=<?php echo $event['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>&category=<?php echo urlencode($categoryFilter); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        function executeBulkAction() {
            const action = document.getElementById('bulk_action').value;
            if (!action) {
                alert('Please select an action');
                return;
            }
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one item');
                return;
            }
            if (confirm(`Are you sure you want to ${action} selected items?`)) {
                document.getElementById('bulkForm').submit();
            }
        }
    </script>
</body>
</html>