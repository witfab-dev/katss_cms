<?php
/**
 * Simple API endpoint for KATSS CMS
 * Handles all CRUD operations with simplified parameter handling
 */

// ==================== ERROR HANDLING ====================
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handler
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    handleError('Unauthorized', 401);
}

// Load database
try {
    require_once '../config/database.php';
} catch (Exception $e) {
    handleError('Database connection failed');
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    handleError('Database error');
}

// ==================== GET PARAMETERS ====================
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$type = $_GET['type'] ?? $_POST['type'] ?? 'events';

// Map types
if ($type === 'gallery_items') $type = 'gallery';
if (!in_array($type, ['events', 'gallery', 'management_team', 'announcements'])) {
    $type = 'events';
}

// ==================== ROUTE ACTIONS ====================
try {
    switch ($action) {
        case 'list':
            handleList($db, $type);
            break;
        case 'create':
            handleCreate($db, $type);
            break;
        case 'update':
            handleUpdate($db, $type);
            break;
        case 'delete':
            handleDelete($db, $type);
            break;
        case 'toggle_status':
            handleToggleStatus($db, $type);
            break;
        case 'toggle_featured':
            handleToggleFeatured($db);
            break;
        case 'bulk_action':
            handleBulkAction($db, $type);
            break;
        case 'upload_image':
            handleImageUpload();
            break;
        case 'stats':
            handleStats($db);
            break;
        default:
            handleError('Invalid action: ' . $action, 400);
    }
} catch (Exception $e) {
    handleError('Server error: ' . $e->getMessage());
}

// ==================== HELPER FUNCTIONS ====================

