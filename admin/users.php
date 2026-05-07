<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin_login();

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $username = sanitize_input($_POST['username']);
        $full_name = sanitize_input($_POST['full_name']);
        $email = sanitize_input($_POST['email']);
        $role = sanitize_input($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        
        // Validate required fields
        $errors = [];
        if (empty($username)) $errors[] = "Username is required";
        if (empty($full_name)) $errors[] = "Full name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!validate_email($email)) $errors[] = "Invalid email format";
        if ($action === 'create' && empty($password)) $errors[] = "Password is required";
        if ($action === 'edit' && !empty($password) && strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
        
        // Check if username is unique
        $username_check = $db->prepare("SELECT id FROM admin_users WHERE username = :username AND id != :id");
        $username_check->bindParam(':username', $username);
        $username_check->bindParam(':id', $user_id);
        $username_check->execute();
        if ($username_check->fetch()) {
            $errors[] = "Username already exists";
        }
        
        // Check if email is unique
        $email_check = $db->prepare("SELECT id FROM admin_users WHERE email = :email AND id != :id");
        $email_check->bindParam(':email', $email);
        $email_check->bindParam(':id', $user_id);
        $email_check->execute();
        if ($email_check->fetch()) {
            $errors[] = "Email already exists";
        }
        
        if (empty($errors)) {
            if ($action === 'create') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO admin_users (username, password, full_name, email, role, is_active) 
                                     VALUES (:username, :password, :full_name, :email, :role, :is_active)");
                $stmt->bindParam(':password', $hashed_password);
                log_activity($_SESSION['admin_id'], 'Created user', "Created user: $username");
            } else {
                $stmt = $db->prepare("UPDATE admin_users SET username = :username, full_name = :full_name, email = :email, 
                                     role = :role, is_active = :is_active WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                
                // Update password if provided
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $password_stmt = $db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
                    $password_stmt->bindParam(':password', $hashed_password);
                    $password_stmt->bindParam(':id', $user_id);
                    $password_stmt->execute();
                }
                
                log_activity($_SESSION['admin_id'], 'Updated user', "Updated user: $username");
            }
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':is_active', $is_active);
            
            if ($stmt->execute()) {
                header("Location: users.php?success=" . urlencode($action === 'create' ? 'User created successfully' : 'User updated successfully'));
                exit();
            } else {
                $error = "Failed to save user";
            }
        }
    } elseif ($action === 'delete' && $user_id) {
        // Prevent self-deletion
        if ($user_id == $_SESSION['admin_id']) {
            $error = "You cannot delete your own account";
        } else {
            $stmt = $db->prepare("DELETE FROM admin_users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['admin_id'], 'Deleted user', "Deleted user ID: $user_id");
                header("Location: users.php?success=" . urlencode('User deleted successfully'));
                exit();
            } else {
                $error = "Failed to delete user";
            }
        }
    }
}

// Get user data for edit
$user = null;
if ($action === 'edit' && $user_id) {
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: users.php?error=" . urlencode('User not found'));
        exit();
    }
}

// Get users list for list view
$users = [];
if ($action === 'list') {
    $search = sanitize_input($_GET['search'] ?? '');
    $role_filter = sanitize_input($_GET['role'] ?? '');
    $status_filter = sanitize_input($_GET['status'] ?? '');
    $page_num = (int)($_GET['page'] ?? 1);
    $items_per_page = 10;
    $offset = ($page_num - 1) * $items_per_page;
    
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(username LIKE :search OR full_name LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($role_filter) {
        $where_conditions[] = "role = :role";
        $params[':role'] = $role_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "is_active = :status";
        $params[':status'] = $status_filter === 'active' ? 1 : 0;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM admin_users $where_clause";
    $count_stmt = $db->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->fetchColumn();
    
    // Get users
    $sql = "SELECT * FROM admin_users $where_clause ORDER BY created_at DESC LIMIT :offset, :limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pagination = get_pagination($total_items, $items_per_page, $page_num);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> User - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php if ($action === 'list'): ?>
            <div class="page-header">
                <h1>Users</h1>
                <a href="users.php?action=create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New User
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
                    <h3 class="table-title">All Users</h3>
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" class="form-control" style="width: 200px; display: inline-block;">
                        <select name="role" class="form-control" style="width: 120px; display: inline-block;">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php echo $role_filter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="editor" <?php echo $role_filter === 'editor' ? 'selected' : ''; ?>>Editor</option>
                        </select>
                        <select name="status" class="form-control" style="width: 120px; display: inline-block;">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="bi bi-people" style="font-size: 48px; color: #ccc;"></i>
                                    <p style="margin-top: 10px; color: #999;">No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar" style="width: 35px; height: 35px; font-size: 12px;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                <br>
                                                <small style="color: var(--gray-color);"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] === 'super_admin' ? 'danger' : ($user['role'] === 'admin' ? 'info' : 'warning'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $user['last_login'] ? time_ago($user['last_login']) : 'Never'; ?>
                                    </td>
                                    <td><?php echo format_date($user['created_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (isset($pagination)): ?>
                    <?php echo render_pagination($pagination, 'users.php?' . http_build_query(array_diff_key($_GET, ['page' => '']))); ?>
                <?php endif; ?>
            </div>
            
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <div class="page-header">
                <h1><?php echo $action === 'create' ? 'Create New User' : 'Edit User'; ?></h1>
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Users
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
                <form method="POST" id="userForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Username <span class="required">*</span></label>
                            <input type="text" name="username" class="form-control" id="username" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Full Name <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control">
                                <option value="editor" <?php echo ($user['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="super_admin" <?php echo ($user['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            Active Account
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <?php echo $action === 'create' ? 'Password <span class="required">*</span>' : 'Password (leave empty to keep current)'; ?>
                        </label>
                        <input type="password" name="password" class="form-control" 
                               <?php echo $action === 'create' ? 'required' : ''; ?>>
                        <small class="form-text">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <?php echo $action === 'create' ? 'Create User' : 'Update User'; ?>
                        </button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
        <?php endif; ?>
    </main>
    
    <script>
        function confirmDelete(id, name) {
            if (confirm('Are you sure you want to delete the user "' + name + '"? This action cannot be undone.')) {
                window.location.href = 'users.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
