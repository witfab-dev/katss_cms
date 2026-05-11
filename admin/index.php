<?php
session_start();

// Check for password reset success message
$resetSuccess = '';
if (isset($_SESSION['password_reset_success']) && $_SESSION['password_reset_success'] === true) {
    $resetSuccess = "Your password has been reset successfully! Please login with your new password.";
    unset($_SESSION['password_reset_success']);
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Stay on index, it's the SPA
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once '../config/database.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $username]);
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_fullname'] = $user['full_name'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_phone'] = $user['phone'] ?? '';
                    $_SESSION['admin_avatar'] = $user['avatar'] ?? '';
                    
                    // Update last login
                    $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    // Return JSON for AJAX
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'redirect' => 'dashboard']);
                        exit();
                    }
                } else {
                    $error = "Invalid password!";
                }
            } else {
                $error = "Username or email not found!";
            }
        } catch (Exception $e) {
            $error = "Database error. Please try again.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    require_once '../config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $admin_id = $_SESSION['admin_id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "Full name is required";
        }
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check if email exists for other users
        $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already used by another account";
        }
        
        // Handle password change
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $errors[] = "Current password is required to change password";
            } else {
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
                $stmt->execute([$admin_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($current_password, $user['password'])) {
                    $errors[] = "Current password is incorrect";
                }
            }
            
            if (empty($new_password)) {
                $errors[] = "New password is required";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match";
            }
        }
        
        // Handle avatar upload
        $avatar_path = $_SESSION['admin_avatar'] ?? '';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowed)) {
                $errors[] = "Invalid avatar file type. Allowed: JPG, PNG, GIF, WebP";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = "Avatar file too large. Maximum 5MB";
            } else {
                $uploadDir = __DIR__ . '/uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Delete old avatar
                if (!empty($avatar_path)) {
                    $oldFile = __DIR__ . '/' . ltrim($avatar_path, '/');
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = 'avatar_' . $admin_id . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $avatar_path = 'uploads/avatars/' . $fileName;
                } else {
                    $errors[] = "Failed to upload avatar";
                }
            }
        }
        
        if (empty($errors)) {
            // Update profile
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ?, phone = ?, avatar = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $avatar_path, $hashed_password, $admin_id]);
            } else {
                $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ?, phone = ?, avatar = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $avatar_path, $admin_id]);
            }
            
            // Update session
            $_SESSION['admin_fullname'] = $full_name;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_phone'] = $phone;
            $_SESSION['admin_avatar'] = $avatar_path;
            
            $success = "Profile updated successfully!";
        } else {
            $error = implode("<br>", $errors);
        }
    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KATSS - Admin Panel</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        /* Profile Modal Styles */
        .profile-avatar-section {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin-bottom: 10px;
        }
        
        .profile-avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e0e7ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .profile-upload-btn {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .profile-upload-btn:hover {
            background: var(--primary-dark);
        }
        
        .profile-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }
    </style>
</head>
<body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">

<!-- LOGIN SCREEN -->
<div id="loginScreen" class="login-screen <?php echo $isLoggedIn ? 'hidden' : ''; ?>">
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="../images/logo.jpeg" alt="KATSS Logo">
            </div>
            <h1>KATSS ADMIN PANEL</h1>
            <p>Admin Panel Login</p>
        </div>
        <?php if ($resetSuccess): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $resetSuccess; ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Username or Email</label>
                <div class="input-icon">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" name="username" placeholder="Enter username or email" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="bi bi-key-fill"></i>
                    <input type="password" name="password" id="loginPassword" placeholder="Enter password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('loginPassword', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>
        
        <div class="login-footer">
            <a href="forgot-password.php">Forgot Password?</a>
        </div>
    </div>
</div>

