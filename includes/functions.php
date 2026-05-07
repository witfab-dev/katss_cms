<?php
// includes/functions.php - Common functions for KATSS CMS

// Security functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header("Location: ../admin/login.php");
        exit();
    }
}

// File upload functions
function upload_file($file, $upload_dir, $allowed_types = []) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Validate file type
    if (!empty($allowed_types)) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('File type not allowed.');
        }
    }

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $filename = time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    } else {
        throw new Exception('Failed to move uploaded file.');
    }
}

function create_thumbnail($source_path, $thumb_path, $thumb_width = 300, $thumb_height = 200) {
    if (!file_exists($source_path)) {
        return false;
    }

    $image_info = getimagesize($source_path);
    if (!$image_info) {
        return false;
    }

    $mime = $image_info['mime'];
    $width = $image_info[0];
    $height = $image_info[1];

    // Create image resource based on MIME type
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }

    // Calculate thumbnail dimensions
    $ratio = max($thumb_width / $width, $thumb_height / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;

    // Create thumbnail
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // Handle transparency for PNG/GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $thumb_width, $thumb_height, $transparent);
    }

    // Resize and crop
    $src_x = ($width - $new_width) / 2;
    $src_y = ($height - $new_height) / 2;
    
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $thumb_width, $thumb_height, $new_width, $new_height);

    // Save thumbnail
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumb, $thumb_path, 85);
            break;
        case 'image/png':
            imagepng($thumb, $thumb_path, 8);
            break;
        case 'image/gif':
            imagegif($thumb, $thumb_path);
            break;
    }

    imagedestroy($source);
    imagedestroy($thumb);

    return true;
}

// Pagination functions
function get_pagination($total_items, $items_per_page, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;

    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}

function render_pagination($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }

    $html = '<div class="pagination">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $base_url . '?page=' . $pagination['prev_page'] . '" class="page-link">';
        $html .= '<i class="bi bi-chevron-left"></i>';
        $html .= '</a>';
    }

    // Page numbers
    $start_page = max(1, $pagination['current_page'] - 2);
    $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);

    if ($start_page > 1) {
        $html .= '<a href="' . $base_url . '?page=1" class="page-link">1</a>';
        if ($start_page > 2) {
            $html .= '<span class="page-link">...</span>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = $i == $pagination['current_page'] ? ' active' : '';
        $html .= '<a href="' . $base_url . '?page=' . $i . '" class="page-link' . $active_class . '">' . $i . '</a>';
    }

    if ($end_page < $pagination['total_pages']) {
        if ($end_page < $pagination['total_pages'] - 1) {
            $html .= '<span class="page-link">...</span>';
        }
        $html .= '<a href="' . $base_url . '?page=' . $pagination['total_pages'] . '" class="page-link">' . $pagination['total_pages'] . '</a>';
    }

    // Next button
    if ($pagination['has_next']) {
        $html .= '<a href="' . $base_url . '?page=' . $pagination['next_page'] . '" class="page-link">';
        $html .= '<i class="bi bi-chevron-right"></i>';
        $html .= '</a>';
    }

    $html .= '</div>';
    return $html;
}

// Date formatting functions
function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return format_date($datetime);
    }
}

// Settings functions
function get_setting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

function update_setting($key, $value) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) 
                          VALUES (:key, :value) 
                          ON DUPLICATE KEY UPDATE setting_value = :value");
    $stmt->bindParam(':key', $key);
    $stmt->bindParam(':value', $value);
    
    return $stmt->execute();
}

// Activity logging
function log_activity($user_id, $action, $details = '') {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) 
                          VALUES (:user_id, :action, :details, :ip_address, :user_agent)");
    
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    
    return $stmt->execute();
}

// Email functions
function send_email($to, $subject, $message, $from = '') {
    $headers = [];
    
    if ($from) {
        $headers[] = 'From: ' . $from;
    } else {
        $headers[] = 'From: ' . get_setting('site_name') . ' <' . get_setting('contact_email') . '>';
    }
    
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Validation functions
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_required($fields) {
    $errors = [];
    foreach ($fields as $field => $value) {
        if (empty(trim($value))) {
            $errors[$field] = ucfirst($field) . ' is required.';
        }
    }
    return $errors;
}

function validate_min_length($field, $value, $min_length) {
    if (strlen(trim($value)) < $min_length) {
        return ucfirst($field) . ' must be at least ' . $min_length . ' characters.';
    }
    return null;
}

// URL functions
function base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

function admin_url($path = '') {
    return base_url() . '/admin/' . ltrim($path, '/');
}

function site_url($path = '') {
    return base_url() . '/' . ltrim($path, '/');
}

// Security: CSRF protection
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Clean up old files
function cleanup_old_files($directory, $max_age_days = 30) {
    $files = glob($directory . '/*');
    $now = time();
    $deleted = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) > ($max_age_days * 24 * 60 * 60)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
    }

    return $deleted;
}
?>
