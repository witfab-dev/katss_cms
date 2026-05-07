<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin_login();

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$page_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = sanitize_input($_POST['title']);
        $slug = sanitize_input($_POST['slug']);
        $content = $_POST['content'];
        $meta_title = sanitize_input($_POST['meta_title']);
        $meta_description = sanitize_input($_POST['meta_description']);
        $meta_keywords = sanitize_input($_POST['meta_keywords']);
        $status = sanitize_input($_POST['status']);
        $template = sanitize_input($_POST['template']);
        
        // Validate required fields
        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($slug)) $errors[] = "Slug is required";
        if (empty($content)) $errors[] = "Content is required";
        
        // Check if slug is unique
        $slug_check = $db->prepare("SELECT id FROM pages WHERE slug = :slug AND id != :id");
        $slug_check->bindParam(':slug', $slug);
        $slug_check->bindParam(':id', $page_id);
        $slug_check->execute();
        if ($slug_check->fetch()) {
            $errors[] = "Slug already exists";
        }
        
        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, status, template, created_by) 
                                     VALUES (:title, :slug, :content, :meta_title, :meta_description, :meta_keywords, :status, :template, :created_by)");
                $stmt->bindParam(':created_by', $_SESSION['admin_id']);
                log_activity($_SESSION['admin_id'], 'Created page', "Created page: $title");
            } else {
                $stmt = $db->prepare("UPDATE pages SET title = :title, slug = :slug, content = :content, meta_title = :meta_title, 
                                     meta_description = :meta_description, meta_keywords = :meta_keywords, status = :status, template = :template 
                                     WHERE id = :id");
                $stmt->bindParam(':id', $page_id);
                log_activity($_SESSION['admin_id'], 'Updated page', "Updated page: $title");
            }
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':meta_title', $meta_title);
            $stmt->bindParam(':meta_description', $meta_description);
            $stmt->bindParam(':meta_keywords', $meta_keywords);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':template', $template);
            
            if ($stmt->execute()) {
                header("Location: pages.php?success=" . urlencode($action === 'create' ? 'Page created successfully' : 'Page updated successfully'));
                exit();
            } else {
                $error = "Failed to save page";
            }
        }
    } elseif ($action === 'delete' && $page_id) {
        $stmt = $db->prepare("DELETE FROM pages WHERE id = :id");
        $stmt->bindParam(':id', $page_id);
        
        if ($stmt->execute()) {
            log_activity($_SESSION['admin_id'], 'Deleted page', "Deleted page ID: $page_id");
            header("Location: pages.php?success=" . urlencode('Page deleted successfully'));
            exit();
        } else {
            $error = "Failed to delete page";
        }
    }
}

// Get page data for edit
$page = null;
if ($action === 'edit' && $page_id) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = :id");
    $stmt->bindParam(':id', $page_id);
    $stmt->execute();
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        header("Location: pages.php?error=" . urlencode('Page not found'));
        exit();
    }
}

// Get pages list for list view
$pages = [];
if ($action === 'list') {
    $search = sanitize_input($_GET['search'] ?? '');
    $status_filter = sanitize_input($_GET['status'] ?? '');
    $page_num = (int)($_GET['page'] ?? 1);
    $items_per_page = 10;
    $offset = ($page_num - 1) * $items_per_page;
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(title LIKE :search OR content LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($status_filter) {
        $where_conditions[] = "status = :status";
        $params[':status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM pages $where_clause";
    $count_stmt = $db->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->fetchColumn();
    
    // Get pages
    $sql = "SELECT * FROM pages $where_clause ORDER BY updated_at DESC LIMIT :offset, :limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pagination = get_pagination($total_items, $items_per_page, $page_num);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> Page - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php if ($action === 'list'): ?>
            <div class="page-header">
                <h1>Pages</h1>
                <a href="pages.php?action=create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Page
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <div class="table-header">
                    <h3 class="table-title">All Pages</h3>
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search pages..." value="<?php echo htmlspecialchars($search); ?>" class="form-control" style="width: 200px; display: inline-block;">
                        <select name="status" class="form-control" style="width: 120px; display: inline-block;">
                            <option value="">All Status</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Template</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="bi bi-file-earmark-text" style="font-size: 48px; color: #ccc;"></i>
                                    <p style="margin-top: 10px; color: #999;">No pages found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                        <?php if ($page['is_homepage']): ?>
                                            <span class="badge badge-info" style="margin-left: 10px;">Homepage</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                    <td>
                                        <span class="badge badge-<?php echo $page['status'] === 'published' ? 'success' : ($page['status'] === 'draft' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($page['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($page['template']); ?></td>
                                    <td><?php echo time_ago($page['updated_at']); ?></td>
                                    <td>
                                        <a href="pages.php?action=edit&id=<?php echo $page['id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (isset($pagination)): ?>
                    <?php echo render_pagination($pagination, 'pages.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))); ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <div class="page-header">
                <h1><?php echo $action === 'create' ? 'Create New Page' : 'Edit Page'; ?></h1>
                <a href="pages.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Pages
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" id="pageForm">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="form-label">Page Title <span class="required">*</span></label>
                            <input type="text" name="title" class="form-control" id="title" 
                                   value="<?php echo htmlspecialchars($page['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="draft" <?php echo ($page['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($page['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo ($page['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="form-label">URL Slug <span class="required">*</span></label>
                            <input type="text" name="slug" class="form-control" id="slug" 
                                   value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" required>
                            <small class="form-text">This will be used in the URL: /pages/<strong>slug</strong></small>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label">Template</label>
                            <select name="template" class="form-control">
                                <option value="default" <?php echo ($page['template'] ?? '') === 'default' ? 'selected' : ''; ?>>Default</option>
                                <option value="full-width" <?php echo ($page['template'] ?? '') === 'full-width' ? 'selected' : ''; ?>>Full Width</option>
                                <option value="sidebar" <?php echo ($page['template'] ?? '') === 'sidebar' ? 'selected' : ''; ?>>With Sidebar</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Page Content <span class="required">*</span></label>
                        <textarea name="content" class="form-control" id="content" rows="15" required><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-section">
                        <h3>SEO Settings</h3>
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>"
                                   placeholder="Leave empty to use page title">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3" 
                                      placeholder="Brief description for search engines"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" 
                                   value="<?php echo htmlspecialchars($page['meta_keywords'] ?? ''); ?>"
                                   placeholder="Comma-separated keywords">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <?php echo $action === 'create' ? 'Create Page' : 'Update Page'; ?>
                        </button>
                        <a href="pages.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
        <?php endif; ?>
    </main>
    
    <script>
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            if (!document.getElementById('slug').value || document.getElementById('slug').dataset.autoGenerated) {
                document.getElementById('slug').value = this.value.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                document.getElementById('slug').dataset.autoGenerated = 'true';
            }
        });
        
        document.getElementById('slug').addEventListener('input', function() {
            delete this.dataset.autoGenerated;
        });
        
        function confirmDelete(id, title) {
            if (confirm('Are you sure you want to delete the page "' + title + '"? This action cannot be undone.')) {
                window.location.href = 'pages.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
