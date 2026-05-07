<?php
session_start();

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
</head>
<body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">

<!-- LOGIN SCREEN -->
<div id="loginScreen" class="login-screen <?php echo $isLoggedIn ? 'hidden' : ''; ?>">
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>KATSS ADMIN PANEL</h1>
            <p>Admin Panel Login</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($error); ?>
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
            <a href="register.php">Create Account</a>
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
        <h1><img src="../images/logo.jpeg" alt="KATSS" style="width: 40px; height: 40px; margin-right: 10px;"> KATSS ADMIN PANEL</h1>
        <div class="header-right">
            <span class="user-info">
                <i class="bi bi-person-circle"></i>
                <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
            </span>
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
            
            <!-- Filters -->
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
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="eventBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Move to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('events')">Apply</button>
            </div>
            
            <!-- Events Table -->
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
            
            <!-- Pagination -->
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
            
            <!-- Filters -->
            <div class="filters-bar">
                <input type="text" id="gallerySearch" placeholder="Search gallery..." oninput="loadGallery()">
                <select id="galleryStatusFilter" onchange="loadGallery()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="btn btn-secondary" onclick="resetGalleryFilters()">Reset</button>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="galleryBulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-secondary" onclick="executeBulkAction('gallery')">Apply</button>
            </div>
            
            <!-- Gallery Grid -->
            <div class="gallery-admin-grid" id="galleryGrid">
                <p class="text-center">Loading...</p>
            </div>
            
            <!-- Pagination -->
            <div class="pagination" id="galleryPagination"></div>
        </div>
        
    </main>
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
                            <small>Images: JPG, PNG, GIF, WebP | Videos: MP4 (Max 5MB)</small>
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

<script src="app.js"></script>
</body>
</html>