-- KATSS CMS Database Schema
-- Created for Kirehe Adventist Technical Secondary School CMS

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS katss_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE katss_cms;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    content LONGTEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    featured_image VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_is_featured (is_featured)
);

-- Gallery items table
CREATE TABLE IF NOT EXISTS gallery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    file_type ENUM('image', 'video') DEFAULT 'image',
    file_size INT,
    dimensions VARCHAR(50), -- For images: widthxheight, For videos: duration
    category VARCHAR(100),
    tags VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_file_type (file_type),
    INDEX idx_category (category)
);

-- Pages table for static content
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    is_homepage BOOLEAN DEFAULT FALSE,
    template VARCHAR(100) DEFAULT 'default',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug)
);

-- Settings table for CMS configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'textarea', 'number', 'boolean', 'file') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@katss.edu.rw', 'super_admin');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'KATSS - Kirehe Adventist Technical Secondary School', 'text', 'Site name'),
('site_description', 'Quality Technical Education for Tomorrow\\'s Leaders', 'text', 'Site description'),
('contact_email', 'katsapapen@gmail.com', 'text', 'Contact email'),
('contact_phone', '+250 788416574', 'text', 'Contact phone'),
('address', 'Kirehe District, Eastern Province, Rwanda', 'textarea', 'School address'),
('facebook_url', '#', 'text', 'Facebook URL'),
('twitter_url', '#', 'text', 'Twitter URL'),
('youtube_url', '#', 'text', 'YouTube URL'),
('instagram_url', '#', 'text', 'Instagram URL'),
('max_upload_size', '10485760', 'number', 'Maximum upload size in bytes (default: 10MB)'),
('allowed_image_types', 'jpg,jpeg,png,gif,webp', 'text', 'Allowed image file types'),
('allowed_video_types', 'mp4,avi,mov,wmv', 'text', 'Allowed video file types'),
('items_per_page', '10', 'number', 'Number of items per page in admin lists'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode');
