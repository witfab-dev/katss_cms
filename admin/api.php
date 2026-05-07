<?php
/**
 * KATSS CMS - API Endpoint
 * Handles all CRUD operations for events and gallery
 */
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? ($input['action'] ?? '');
$type = $_GET['type'] ?? ($input['type'] ?? 'events'); // 'events' or 'gallery'

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'list':
            handleList($db, $type);
            break;
        case 'create':
            handleCreate($db, $type, $input);
            break;
        case 'update':
            handleUpdate($db, $type, $input);
            break;
        case 'delete':
            handleDelete($db, $type, $input);
            break;
        case 'toggle_status':
            handleToggleStatus($db, $type, $input);
            break;
        case 'toggle_featured':
            handleToggleFeatured($db, $input);
            break;
        case 'bulk_action':
            handleBulkAction($db, $type, $input);
            break;
        case 'upload_image':
            handleImageUpload();
            break;
        case 'stats':
            handleStats($db);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleList($db, $type) {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $params = [];
    
    if ($type === 'events') {
        $sql = "SELECT * FROM events WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM events WHERE 1=1";
        
        if ($search) {
            $sql .= " AND (title LIKE :search OR content LIKE :search2)";
            $countSql .= " AND (title LIKE :search OR content LIKE :search2)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
        }
        if ($status) {
            $sql .= " AND status = :status";
            $countSql .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($category) {
            $sql .= " AND category = :category";
            $countSql .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    } else {
        $sql = "SELECT * FROM gallery_items WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM gallery_items WHERE 1=1";
        
        if ($search) {
            $sql .= " AND (title LIKE :search OR description LIKE :search2)";
            $countSql .= " AND (title LIKE :search OR description LIKE :search2)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
        }
        if ($status) {
            $sql .= " AND status = :status";
            $countSql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for events
    $categories = [];
    if ($type === 'events') {
        $categories = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'total' => (int)$total,
        'pages' => ceil($total / $limit),
        'categories' => $categories
    ]);
}

function handleCreate($db, $type, $input) {
    if ($type === 'events') {
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $excerpt = trim($input['excerpt'] ?? '');
        $category = trim($input['category'] ?? 'General');
        $event_date = $input['event_date'] ?? date('Y-m-d');
        $status = $input['status'] ?? 'published';
        $is_featured = $input['is_featured'] ?? 0;
        $image_url = $input['image_url'] ?? '';
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $slug = createSlug($title);
        
        // Check unique slug
        $check = $db->prepare("SELECT id FROM events WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->rowCount() > 0) {
            $slug .= '-' . time();
        }
        
        $stmt = $db->prepare("INSERT INTO events (title, slug, content, excerpt, image_url, category, event_date, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content, $excerpt, $image_url, $category, $event_date, $is_featured, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Event created', 'id' => $db->lastInsertId()]);
    } else {
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $media_type = $input['media_type'] ?? 'image';
        $file_path = $input['file_path'] ?? '';
        $category = $input['category'] ?? '';
        $status = $input['status'] ?? 'active';
        
        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        $stmt = $db->prepare("INSERT INTO gallery_items (title, description, media_type, file_path, category, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $media_type, $file_path, $category, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item created', 'id' => $db->lastInsertId()]);
    }
}

function handleUpdate($db, $type, $input) {
    $id = $input['id'] ?? 0;
    
    if ($type === 'events') {
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $excerpt = trim($input['excerpt'] ?? '');
        $category = trim($input['category'] ?? 'General');
        $event_date = $input['event_date'] ?? date('Y-m-d');
        $status = $input['status'] ?? 'published';
        $is_featured = $input['is_featured'] ?? 0;
        $image_url = $input['image_url'] ?? '';
        
        $stmt = $db->prepare("UPDATE events SET title=?, content=?, excerpt=?, category=?, event_date=?, status=?, is_featured=?, image_url=? WHERE id=?");
        $stmt->execute([$title, $content, $excerpt, $category, $event_date, $status, $is_featured, $image_url, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Event updated']);
    } else {
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $media_type = $input['media_type'] ?? 'image';
        $file_path = $input['file_path'] ?? '';
        $category = $input['category'] ?? '';
        $status = $input['status'] ?? 'active';
        
        $stmt = $db->prepare("UPDATE gallery_items SET title=?, description=?, media_type=?, file_path=?, category=?, status=? WHERE id=?");
        $stmt->execute([$title, $description, $media_type, $file_path, $category, $status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item updated']);
    }
}

function handleDelete($db, $type, $input) {
    $ids = $input['ids'] ?? [$input['id'] ?? 0];
    
    if (empty($ids)) {
        echo json_encode(['error' => 'No items selected']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $table = $type === 'events' ? 'events' : 'gallery_items';
    
    $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    echo json_encode(['success' => true, 'message' => count($ids) . ' item(s) deleted']);
}

function handleToggleStatus($db, $type, $input) {
    $id = $input['id'] ?? 0;
    $table = $type === 'events' ? 'events' : 'gallery_items';
    
    $current = $db->prepare("SELECT status FROM $table WHERE id = ?");
    $current->execute([$id]);
    $status = $current->fetchColumn();
    
    if ($type === 'events') {
        $newStatus = ($status === 'published') ? 'draft' : 'published';
    } else {
        $newStatus = ($status === 'active') ? 'inactive' : 'active';
    }
    
    $stmt = $db->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
}

function handleToggleFeatured($db, $input) {
    $id = $input['id'] ?? 0;
    
    $current = $db->prepare("SELECT is_featured FROM events WHERE id = ?");
    $current->execute([$id]);
    $isFeatured = $current->fetchColumn();
    
    $newFeatured = $isFeatured ? 0 : 1;
    
    $stmt = $db->prepare("UPDATE events SET is_featured = ? WHERE id = ?");
    $stmt->execute([$newFeatured, $id]);
    
    echo json_encode(['success' => true, 'is_featured' => $newFeatured]);
}

function handleBulkAction($db, $type, $input) {
    $ids = $input['ids'] ?? [];
    $action = $input['bulk_action'] ?? '';
    $table = $type === 'events' ? 'events' : 'gallery_items';
    
    if (empty($ids) || empty($action)) {
        echo json_encode(['error' => 'No items or action specified']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    switch ($action) {
        case 'delete':
            $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'deleted';
            break;
        case 'publish':
        case 'activate':
            $newStatus = ($type === 'events') ? 'published' : 'active';
            $stmt = $db->prepare("UPDATE $table SET status = '$newStatus' WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'updated';
            break;
        case 'draft':
        case 'deactivate':
            $newStatus = ($type === 'events') ? 'draft' : 'inactive';
            $stmt = $db->prepare("UPDATE $table SET status = '$newStatus' WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'updated';
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            return;
    }
    
    echo json_encode(['success' => true, 'message' => count($ids) . ' items ' . $msg]);
}

function handleImageUpload() {
    $type = $_POST['type'] ?? 'events';
    $uploadDir = __DIR__ . '/uploads/' . $type . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'No file uploaded or upload error']);
        return;
    }
    
    $file = $_FILES['image'];
    
    // Validate
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($fileType, $allowed)) {
        echo json_encode(['error' => 'Invalid file type']);
        return;
    }
    
    if ($file['size'] > $maxSize) {
        echo json_encode(['error' => 'File too large (max 5MB)']);
        return;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = 'uploads/' . $type . '/' . $fileName;
        echo json_encode([
            'success' => true,
            'file_path' => $relativePath,
            'url' => $relativePath
        ]);
    } else {
        echo json_encode(['error' => 'Failed to save file']);
    }
}

function handleStats($db) {
    $eventCount = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $publishedEvents = $db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
    $featuredEvents = $db->query("SELECT COUNT(*) FROM events WHERE is_featured = 1 AND status = 'published'")->fetchColumn();
    $galleryCount = $db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
    $activeGallery = $db->query("SELECT COUNT(*) FROM gallery_items WHERE status = 'active'")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_events' => (int)$eventCount,
            'published_events' => (int)$publishedEvents,
            'featured_events' => (int)$featuredEvents,
            'total_gallery' => (int)$galleryCount,
            'active_gallery' => (int)$activeGallery
        ]
    ]);
}

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}
?>