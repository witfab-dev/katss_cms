# KATSS CMS

Kirehe Adventist Technical Secondary School Content Management System

## Overview

KATSS CMS is a comprehensive content management system designed for Kirehe Adventist Technical Secondary School. This system provides an easy-to-use interface for managing school content including pages, events, gallery items, and user accounts.

## Features

### Core Features
- **Pages Management**: Create, edit, and manage static pages with SEO support
- **Events Management**: Schedule and manage school events with date/time tracking
- **Gallery Management**: Upload and organize images and videos
- **User Management**: Role-based user authentication and permissions
- **Settings Management**: Configure site-wide settings and preferences

### Admin Features
- **Dashboard**: Overview of site statistics and recent activity
- **Authentication**: Secure login system with session management
- **Activity Logging**: Track all admin actions for security
- **File Upload**: Secure file handling with validation
- **Responsive Design**: Mobile-friendly admin interface

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- GD Library (for image processing)

### Setup Instructions

1. **Clone or download the project to your web server**
   ```bash
   cd /xampp/htdocs/katss
   ```

2. **Create the database**
   - Import the SQL schema from `database/schema.sql`
   - Or run the SQL file directly in your MySQL client

3. **Configure database connection**
   - Edit `config/database.php` with your database credentials
   - Default database name: `katss_cms`
   - Default username: `root`
   - Default password: empty

4. **Set file permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 images/
   ```

5. **Access the admin panel**
   - Navigate to `http://localhost/katss/admin/login.php`
   - Default login: `admin` / `admin123`

## Directory Structure

```
katss/
├── admin/                  # Admin panel files
│   ├── dashboard.php       # Admin dashboard
│   ├── events.php         # Events management
│   ├── gallery.php        # Gallery management
│   ├── pages.php          # Pages management
│   ├── users.php          # User management
│   ├── settings.php       # Site settings
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── admin-style.css    # Admin panel styles
│   └── includes/          # Admin includes
│       ├── header.php     # Admin header
│       └── sidebar.php    # Admin sidebar
├── config/                # Configuration files
│   └── database.php       # Database connection
├── database/              # Database files
│   └── schema.sql         # Database schema
├── includes/              # Core functions
│   ├── functions.php      # Common functions
│   └── display-content.php # Content display
├── public/                # Public website files
│   ├── index.php          # Main website
│   ├── style.css          # Website styles
│   └── script.js          # Website scripts
├── src/                   # Source files
│   ├── style.css          # Source styles
│   └── script.js          # Source scripts
├── uploads/               # User uploads
├── images/                # Website images
└── README.md             # This file
```

## Database Schema

The CMS uses the following main tables:

- **admin_users**: Administrator accounts and roles
- **pages**: Static pages with SEO metadata
- **events**: School events and announcements
- **gallery_items**: Media files (images/videos)
- **settings**: Site configuration
- **activity_log**: Admin activity tracking

## User Roles

### Super Admin
- Full access to all features
- Can manage other users
- Can modify system settings

### Admin
- Can manage content (pages, events, gallery)
- Cannot manage other users
- Limited settings access

### Editor
- Can create and edit content
- Cannot delete content
- Cannot access settings

## Security Features

- **Password Hashing**: Uses PHP's password_hash() with bcrypt
- **SQL Injection Prevention**: Prepared statements for all queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based request validation
- **Session Management**: Secure session handling

## File Upload Security

- **File Type Validation**: Only allowed file types accepted
- **Size Limits**: Configurable upload size restrictions
- **Secure Storage**: Files stored outside web root when possible
- **Image Processing**: Automatic thumbnail generation

## Customization

### Adding New Features
1. Create database migrations in `database/`
2. Add admin pages in `admin/`
3. Update navigation in `admin/includes/sidebar.php`
4. Add functions in `includes/functions.php`

### Styling
- Admin styles: `admin/admin-style.css`
- Frontend styles: `public/style.css`
- Uses Bootstrap Icons for consistency

### Configuration
- Database settings: `config/database.php`
- Site settings: Admin panel > Settings
- Upload settings: Admin panel > Settings

## Maintenance

### Regular Tasks
- Clear old files from uploads directory
- Review activity logs
- Update passwords regularly
- Backup database

### Backup Commands
```bash
# Database backup
mysqldump -u root -p katss_cms > backup.sql

# Files backup
tar -czf files_backup.tar.gz uploads/ images/
```

## Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `config/database.php`
- Ensure MySQL server is running
- Verify database exists

**File Upload Issues**
- Check directory permissions
- Verify PHP upload limits
- Ensure GD library is installed

**Login Problems**
- Clear browser cookies
- Check session configuration
- Verify user account is active

## Support

For technical support or questions:
- Email: katsapapen@gmail.com
- Phone: +250 788416574

## License

This project is proprietary software for Kirehe Adventist Technical Secondary School.

## Version History

### v1.0.0 (Current)
- Initial release with core CMS functionality
- Admin panel with user management
- Page, event, and gallery management
- Settings and configuration system
- Security features and logging 
