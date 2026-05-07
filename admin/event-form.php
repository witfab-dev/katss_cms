<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$isEdit = false;
$event = [
    'id' => '',
    'title' => '',
    'slug' => '',
    'content' => '',
    'excerpt' => '',
    'image_url' => '',
    'category' => 'General',
    'event_date' => date('Y-m-d'),
    'is_featured' => 0,
    'status' => 'published'
];
$success = '';
$error = '';

if (isset($_GET['id'])) {
    $isEdit = true;
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        header("Location: events.php");
        exit();
    }
}

// Generate slug from title
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $excerpt = trim($_POST['excerpt']) ?: substr(strip_tags($content), 0, 200);
    $category = trim($_POST['category']) ?: 'General';
    $event_date = $_POST['event_date'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $status = $_POST['status'];
    $image_url = $event['image_url'] ?? ''; // Keep existing image by default
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/events/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['image'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $error = "File is too large. Maximum size is 5MB.";
        } elseif (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old image if exists and we're editing
            if ($isEdit && !empty($event['image_url'])) {
                $oldFile = __DIR__ . '/' . basename($event['image_url']);
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Store relative path from admin directory
            $image_url = 'uploads/events/' . $fileName;
        } else {
            $error = "Failed to upload image. Please try again.";
        }
    }
    
    if (empty($error)) {
        // Generate slug
        $slug = createSlug($title);
        
        try {
            if ($isEdit) {
                // Check unique slug
                $checkStmt = $db->prepare("SELECT id FROM events WHERE slug = ? AND id != ?");
                $checkStmt->execute([$slug, $_POST['id']]);
                if ($checkStmt->rowCount() > 0) {
                    $slug = $slug . '-' . time();
                }
                
                $stmt = $db->prepare("UPDATE events SET title=?, slug=?, content=?, excerpt=?, image_url=?, category=?, event_date=?, is_featured=?, status=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $excerpt, $image_url, $category, $event_date, $is_featured, $status, $_POST['id']]);
                $success = "Event updated successfully!";
            } else {
                // Check unique slug
                $checkStmt = $db->prepare("SELECT id FROM events WHERE slug = ?");
                $checkStmt->execute([$slug]);
                if ($checkStmt->rowCount() > 0) {
                    $slug = $slug . '-' . time();
                }
                
                $stmt = $db->prepare("INSERT INTO events (title, slug, content, excerpt, image_url, category, event_date, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $image_url, $category, $event_date, $is_featured, $status]);
                $success = "Event created successfully!";
            }
            
            // Refresh event data if editing
            if ($isEdit) {
                $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Event - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        .admin-header h1 { font-size: 22px; display: flex; align-items: center; gap: 10px; }
        .admin-nav { display: flex; gap: 5px; }
        .admin-nav a {
            color: white; text-decoration: none; padding: 8px 18px;
            border-radius: 8px; display: flex; align-items: center; gap: 8px;
            transition: all 0.3s;
        }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); }
        .logout-btn {
            background: rgba(220,53,69,0.9); padding: 8px 20px; border-radius: 8px;
            text-decoration: none; color: white; display: flex; align-items: center; gap: 8px;
        }
        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }
        .page-header h2 { color: #003366; }
        .btn-back {
            background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .alert {
            padding: 15px 20px; border-radius: 10px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #fef2f2; color: #dc2626; border-left: 4px solid #dc2626; }
        
        .form-card {
            background: white; border-radius: 15px; padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block; margin-bottom: 8px; color: #333;
            font-weight: 600; font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0;
            border-radius: 8px; font-size: 15px; font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: #003366;
            box-shadow: 0 0 0 3px rgba(0,51,102,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .image-upload-area {
            border: 2px dashed #ddd; border-radius: 12px; padding: 20px;
            text-align: center; cursor: pointer; transition: all 0.3s;
            background: #fafafa; position: relative;
        }
        .image-upload-area:hover { border-color: #003366; background: #f0f5ff; }
        .image-upload-area i { font-size: 48px; color: #999; margin-bottom: 10px; display: block; }
        .image-upload-area p { color: #666; font-size: 13px; }
        .image-upload-area input[type="file"] {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        .image-preview {
            display: none; margin-top: 15px; border-radius: 10px;
            overflow: hidden; position: relative;
        }
        .image-preview img { width: 100%; max-height: 250px; object-fit: cover; }
        .image-preview .remove-image {
            position: absolute; top: 10px; right: 10px; background: #dc3545;
            color: white; border: none; border-radius: 50%; width: 30px; height: 30px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .current-image { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; }
        .current-image img { max-width: 200px; border-radius: 8px; margin-top: 5px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 5px; }
        .checkbox-group input[type="checkbox"] {
            width: 18px; height: 18px; accent-color: #003366;
        }
        .checkbox-group label { margin-bottom: 0; }
        
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #003366 0%, #004d99 100%);
            color: white; border: none; border-radius: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,51,102,0.3); }
        .help-text { font-size: 12px; color: #888; margin-top: 5px; }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
            .form-card { padding: 20px; }
            .admin-nav a span { display: none; }
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
        <div class="page-header">
            <h2><i class="bi bi-<?php echo $isEdit ? 'pencil-square' : 'plus-circle'; ?>"></i> 
                <?php echo $isEdit ? 'Edit Event' : 'Create New Event'; ?>
            </h2>
            <a href="events.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Events</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="" enctype="multipart/form-data" id="eventForm">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Event Title *</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>"
                           placeholder="Enter event title">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <?php
                            $categories = ['General', 'Academics', 'Sports', 'Technology', 'Campus Life', 'Service', 'Alumni', 'Announcement'];
                            $selectedCat = $event['category'] ?? 'General';
                            foreach ($categories as $cat):
                            ?>
                                <option value="<?php echo $cat; ?>" <?php echo $selectedCat == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_date">Event Date *</label>
                        <input type="date" id="event_date" name="event_date" required
                               value="<?php echo htmlspecialchars($event['event_date'] ?? date('Y-m-d')); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="excerpt">Short Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="2" 
                              placeholder="Brief summary shown in news cards (150-200 characters)"><?php echo htmlspecialchars($event['excerpt'] ?? ''); ?></textarea>
                    <div class="help-text">Leave empty to auto-generate from content</div>
                </div>
                
                <div class="form-group">
                    <label for="content">Full Content *</label>
                    <textarea id="content" name="content" rows="8" required
                              placeholder="Write the full event/news content here..."><?php echo htmlspecialchars($event['content'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Feature Image</label>
                    <div class="image-upload-area" id="uploadArea">
                        <i class="bi bi-cloud-upload"></i>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <p style="font-size: 11px;">JPG, PNG, GIF or WebP (Max 5MB)</p>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="remove-image" onclick="removeImage()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <?php if ($isEdit && !empty($event['image_url'])): ?>
                    <div class="current-image" id="currentImage">
                        <strong>Current Image:</strong>
                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="Current event image">
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo ($event['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($event['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo ($event['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                   <?php echo ($event['is_featured'] ?? 0) == 1 ? 'checked' : ''; ?>>
                            <label for="is_featured">⭐ Feature this event on homepage</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="bi bi-<?php echo $isEdit ? 'check-circle' : 'plus-circle'; ?>"></i>
                    <?php echo $isEdit ? 'Update Event' : 'Create Event'; ?>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const uploadArea = document.getElementById('uploadArea');
        const currentImage = document.getElementById('currentImage');
        
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                    uploadArea.style.display = 'none';
                    if (currentImage) currentImage.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
        
        function removeImage() {
            imageInput.value = '';
            imagePreview.style.display = 'none';
            uploadArea.style.display = 'block';
            if (currentImage) currentImage.style.display = 'block';
        }
        
        // Drag and drop support
        ['dragover', 'dragenter'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.style.borderColor = '#003366';
                this.style.background = '#f0f5ff';
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.style.borderColor = '#ddd';
                this.style.background = '#fafafa';
            });
        });
        
        uploadArea.addEventListener('drop', function(e) {
            imageInput.files = e.dataTransfer.files;
            imageInput.dispatchEvent(new Event('change'));
        });
        
        // Auto-generate excerpt from content
        const contentField = document.getElementById('content');
        const excerptField = document.getElementById('excerpt');
        
        contentField.addEventListener('blur', function() {
            if (!excerptField.value.trim() && this.value.trim()) {
                let excerpt = this.value.trim().replace(/<[^>]*>/g, '').substring(0, 200);
                if (excerpt.length === 200) excerpt += '...';
                excerptField.value = excerpt;
            }
        });
    </script>
</body>
</html>