<!-- ADMIN PANEL (hidden until login) -->
<div id="adminPanel" class="admin-panel <?php echo $isLoggedIn ? '' : 'hidden'; ?>">
    
    <!-- Header -->
    <header class="admin-header">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1>
            <?php 
            $avatar_path = !empty($_SESSION['admin_avatar']) ? $_SESSION['admin_avatar'] : '../images/logo.jpeg';
            // Check if avatar file exists, fallback to logo if not
            if (!empty($_SESSION['admin_avatar']) && !file_exists(__DIR__ . '/' . ltrim($_SESSION['admin_avatar'], '/'))) {
                $avatar_path = '../images/logo.jpeg';
            }
            ?>
            <img src="<?php echo htmlspecialchars($avatar_path); ?>" 
                 alt="KATSS" style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%; object-fit: cover;">
             ADMIN PANEL
        </h1>
        <div class="header-right">
            <span class="user-info" onclick="openProfileModal()" style="cursor: pointer;" title="Edit Profile">
                <i class="bi bi-person-circle"></i>
                <?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?>
            </span>
            <a href="../public/index.php" class="btn-website" target="_blank" title="Visit Website">
                <i class="bi bi-globe"></i>
            </a>
            <a href="?logout=1" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-item active" data-page="dashboard">
                <i class="bi bi-house-door"></i> <span>Dashboard</span>
            </a>
            <a href="#events" class="nav-item" data-page="events">
                <i class="bi bi-megaphone"></i> <span>Events & News</span>
            </a>
            <a href="#gallery" class="nav-item" data-page="gallery">
                <i class="bi bi-images"></i> <span>Gallery</span>
            </a>
            <a href="#management-team" class="nav-item" data-page="management-team">
                <i class="bi bi-person-badge"></i> <span>Management Team</span>
            </a>
            <a href="#announcements" class="nav-item" data-page="announcements">
                <i class="bi bi-megaphone"></i> <span>Announcements</span>
            </a>
        </nav>
    </aside>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <main class="admin-main">
        
        <!-- DASHBOARD PAGE -->
        <div id="dashboard" class="page-content active">
            <div class="page-header">
                <h2>Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin'); ?>!</p>
            </div>
            
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div class="stat-info">
                        <h3 id="statTotalEvents">-</h3>
                        <p>Total Events</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                    <div class="stat-info">
                        <h3 id="statFeaturedEvents">-</h3>
                        <p>Featured Events</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-images"></i></div>
                    <div class="stat-info">
                        <h3 id="statTotalGallery">-</h3>
                        <p>Gallery Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 id="statActiveGallery">-</h3>
                        <p>Active Gallery</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- EVENTS PAGE -->
        <div id="events" class="page-content">
            <div class="page-header">
                <h2>Manage Events & News</h2>
                <button class="btn btn-primary" onclick="openEventModal()">
                    <i class="bi bi-plus-circle"></i> Add Event
                </button>
            </div>
            
            <div class="filters-bar">
                <input type="text" id="eventSearch" placeholder="Search events..." oninput="loadEvents()">
                <select id="eventStatusFilter" onchange="loadEvents()">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <select id="eventCategoryFilter" onchange="loadEvents()">
                    <option value="">All Categories</option>
                </select>
                <button class="btn btn-secondary" onclick="resetEventFilters()">Reset</button>
            </div>
            
            <div class="bulk-actions">
                <select id="eventBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Move to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('events')">Apply</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllEvents" onchange="toggleSelectAll('events')"></th>
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
                    <tbody id="eventsTableBody">
                        <tr><td colspan="9" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="eventsPagination"></div>
        </div>
        
        <!-- GALLERY PAGE -->
        <div id="gallery" class="page-content">
            <div class="page-header">
                <h2>Manage Gallery</h2>
                <button class="btn btn-primary" onclick="openGalleryModal()">
                    <i class="bi bi-plus-circle"></i> Add Item
                </button>
            </div>
            
            <div class="filters-bar">
                <input type="text" id="gallerySearch" placeholder="Search gallery..." oninput="if(this.value.length >= 2 || this.value.length === 0) loadGallery()">
                <select id="galleryStatusFilter" onchange="loadGallery()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="btn btn-secondary" onclick="resetGalleryFilters()">Reset</button>
            </div>
            
            <div class="bulk-actions">
                <select id="galleryBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('gallery')">Apply</button>
            </div>
            
            <div class="gallery-admin-grid" id="galleryGrid">
                <p class="text-center">Loading...</p>
            </div>
            
            <div class="pagination" id="galleryPagination"></div>
        </div>
        
        <!-- MANAGEMENT TEAM PAGE -->
        <div id="management-team" class="page-content">
            <div class="page-header">
                <h2>Manage Management Team</h2>
                <button class="btn btn-primary" onclick="openTeamModal()">
                    <i class="bi bi-plus-circle"></i> Add Team Member
                </button>
            </div>
            
            <div class="filters-bar">
                <input type="text" id="teamSearch" placeholder="Search team members..." oninput="loadTeam()">
                <select id="teamStatusFilter" onchange="loadTeam()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="btn btn-secondary" onclick="resetTeamFilters()">Reset</button>
            </div>
            
            <div class="bulk-actions">
                <select id="teamBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('management-team')">Apply</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllTeam" onchange="toggleSelectAll('management-team')"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Telephone</th>
                            <th>Post/Role</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teamTableBody">
                        <tr><td colspan="8" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="teamPagination"></div>
        </div>
        
        <!-- ANNOUNCEMENTS PAGE -->
        <div id="announcements" class="page-content">
            <div class="page-header">
                <h2>Manage Announcements</h2>
                <button class="btn btn-primary" onclick="openAnnouncementModal()">
                    <i class="bi bi-plus-circle"></i> Add Announcement
                </button>
            </div>
            
            <div class="filters-bar">
                <input type="text" id="announcementSearch" placeholder="Search announcements..." oninput="loadAnnouncements()">
                <select id="announcementStatusFilter" onchange="loadAnnouncements()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="announcementPriorityFilter" onchange="loadAnnouncements()">
                    <option value="">All Priority</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <button class="btn btn-secondary" onclick="resetAnnouncementFilters()">Reset</button>
            </div>
            
            <div class="bulk-actions">
                <select id="announcementBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('announcements')">Apply</button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllAnnouncements" onchange="toggleSelectAll('announcements')"></th>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Content</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="announcementTableBody">
                        <tr><td colspan="8" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="announcementPagination"></div>
        </div>
        
    </main>