function getInput($key, $default = '') {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function formatUrl($url) {
    if (empty($url)) return '';
    if (preg_match('#^https?://#i', $url)) return $url;
    return ltrim($url, '/');
}

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// ==================== LIST ====================
function handleList($db, $type) {
    $search = getInput('search', '');
    $status = getInput('status', '');
    $category = getInput('category', '');
    $page = max(1, (int)getInput('page', 1));
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
    } elseif ($type === 'management_team') {
        $sql = "SELECT * FROM management_team WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM management_team WHERE 1=1";
        
        if ($search) {
            $sql .= " AND (name LIKE :search OR post LIKE :search2)";
            $countSql .= " AND (name LIKE :search OR post LIKE :search2)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
        }
        if ($status) {
            $sql .= " AND status = :status";
            $countSql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY sort_order ASC, created_at DESC LIMIT $limit OFFSET $offset";
    } elseif ($type === 'announcements') {
        $sql = "SELECT * FROM announcements WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM announcements WHERE 1=1";
        
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
        
        $priority = getInput('priority', '');
        if ($priority) {
            $sql .= " AND priority = :priority";
            $countSql .= " AND priority = :priority";
            $params[':priority'] = $priority;
        }
        
        $sql .= " ORDER BY FIELD(priority, 'high', 'medium', 'low'), created_at DESC LIMIT $limit OFFSET $offset";
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
    
    // Get total count
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Get items
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format image URLs
    foreach ($items as &$item) {
        if ($type === 'events' && !empty($item['image_url'])) {
            $item['image_url'] = formatUrl($item['image_url']);
        } elseif ($type === 'gallery') {
            if (!empty($item['media_url'])) $item['media_url'] = formatUrl($item['media_url']);
            if (!empty($item['file_path'])) $item['file_path'] = formatUrl($item['file_path']);
            if (!empty($item['thumbnail_path'])) $item['thumbnail_path'] = formatUrl($item['thumbnail_path']);
        }
    }
    
    // Get categories
    $categories = [];
    if ($type === 'events') {
        try {
            $categories = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {}
    }
    
    echo json_encode([
        'success' => true,
        'data' => $items,
        'total' => (int)$total,
        'pages' => ceil($total / $limit),
        'current_page' => $page,
        'categories' => $categories
    ]);
}

// ==================== CREATE ====================
function handleCreate($db, $type) {
    if ($type === 'events') {
        $title = trim(getInput('title', ''));
        $content = trim(getInput('content', ''));
        $excerpt = trim(getInput('excerpt', ''));
        $category = getInput('category', 'General');
        $event_date = getInput('event_date', date('Y-m-d'));
        $status = getInput('status', 'published');
        $is_featured = (int)getInput('is_featured', 0);
        $image_url = getInput('image_url', '');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $slug = createSlug($title);
        $check = $db->prepare("SELECT id FROM events WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->rowCount() > 0) $slug .= '-' . time();
        
        $image_url = formatUrl($image_url);
        
        $stmt = $db->prepare("INSERT INTO events (title, slug, content, excerpt, image_url, category, event_date, is_featured, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $slug, $content, $excerpt, $image_url, $category, $event_date, $is_featured, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Event created', 'id' => $db->lastInsertId()]);
    } elseif ($type === 'management_team') {
        $name = trim(getInput('name', ''));
        $telephone = trim(getInput('telephone', ''));
        $post = trim(getInput('post', ''));
        $status = getInput('status', 'active');
        $sort_order = (int)getInput('sort_order', 0);
        
        if (empty($name) || empty($telephone) || empty($post)) {
            echo json_encode(['error' => 'Name, telephone, and post are required']);
            return;
        }
        
        $stmt = $db->prepare("INSERT INTO management_team (name, telephone, post, status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $telephone, $post, $status, $sort_order]);
        
        echo json_encode(['success' => true, 'message' => 'Team member created', 'id' => $db->lastInsertId()]);
    } elseif ($type === 'announcements') {
        $title = trim(getInput('title', ''));
        $content = trim(getInput('content', ''));
        $priority = getInput('priority', 'medium');
        $status = getInput('status', 'active');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $content, $priority, $status, $_SESSION['admin_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Announcement created', 'id' => $db->lastInsertId()]);
    } else {
        $title = trim(getInput('title', ''));
        $description = trim(getInput('description', ''));
        $media_type = getInput('media_type', 'image');
        $file_path = getInput('file_path', '');
        $category = trim(getInput('category', ''));
        $status = getInput('status', 'active');
        
        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        $media_url = formatUrl($file_path);
        $file_path = formatUrl($file_path);
        
        $stmt = $db->prepare("INSERT INTO gallery_items (title, description, media_type, media_url, file_path, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $media_type, $media_url, $file_path, $category, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item created', 'id' => $db->lastInsertId()]);
    }
}

// ==================== UPDATE ====================
function handleUpdate($db, $type) {
    $id = (int)getInput('id', 0);
    
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID']);
        return;
    }
    
    if ($type === 'events') {
        $title = trim(getInput('title', ''));
        $content = trim(getInput('content', ''));
        $excerpt = trim(getInput('excerpt', ''));
        $category = getInput('category', 'General');
        $event_date = getInput('event_date', date('Y-m-d'));
        $status = getInput('status', 'published');
        $is_featured = (int)getInput('is_featured', 0);
        $image_url = getInput('image_url', '');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $image_url = formatUrl($image_url);
        
        $stmt = $db->prepare("UPDATE events SET title=?, content=?, excerpt=?, category=?, event_date=?, status=?, is_featured=?, image_url=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $content, $excerpt, $category, $event_date, $status, $is_featured, $image_url, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Event updated']);
    } elseif ($type === 'management_team') {
        $name = trim(getInput('name', ''));
        $telephone = trim(getInput('telephone', ''));
        $post = trim(getInput('post', ''));
        $status = getInput('status', 'active');
        $sort_order = (int)getInput('sort_order', 0);
        
        if (empty($name) || empty($telephone) || empty($post)) {
            echo json_encode(['error' => 'Name, telephone, and post are required']);
            return;
        }
        
        $stmt = $db->prepare("UPDATE management_team SET name=?, telephone=?, post=?, status=?, sort_order=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$name, $telephone, $post, $status, $sort_order, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Team member updated']);
    } elseif ($type === 'announcements') {
        $title = trim(getInput('title', ''));
        $content = trim(getInput('content', ''));
        $priority = getInput('priority', 'medium');
        $status = getInput('status', 'active');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $stmt = $db->prepare("UPDATE announcements SET title=?, content=?, priority=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $content, $priority, $status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Announcement updated']);
    } else {
        $title = trim(getInput('title', ''));
        $description = trim(getInput('description', ''));
        $media_type = getInput('media_type', 'image');
        $file_path = getInput('file_path', '');
        $category = trim(getInput('category', ''));
        $status = getInput('status', 'active');
        
        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        $media_url = formatUrl($file_path);
        $file_path = formatUrl($file_path);
        
        $stmt = $db->prepare("UPDATE gallery_items SET title=?, description=?, media_type=?, media_url=?, file_path=?, category=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $description, $media_type, $media_url, $file_path, $category, $status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item updated']);
    }
}

// ==================== DELETE ====================
function handleDelete($db, $type) {
    $ids = [];
    
    if (isset($_POST['ids'])) {
        $ids = is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']];
    } elseif ($id = getInput('id', 0)) {
        $ids = [(int)$id];
    }
    
    if (empty($ids)) {
        echo json_encode(['error' => 'No items selected']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $tables = [
        'events' => 'events',
        'management_team' => 'management_team',
        'announcements' => 'announcements',
        'gallery' => 'gallery_items'
    ];
    
    $table = $tables[$type] ?? 'events';
    
    $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    echo json_encode(['success' => true, 'message' => $stmt->rowCount() . ' item(s) deleted']);
}

// ==================== TOGGLE STATUS ====================
function handleToggleStatus($db, $type) {
    $id = (int)getInput('id', 0);
    
    $tables = [
        'events' => 'events',
        'management_team' => 'management_team',
        'announcements' => 'announcements',
        'gallery' => 'gallery_items'
    ];
    
    $table = $tables[$type] ?? 'events';
    
    $stmt = $db->prepare("SELECT status FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    
    if ($current === false) {
        echo json_encode(['error' => 'Item not found']);
        return;
    }
    
    $statuses = [
        'published' => 'draft',
        'draft' => 'published',
        'active' => 'inactive',
        'inactive' => 'active'
    ];
    
    $new = $statuses[$current] ?? 'active';
    
    $stmt = $db->prepare("UPDATE $table SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new, $id]);
    
    echo json_encode(['success' => true, 'new_status' => $new]);
}

// ==================== TOGGLE FEATURED ====================
function handleToggleFeatured($db) {
    $id = (int)getInput('id', 0);
    
    $stmt = $db->prepare("SELECT is_featured FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $is = $stmt->fetchColumn();
    
    if ($is === false) {
        echo json_encode(['error' => 'Event not found']);
        return;
    }
    
    $new = $is ? 0 : 1;
    $stmt = $db->prepare("UPDATE events SET is_featured = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new, $id]);
    
    echo json_encode(['success' => true, 'is_featured' => $new]);
}

// ==================== BULK ACTION ====================
function handleBulkAction($db, $type) {
    $ids = [];
    $action = getInput('bulk_action', '');
    
    if (isset($_POST['ids'])) {
        $ids = is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']];
    }
    
    if (empty($ids) || empty($action)) {
        echo json_encode(['error' => 'No items or action specified']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $tables = [
        'events' => 'events',
        'management_team' => 'management_team',
        'announcements' => 'announcements',
        'gallery' => 'gallery_items'
    ];
    
    $table = $tables[$type] ?? 'events';
    
    switch ($action) {
        case 'delete':
            $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'deleted';
            break;
        case 'publish':
            $stmt = $db->prepare("UPDATE $table SET status = 'published', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'published';
            break;
        case 'draft':
            $stmt = $db->prepare("UPDATE $table SET status = 'draft', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'moved to draft';
            break;
        case 'activate':
            $stmt = $db->prepare("UPDATE $table SET status = 'active', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'activated';
            break;
        case 'deactivate':
            $stmt = $db->prepare("UPDATE $table SET status = 'inactive', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'deactivated';
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            return;
    }
    
    echo json_encode(['success' => true, 'message' => count($ids) . ' items ' . $msg]);
}

// ==================== IMAGE UPLOAD ====================
function handleImageUpload() {
    $type = $_POST['type'] ?? 'events';
    
    if (!in_array($type, ['events', 'gallery'])) {
        echo json_encode(['error' => 'Invalid upload type']);
        return;
    }
    
    $uploadDir = __DIR__ . '/uploads/' . $type . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Upload failed']);
        return;
    }
    
    $file = $_FILES['image'];
    
    // Allow both images and videos
    $allowedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedVideos = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];
    $allowed = array_merge($allowedImages, $allowedVideos);
    
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowed)) {
        echo json_encode(['error' => 'Invalid file type. Allowed: Images (JPG, PNG, GIF, WebP) and Videos (MP4, WebM, OGG, MOV, AVI)']);
        return;
    }
    
    // Different size limits for images and videos
    $isVideo = in_array($fileType, $allowedVideos);
    $maxSize = $isVideo ? 50 * 1024 * 1024 : 5 * 1024 * 1024; // 50MB for videos, 5MB for images
    
    if ($file['size'] > $maxSize) {
        $maxSizeText = $isVideo ? '50MB' : '5MB';
        $fileTypeText = $isVideo ? 'video' : 'image';
        echo json_encode(['error' => "File too large (max {$maxSizeText} for {$fileTypeText}s)"]);
        return;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
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

// ==================== STATS ====================
function handleStats($db) {
    try {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_events' => (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn(),
                'published_events' => (int)$db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn(),
                'featured_events' => (int)$db->query("SELECT COUNT(*) FROM events WHERE is_featured = 1")->fetchColumn(),
                'total_gallery' => (int)$db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn(),
                'active_gallery' => (int)$db->query("SELECT COUNT(*) FROM gallery_items WHERE status = 'active'")->fetchColumn()
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_events' => 0,
                'published_events' => 0,
                'featured_events' => 0,
                'total_gallery' => 0,
                'active_gallery' => 0
            ]
        ]);
    }
}