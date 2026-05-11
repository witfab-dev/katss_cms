<?php
/**
 * KATSS CMS - API Endpoint
 * Handles all CRUD operations for events and gallery
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

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['error' => 'Fatal error occurred', 'message' => $error['message']]);
    }
});

// Custom error handler for JSON responses
function json_error($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please login first']);
    exit();
}

// Load database
try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ==================== DETECT ACTION ====================
$action = '';
$type = 'events';

if (isset($_GET['action']) && !empty($_GET['action'])) {
    $action = $_GET['action'];
}
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $type = $_GET['type'];
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
}
if (isset($_POST['type']) && !empty($_POST['type'])) {
    $type = $_POST['type'];
}

$jsonInput = json_decode(file_get_contents('php://input'), true);
if ($jsonInput) {
    if (isset($jsonInput['action']) && !empty($jsonInput['action'])) {
        $action = $jsonInput['action'];
    }
    if (isset($jsonInput['type']) && !empty($jsonInput['type'])) {
        $type = $jsonInput['type'];
    }
}

if (empty($action) && isset($_FILES['image'])) {
    $action = 'upload_image';
    $type = $_POST['type'] ?? 'events';
}

if (!in_array($type, ['events', 'gallery', 'management_team', 'announcements'])) {
    $type = 'events';
}

try {
    switch ($action) {
        case 'list':
        case 'get':
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
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid action: "' . $action . '"',
                'available' => ['list','create','update','delete','toggle_status','toggle_featured','bulk_action','upload_image','stats']
            ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// ==================== HELPER FUNCTIONS ====================

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function formatImageUrl($url) {
    if (empty($url)) return '';
    if (preg_match('#^https?://#i', $url)) return $url;
    $url = ltrim($url, '/');
    $url = preg_replace('#^(admin/)+#', '', $url);
    $url = preg_replace('#^(uploads/)+#', 'uploads/', $url);
    return $url;
}

function getInput($key, $default = '') {
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    $json = json_decode(file_get_contents('php://input'), true);
    if ($json && isset($json[$key])) return $json[$key];
    return $default;
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
        }
        
        $sql .= " ORDER BY priority DESC, created_at DESC LIMIT $limit OFFSET $offset";
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
        if ($type === 'events') {
            if (!empty($item['image_url'])) {
                $item['image_url'] = formatImageUrl($item['image_url']);
            }
        } elseif ($type === 'management_team') {
            // No image formatting needed for management team
        } else {
            // Gallery: Check media_url first (your DB uses this column)
            if (!empty($item['media_url'])) {
                $item['media_url'] = formatImageUrl($item['media_url']);
            }
            if (!empty($item['file_path'])) {
                $item['file_path'] = formatImageUrl($item['file_path']);
            }
            if (!empty($item['thumbnail_path'])) {
                $item['thumbnail_path'] = formatImageUrl($item['thumbnail_path']);
            }
        }
    }
    
    // Get categories
    $categories = [];
    if ($type === 'events') {
        try {
            $categories = $db->query("SELECT DISTINCT category FROM events WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            $categories = [];
        }
    } elseif ($type === 'management_team') {
        // No categories for management team
        $categories = [];
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
        $category = trim(getInput('category', 'General'));
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
        
        $image_url = formatImageUrl($image_url);
        
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
        
        echo json_encode(['success' => true, 'message' => 'Management team member created', 'id' => $db->lastInsertId()]);
    } else {
        $title = trim(getInput('title', ''));
        $description = trim(getInput('description', ''));
        $media_type = getInput('media_type', 'image');
        $file_path = getInput('file_path', '');
        $media_url = getInput('media_url', $file_path); // Use file_path as fallback for media_url
        $thumbnail_path = getInput('thumbnail_path', '');
        $category = trim(getInput('category', ''));
        $status = getInput('status', 'active');
        
        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        $media_url = formatImageUrl($media_url);
        $file_path = formatImageUrl($file_path);
        $thumbnail_path = formatImageUrl($thumbnail_path);
        
        // Insert into media_url (your DB's primary image column)
        $stmt = $db->prepare("INSERT INTO gallery_items (title, description, media_type, media_url, file_path, thumbnail_path, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $description, $media_type, $media_url, $file_path, $thumbnail_path, $category, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item created', 'id' => $db->lastInsertId()]);
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
        $category = trim(getInput('category', 'General'));
        $event_date = getInput('event_date', date('Y-m-d'));
        $status = getInput('status', 'published');
        $is_featured = (int)getInput('is_featured', 0);
        $image_url = getInput('image_url', '');
        
        if (empty($title) || empty($content)) {
            echo json_encode(['error' => 'Title and content are required']);
            return;
        }
        
        $image_url = formatImageUrl($image_url);
        
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
        
        echo json_encode(['success' => true, 'message' => 'Management team member updated']);
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
        $media_url = getInput('media_url', $file_path);
        $thumbnail_path = getInput('thumbnail_path', '');
        $category = trim(getInput('category', ''));
        $status = getInput('status', 'active');
        
        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            return;
        }
        
        $media_url = formatImageUrl($media_url);
        $file_path = formatImageUrl($file_path);
        $thumbnail_path = formatImageUrl($thumbnail_path);
        
        $stmt = $db->prepare("UPDATE gallery_items SET title=?, description=?, media_type=?, media_url=?, file_path=?, thumbnail_path=?, category=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $description, $media_type, $media_url, $file_path, $thumbnail_path, $category, $status, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Gallery item updated']);
    }
}

// ==================== DELETE ====================
function handleDelete($db, $type) {
    $ids = [];
    
    if (isset($_POST['ids'])) {
        $ids = is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']];
    } elseif (getInput('id', 0) > 0) {
        $ids = [(int)getInput('id')];
    }
    
    if (empty($ids)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            if (isset($input['ids'])) $ids = is_array($input['ids']) ? $input['ids'] : [$input['ids']];
            elseif (isset($input['id'])) $ids = [(int)$input['id']];
        }
    }
    
    if (empty($ids)) {
        echo json_encode(['error' => 'No items selected']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Delete files
    if ($type === 'events') {
        $stmt = $db->prepare("SELECT image_url FROM events WHERE id IN ($placeholders) AND image_url IS NOT NULL AND image_url != ''");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $fp = __DIR__ . '/' . ltrim($item['image_url'], '/');
            if (file_exists($fp)) @unlink($fp);
        }
        $table = 'events';
    } elseif ($type === 'management_team') {
        // No files to delete for management team
        $table = 'management_team';
    } elseif ($type === 'announcements') {
        // No files to delete for announcements
        $table = 'announcements';
    } else {
        $stmt = $db->prepare("SELECT media_url, file_path, thumbnail_path FROM gallery_items WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            foreach (['media_url', 'file_path', 'thumbnail_path'] as $col) {
                if (!empty($item[$col])) {
                    $fp = __DIR__ . '/' . ltrim($item[$col], '/');
                    if (file_exists($fp)) @unlink($fp);
                }
            }
        }
        $table = 'gallery_items';
    }
    
    $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    echo json_encode(['success' => true, 'message' => $stmt->rowCount() . ' item(s) deleted']);
}

// ==================== TOGGLE STATUS ====================
function handleToggleStatus($db, $type) {
    $id = (int)getInput('id', 0);
    
    if ($type === 'events') {
        $table = 'events';
        $stmt = $db->prepare("SELECT status FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        
        if ($current === false) {
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        
        $new = ($current === 'published') ? 'draft' : 'published';
    } elseif ($type === 'management_team') {
        $table = 'management_team';
        $stmt = $db->prepare("SELECT status FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        
        if ($current === false) {
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        
        $new = ($current === 'active') ? 'inactive' : 'active';
    } elseif ($type === 'announcements') {
        $table = 'announcements';
        $stmt = $db->prepare("SELECT status FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        
        if ($current === false) {
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        
        $new = ($current === 'active') ? 'inactive' : 'active';
    } else {
        $table = 'gallery_items';
        $stmt = $db->prepare("SELECT status FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();
        
        if ($current === false) {
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        
        $new = ($current === 'active') ? 'inactive' : 'active';
    }
    
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
    
    if (empty($ids)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $ids = $input['ids'] ?? [];
            $action = $action ?: ($input['bulk_action'] ?? '');
        }
    }
    
    if (empty($ids) || empty($action)) {
        echo json_encode(['error' => 'No items or action specified']);
        return;
    }
    
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    if ($type === 'events') {
        $table = 'events';
    } elseif ($type === 'management_team') {
        $table = 'management_team';
    } else {
        $table = 'gallery_items';
    }
    
    switch ($action) {
        case 'delete':
            $stmt = $db->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $msg = 'deleted';
            break;
        case 'publish':
            if ($type === 'events') {
                $stmt = $db->prepare("UPDATE $table SET status = 'published', updated_at = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $msg = 'published';
            } else {
                echo json_encode(['error' => 'Publish action only available for events']);
                return;
            }
            break;
        case 'draft':
            if ($type === 'events') {
                $stmt = $db->prepare("UPDATE $table SET status = 'draft', updated_at = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $msg = 'moved to draft';
            } else {
                echo json_encode(['error' => 'Draft action only available for events']);
                return;
            }
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
            echo json_encode(['error' => 'Invalid action: ' . $action]);
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
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode(['error' => 'Failed to create upload directory']);
            return;
        }
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'Partial upload',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
        ];
        $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
        $msg = $errors[$code] ?? 'Upload error code: ' . $code;
        echo json_encode(['error' => $msg]);
        return;
    }
    
    $file = $_FILES['image'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowed)) {
        echo json_encode(['error' => 'Invalid file type: ' . $fileType . '. Allowed: JPG, PNG, GIF, WebP']);
        return;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['error' => 'File too large. Max 5MB. Current: ' . round($file['size'] / 1024 / 1024, 1) . 'MB']);
        return;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $ext = 'jpg';
    
    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = 'uploads/' . $type . '/' . $fileName;
        
        // Create thumbnail for gallery
        $thumbPath = '';
        if ($type === 'gallery' && extension_loaded('gd')) {
            $thumbPath = createThumbnail($targetPath, $uploadDir, $fileName);
        }
        
        echo json_encode([
            'success' => true,
            'file_path' => $relativePath,
            'media_url' => $relativePath,
            'url' => $relativePath,
            'thumbnail_path' => $thumbPath ?: ''
        ]);
    } else {
        echo json_encode(['error' => 'Failed to save file. Check permissions.']);
    }
}

function createThumbnail($sourcePath, $uploadDir, $fileName) {
    if (!extension_loaded('gd')) return '';
    
    $thumbDir = $uploadDir . 'thumbnails/';
    if (!file_exists($thumbDir)) mkdir($thumbDir, 0755, true);
    
    $thumbPath = $thumbDir . 'thumb_' . $fileName;
    $info = getimagesize($sourcePath);
    if (!$info) return '';
    
    $src = null;
    switch ($info['mime']) {
        case 'image/jpeg': $src = imagecreatefromjpeg($sourcePath); break;
        case 'image/png': $src = imagecreatefrompng($sourcePath); break;
        case 'image/gif': $src = imagecreatefromgif($sourcePath); break;
        case 'image/webp': if (function_exists('imagecreatefromwebp')) $src = imagecreatefromwebp($sourcePath); break;
    }
    if (!$src) return '';
    
    $tw = 300;
    $th = (int)($info[1] * ($tw / $info[0]));
    $thumb = imagecreatetruecolor($tw, $th);
    
    if ($info['mime'] === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $info[0], $info[1]);
    
    switch ($info['mime']) {
        case 'image/jpeg': imagejpeg($thumb, $thumbPath, 80); break;
        case 'image/png': imagepng($thumb, $thumbPath, 8); break;
        case 'image/gif': imagegif($thumb, $thumbPath); break;
        case 'image/webp': if (function_exists('imagewebp')) imagewebp($thumb, $thumbPath, 80); break;
    }
    
    imagedestroy($src);
    imagedestroy($thumb);
    
    $type = basename(dirname($uploadDir));
    return 'uploads/' . $type . '/thumbnails/thumb_' . $fileName;
}

// ==================== STATS ====================
function handleStats($db) {
    try {
        echo json_encode([
            'success' => true,
            'data' => [
                'total_events' => (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn(),
                'published_events' => (int)$db->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn(),
                'featured_events' => (int)$db->query("SELECT COUNT(*) FROM events WHERE is_featured = 1 AND status = 'published'")->fetchColumn(),
                'draft_events' => (int)$db->query("SELECT COUNT(*) FROM events WHERE status = 'draft'")->fetchColumn(),
                'total_gallery' => (int)$db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn(),
                'active_gallery' => (int)$db->query("SELECT COUNT(*) FROM gallery_items WHERE status = 'active'")->fetchColumn(),
                'image_count' => (int)$db->query("SELECT COUNT(*) FROM gallery_items WHERE media_type = 'image'")->fetchColumn(),
                'video_count' => (int)$db->query("SELECT COUNT(*) FROM gallery_items WHERE media_type = 'video'")->fetchColumn()
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'data' => [
            'total_events' => 0, 'published_events' => 0, 'featured_events' => 0,
            'draft_events' => 0, 'total_gallery' => 0, 'active_gallery' => 0,
            'image_count' => 0, 'video_count' => 0
        ]]);
    }
}
?>