</div>

<!-- PROFILE MODAL -->
<div id="profileModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Edit Profile</h3>
            <button class="modal-close" onclick="closeProfileModal()">&times;</button>
        </div>
        <div class="modal-body">
            <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'update_profile'): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form id="profileForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Avatar Section -->
                <div class="profile-avatar-section">
                    <?php 
                    $profile_avatar = $_SESSION['admin_avatar'] ?? '';
                    // Check if avatar file exists
                    if (!empty($profile_avatar) && !file_exists(__DIR__ . '/' . ltrim($profile_avatar, '/'))) {
                        $profile_avatar = '';
                    }
                    ?>
                    <?php if (!empty($profile_avatar)): ?>
                        <img src="<?php echo htmlspecialchars($profile_avatar); ?>" 
                             alt="Avatar" class="profile-avatar" id="avatarPreview">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder" id="avatarPlaceholder">
                            <?php echo strtoupper(substr($_SESSION['admin_fullname'] ?? $_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="avatarInput" class="profile-upload-btn">
                            <i class="bi bi-camera"></i> Change Avatar
                        </label>
                        <input type="file" id="avatarInput" name="avatar" accept="image/*" 
                               onchange="previewAvatar(this)" style="display: none;">
                    </div>
                    <small style="color: #999; display: block; margin-top: 5px;">JPG, PNG, GIF, WebP (Max 5MB)</small>
                </div>
                
                <!-- Personal Information -->
                <div class="profile-section-title">Personal Information</div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" id="profileFullName" 
                               value="<?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group flex-1">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?>" 
                               disabled style="background: #f5f5f5;">
                        <small style="color: #999;">Username cannot be changed</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Email *</label>
                        <input type="email" name="email" id="profileEmail" 
                               value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group flex-1">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="profilePhone" 
                               value="<?php echo htmlspecialchars($_SESSION['admin_phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="profile-section-title">Change Password</div>
                <small style="color: #999; display: block; margin-bottom: 15px;">
                    Leave blank if you don't want to change password
                </small>
                
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="current_password" id="currentPassword" 
                               placeholder="Enter current password">
                        <button type="button" class="toggle-password" onclick="togglePassword('currentPassword', this)">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>New Password</label>
                        <div class="input-icon">
                            <i class="bi bi-key-fill"></i>
                            <input type="password" name="new_password" id="newPassword" 
                                   placeholder="Enter new password (min 6 characters)">
                            <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group flex-1">
                        <label>Confirm New Password</label>
                        <div class="input-icon">
                            <i class="bi bi-key-fill"></i>
                            <input type="password" name="confirm_password" id="confirmPassword" 
                                   placeholder="Confirm new password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProfileModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveProfile()">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<!-- EVENT MODAL -->
<div id="eventModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="eventModalTitle">Add Event</h3>
            <button class="modal-close" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="eventForm">
                <input type="hidden" id="eventId">
                
                <div class="form-row">
                    <div class="form-group flex-2">
                        <label>Title *</label>
                        <input type="text" id="eventTitle" required placeholder="Event title">
                    </div>
                    <div class="form-group flex-1">
                        <label>Category</label>
                        <select id="eventCategory">
                            <option value="General">General</option>
                            <option value="Academics">Academics</option>
                            <option value="Sports">Sports</option>
                            <option value="Technology">Technology</option>
                            <option value="Campus Life">Campus Life</option>
                            <option value="Service">Service</option>
                            <option value="Alumni">Alumni</option>
                            <option value="Announcement">Announcement</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Event Date *</label>
                        <input type="date" id="eventDate" required>
                    </div>
                    <div class="form-group flex-1">
                        <label>Status</label>
                        <select id="eventStatus">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Short Excerpt</label>
                    <textarea id="eventExcerpt" rows="2" placeholder="Brief summary (optional)"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Full Content *</label>
                    <textarea id="eventContent" rows="6" required placeholder="Event description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Featured Image</label>
                    <div class="image-upload-area" id="eventImageUpload">
                        <input type="file" id="eventImageInput" accept="image/*" onchange="previewImage(this, 'eventImagePreview')">
                        <div class="upload-placeholder">
                            <i class="bi bi-cloud-upload"></i>
                            <p>Click or drag to upload</p>
                            <small>JPG, PNG, GIF, WebP (Max 5MB)</small>
                        </div>
                    </div>
                    <div id="eventImagePreview" class="image-preview-area" style="display:none;">
                        <img src="" alt="Preview">
                        <button type="button" class="remove-image" onclick="removeEventImage()">&times;</button>
                    </div>
                    <input type="hidden" id="eventImageUrl">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="eventFeatured">
                        Feature this event on homepage
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEventModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveEvent()">Save Event</button>
        </div>
    </div>
</div>

<!-- GALLERY MODAL -->
<div id="galleryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="galleryModalTitle">Add Gallery Item</h3>
            <button class="modal-close" onclick="closeGalleryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="galleryForm">
                <input type="hidden" id="galleryId">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="galleryTitle" required placeholder="Item title">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="galleryDescription" rows="3" placeholder="Optional description"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Media Type</label>
                        <select id="galleryMediaType">
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div class="form-group flex-1">
                        <label>Status</label>
                        <select id="galleryStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Upload Media</label>
                    <div class="image-upload-area" id="galleryImageUpload">
                        <input type="file" id="galleryImageInput" accept="image/*,video/*" onchange="previewImage(this, 'galleryImagePreview')">
                        <div class="upload-placeholder">
                            <i class="bi bi-cloud-upload"></i>
                            <p>Click or drag to upload</p>
                            <small>Images: JPG, PNG, GIF, WebP (Max 5MB) | Videos: MP4, WebM, OGG, MOV, AVI (Max 50MB)</small>
                        </div>
                    </div>
                    <div id="galleryImagePreview" class="image-preview-area" style="display:none;">
                        <img src="" alt="Preview">
                        <button type="button" class="remove-image" onclick="removeGalleryImage()">&times;</button>
                    </div>
                    <input type="hidden" id="galleryFilePath">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeGalleryModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveGalleryItem()">Save Item</button>
        </div>
    </div>
</div>

<!-- MANAGEMENT TEAM MODAL -->
<div id="teamModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="teamModalTitle">Add Team Member</h3>
            <button class="modal-close" onclick="closeTeamModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="teamForm">
                <input type="hidden" id="teamId">
                
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" id="teamName" required placeholder="Full name">
                </div>
                
                <div class="form-group">
                    <label>Telephone *</label>
                    <input type="tel" id="teamTelephone" required placeholder="Phone number">
                </div>
                
                <div class="form-group">
                    <label>Post/Role *</label>
                    <input type="text" id="teamPost" required placeholder="Position or role">
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Status</label>
                        <select id="teamStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group flex-1">
                        <label>Sort Order</label>
                        <input type="number" id="teamSortOrder" min="0" value="0" placeholder="Lower numbers appear first">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeTeamModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveTeamMember()">Save Team Member</button>
        </div>
    </div>
</div>

<!-- ANNOUNCEMENT MODAL -->
<div id="announcementModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 id="announcementModalTitle">Add Announcement</h3>
            <button class="modal-close" onclick="closeAnnouncementModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="announcementForm">
                <input type="hidden" id="announcementId">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="announcementTitle" required placeholder="Announcement title">
                </div>
                
                <div class="form-group">
                    <label>Content *</label>
                    <textarea id="announcementContent" rows="6" required placeholder="Announcement content"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>Priority</label>
                        <select id="announcementPriority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group flex-1">
                        <label>Status</label>
                        <select id="announcementStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAnnouncementModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveAnnouncement()">Save Announcement</button>
        </div>
    </div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div id="confirmModal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage">Are you sure you want to delete this item?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div id="toast" class="toast"></div>

<script>
// Profile functions
function openProfileModal() {
    document.getElementById('profileModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.remove('open');
    document.body.style.overflow = '';
}

function saveProfile() {
    document.getElementById('profileForm').submit();
}

function previewAvatar(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarPreview = document.getElementById('avatarPreview');
            const avatarPlaceholder = document.getElementById('avatarPlaceholder');
            
            if (avatarPreview) {
                avatarPreview.src = e.target.result;
                avatarPreview.style.display = 'block';
                avatarPreview.onerror = function() {
                    this.style.display = 'none';
                    if (avatarPlaceholder) {
                        avatarPlaceholder.style.display = 'block';
                    }
                };
            } else if (avatarPlaceholder) {
                avatarPlaceholder.style.display = 'none';
                // Create image element
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Avatar Preview';
                img.id = 'avatarPreview';
                img.className = 'profile-avatar';
                img.onerror = function() {
                    this.style.display = 'none';
                    if (avatarPlaceholder) {
                        avatarPlaceholder.style.display = 'block';
                    }
                };
                avatarPlaceholder.parentNode.insertBefore(img, avatarPlaceholder.nextSibling);
            }
        };
        reader.readAsDataURL(file);
    }
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'bi bi-eye-slash-fill';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'bi bi-eye-fill';
    }
}

// Auto-open profile modal if there was an error or success
<?php if (($error || $success) && isset($_POST['action']) && $_POST['action'] === 'update_profile'): ?>
document.addEventListener('DOMContentLoaded', function() {
    openProfileModal();
});
<?php endif; ?>
</script>

<script src="app.js"></script>
</body>
</html>