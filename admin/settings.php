<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin_login();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => sanitize_input($_POST['site_name'] ?? ''),
        'site_description' => sanitize_input($_POST['site_description'] ?? ''),
        'contact_email' => sanitize_input($_POST['contact_email'] ?? ''),
        'contact_phone' => sanitize_input($_POST['contact_phone'] ?? ''),
        'address' => sanitize_input($_POST['address'] ?? ''),
        'facebook_url' => sanitize_input($_POST['facebook_url'] ?? ''),
        'twitter_url' => sanitize_input($_POST['twitter_url'] ?? ''),
        'youtube_url' => sanitize_input($_POST['youtube_url'] ?? ''),
        'instagram_url' => sanitize_input($_POST['instagram_url'] ?? ''),
        'max_upload_size' => (int)($_POST['max_upload_size'] ?? 10485760),
        'allowed_image_types' => sanitize_input($_POST['allowed_image_types'] ?? 'jpg,jpeg,png,gif,webp'),
        'allowed_video_types' => sanitize_input($_POST['allowed_video_types'] ?? 'mp4,avi,mov,wmv'),
        'items_per_page' => (int)($_POST['items_per_page'] ?? 10),
        'maintenance_mode' => sanitize_input($_POST['maintenance_mode'] ?? 'false')
    ];
    
    // Validate required fields
    $errors = [];
    if (empty($settings['site_name'])) $errors[] = "Site name is required";
    if (empty($settings['contact_email']) || !validate_email($settings['contact_email'])) {
        $errors[] = "Valid contact email is required";
    }
    
    if (empty($errors)) {
        $all_updated = true;
        foreach ($settings as $key => $value) {
            if (!update_setting($key, $value)) {
                $all_updated = false;
                break;
            }
        }
        
        if ($all_updated) {
            $success = "Settings updated successfully!";
            log_activity($_SESSION['admin_id'], 'Updated settings', 'Updated CMS settings');
        } else {
            $error = "Failed to update some settings";
        }
    } else {
        $error = implode(', ', $errors);
    }
}

// Get current settings
$current_settings = [
    'site_name' => get_setting('site_name', 'KATSS - Kirehe Adventist Technical Secondary School'),
    'site_description' => get_setting('site_description', 'Quality Technical Education for Tomorrow\'s Leaders'),
    'contact_email' => get_setting('contact_email', 'katsapapen@gmail.com'),
    'contact_phone' => get_setting('contact_phone', '+250 788416574'),
    'address' => get_setting('address', 'Kirehe District, Eastern Province, Rwanda'),
    'facebook_url' => get_setting('facebook_url', '#'),
    'twitter_url' => get_setting('twitter_url', '#'),
    'youtube_url' => get_setting('youtube_url', '#'),
    'instagram_url' => get_setting('instagram_url', '#'),
    'max_upload_size' => get_setting('max_upload_size', '10485760'),
    'allowed_image_types' => get_setting('allowed_image_types', 'jpg,jpeg,png,gif,webp'),
    'allowed_video_types' => get_setting('allowed_video_types', 'mp4,avi,mov,wmv'),
    'items_per_page' => get_setting('items_per_page', '10'),
    'maintenance_mode' => get_setting('maintenance_mode', 'false')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - KATSS CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="page-header">
            <h1>Settings</h1>
            <p>Configure your website settings and preferences</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="settingsForm">
            <div class="form-container">
                <div class="form-section">
                    <h3><i class="bi bi-globe"></i> General Settings</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Site Name <span class="required">*</span></label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Description</label>
                        <textarea name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="maintenance_mode" value="true" 
                                   <?php echo $current_settings['maintenance_mode'] === 'true' ? 'checked' : ''; ?>>
                            Enable Maintenance Mode
                        </label>
                        <small class="form-text">When enabled, visitors will see a maintenance page</small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="bi bi-envelope"></i> Contact Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Contact Email <span class="required">*</span></label>
                            <input type="email" name="contact_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['contact_email']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="contact_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['contact_phone']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($current_settings['address']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="bi bi-share"></i> Social Media</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Facebook URL</label>
                            <input type="url" name="facebook_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['facebook_url']); ?>"
                                   placeholder="https://facebook.com/yourpage">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Twitter URL</label>
                            <input type="url" name="twitter_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['twitter_url']); ?>"
                                   placeholder="https://twitter.com/yourhandle">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">YouTube URL</label>
                            <input type="url" name="youtube_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['youtube_url']); ?>"
                                   placeholder="https://youtube.com/yourchannel">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Instagram URL</label>
                            <input type="url" name="instagram_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['instagram_url']); ?>"
                                   placeholder="https://instagram.com/yourprofile">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="bi bi-upload"></i> Upload Settings</h3>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="form-label">Max Upload Size (bytes)</label>
                            <input type="number" name="max_upload_size" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['max_upload_size']); ?>"
                                   min="1048576" step="1048576">
                            <small class="form-text">Default: 10485760 (10MB)</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label">Allowed Image Types</label>
                            <input type="text" name="allowed_image_types" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['allowed_image_types']); ?>"
                                   placeholder="jpg,jpeg,png,gif,webp">
                            <small class="form-text">Comma-separated extensions</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label">Allowed Video Types</label>
                            <input type="text" name="allowed_video_types" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_settings['allowed_video_types']); ?>"
                                   placeholder="mp4,avi,mov,wmv">
                            <small class="form-text">Comma-separated extensions</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="bi bi-gear"></i> Admin Settings</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Items Per Page</label>
                        <input type="number" name="items_per_page" class="form-control" 
                               value="<?php echo htmlspecialchars($current_settings['items_per_page']); ?>"
                               min="5" max="100" step="5">
                        <small class="form-text">Number of items to display in admin lists</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Settings
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </main>
    
    <script>
        function resetForm() {
            if (confirm('Are you sure you want to reset all fields to their current saved values?')) {
                location.reload();
            }
        }
        
        // Validate URL fields
        document.querySelectorAll('input[type="url"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !this.value.startsWith('http')) {
                    this.value = 'https://' + this.value;
                }
            });
        });
        
        // Format file size display
        document.querySelector('input[name="max_upload_size"]').addEventListener('input', function() {
            const bytes = parseInt(this.value);
            if (!isNaN(bytes)) {
                const mb = (bytes / 1048576).toFixed(1);
                this.title = mb + ' MB';
            }
        });
    </script>
</body>
</html>
