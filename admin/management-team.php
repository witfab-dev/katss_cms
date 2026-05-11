<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin_login();

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$member_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize_input($_POST['name']);
        $telephone = sanitize_input($_POST['telephone']);
        $post = sanitize_input($_POST['post']);
        $status = sanitize_input($_POST['status']);
        $sort_order = (int)$_POST['sort_order'];
        
        // Validate required fields
        $errors = [];
        if (empty($name)) $errors[] = "Name is required";
        if (empty($telephone)) $errors[] = "Telephone is required";
        if (empty($post)) $errors[] = "Post/Role is required";
        
        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO management_team (name, telephone, post, status, sort_order, created_by) 
                                     VALUES (:name, :telephone, :post, :status, :sort_order, :created_by)");
                $stmt->bindParam(':created_by', $_SESSION['admin_id']);
                log_activity($_SESSION['admin_id'], 'Created management team member', "Created team member: $name");
            } else {
                $stmt = $db->prepare("UPDATE management_team SET name = :name, telephone = :telephone, post = :post, 
                                     status = :status, sort_order = :sort_order WHERE id = :id");
                $stmt->bindParam(':id', $member_id);
                log_activity($_SESSION['admin_id'], 'Updated management team member', "Updated team member: $name");
            }
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':post', $post);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':sort_order', $sort_order);
            
            if ($stmt->execute()) {
                header("Location: management-team.php?success=" . urlencode($action === 'create' ? 'Team member created successfully' : 'Team member updated successfully'));
                exit();
            } else {
                $error = "Failed to save team member";
            }
        }
    } elseif ($action === 'delete' && $member_id) {
        $stmt = $db->prepare("DELETE FROM management_team WHERE id = :id");
        $stmt->bindParam(':id', $member_id);
        
        if ($stmt->execute()) {
            log_activity($_SESSION['admin_id'], 'Deleted management team member', "Deleted team member ID: $member_id");
            header("Location: management-team.php?success=" . urlencode('Team member deleted successfully'));
            exit();
        } else {
            $error = "Failed to delete team member";
        }
    }
}

// Get member data for edit
$member = null;
if ($action === 'edit' && $member_id) {
    $stmt = $db->prepare("SELECT * FROM management_team WHERE id = :id");
    $stmt->bindParam(':id', $member_id);
    $stmt->execute();
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        header("Location: management-team.php?error=" . urlencode('Team member not found'));
        exit();
    }
}

// Get team members list for list view
$members = [];
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM management_team WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR post LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status_filter;
    }
    
    $sql .= " ORDER BY sort_order ASC, created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Team - KATSS Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Page Content -->
            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                    <!-- List View -->
                    <div class="page-header">
                        <h1><i class="fas fa-users"></i> Management Team</h1>
                        <a href="management-team.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Team Member
                        </a>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filters">
                        <form method="GET" class="filter-form">
                            <input type="text" name="search" placeholder="Search name or post..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>" class="form-control">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status_filter ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status_filter ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="management-team.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </form>
                    </div>
                    
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Telephone</th>
                                    <th>Post/Role</th>
                                    <th>Status</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No team members found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($members as $m): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($m['name']); ?></td>
                                            <td><?php echo htmlspecialchars($m['telephone']); ?></td>
                                            <td><?php echo htmlspecialchars($m['post']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $m['status']; ?>">
                                                    <?php echo ucfirst($m['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $m['sort_order']; ?></td>
                                            <td class="actions">
                                                <a href="management-team.php?action=edit&id=<?php echo $m['id']; ?>" 
                                                   class="btn btn-sm btn-outline" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="management-team.php?action=delete&id=<?php echo $m['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this team member?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php else: ?>
                    <!-- Create/Edit Form -->
                    <div class="page-header">
                        <h1>
                            <i class="fas fa-user"></i> 
                            <?php echo $action === 'create' ? 'Add Team Member' : 'Edit Team Member'; ?>
                        </h1>
                        <a href="management-team.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                    
                    <div class="form-container">
                        <form method="POST" class="admin-form">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error): ?>
                                        <p><?php echo htmlspecialchars($error); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Name *</label>
                                    <input type="text" id="name" name="name" required
                                           value="<?php echo htmlspecialchars($member['name'] ?? ''); ?>"
                                           class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telephone">Telephone *</label>
                                    <input type="tel" id="telephone" name="telephone" required
                                           value="<?php echo htmlspecialchars($member['telephone'] ?? ''); ?>"
                                           class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="post">Post/Role *</label>
                                <input type="text" id="post" name="post" required
                                       value="<?php echo htmlspecialchars($member['post'] ?? ''); ?>"
                                       class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="active" <?php echo ($member['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($member['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sort_order">Sort Order</label>
                                    <input type="number" id="sort_order" name="sort_order" 
                                           value="<?php echo $member['sort_order'] ?? 0; ?>"
                                           min="0" class="form-control">
                                    <small>Lower numbers appear first</small>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 
                                    <?php echo $action === 'create' ? 'Create Team Member' : 'Update Team Member'; ?>
                                </button>
                                <a href="management-team.php" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-generate slug from title (if needed in future)
        document.addEventListener('DOMContentLoaded', function() {
            // Any JavaScript functionality can be added here
        });
    </script>
</body>
</html>
