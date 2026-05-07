<?php
// admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <nav class="sidebar-menu">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="pages.php" class="<?php echo $current_page === 'pages.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Pages</span>
                </a>
            </li>
            <li>
                <a href="events.php" class="<?php echo $current_page === 'events.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event"></i>
                    <span>Events</span>
                </a>
            </li>
            <li>
                <a href="gallery.php" class="<?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>">
                    <i class="bi bi-images"></i>
                    <span>Gallery</span>
                </a>
            </li>
            <li>
                <a href="#" class="dropdown-toggle">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                            <i class="bi bi-person"></i>
                            <span>All Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                            <i class="bi bi-person-gear"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#" class="dropdown-toggle">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="settings.php" class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                            <i class="bi bi-sliders"></i>
                            <span>General Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="appearance.php" class="<?php echo $current_page === 'appearance.php' ? 'active' : ''; ?>">
                            <i class="bi bi-palette"></i>
                            <span>Appearance</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="#" class="dropdown-toggle">
                    <i class="bi bi-bar-chart"></i>
                    <span>Reports</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="analytics.php" class="<?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>">
                            <i class="bi bi-graph-up"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="activity-log.php" class="<?php echo $current_page === 'activity-log.php' ? 'active' : ''; ?>">
                            <i class="bi bi-clock-history"></i>
                            <span>Activity Log</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="backup.php" class="<?php echo $current_page === 'backup.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cloud-download"></i>
                    <span>Backup</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<style>
.dropdown-toggle {
    cursor: pointer;
}

.submenu {
    display: none;
    padding-left: 20px;
    margin-top: 5px;
}

.submenu li {
    margin-bottom: 3px;
}

.submenu a {
    padding: 8px 15px;
    font-size: 13px;
    color: var(--gray-color);
}

.submenu a:hover,
.submenu a.active {
    color: var(--primary-color);
    background: rgba(0,51,102,0.05);
}

.dropdown-toggle.active + .submenu {
    display: block;
}

.ms-auto {
    margin-left: auto;
}

/* Mobile menu toggle */
@media (max-width: 768px) {
    .admin-sidebar {
        position: fixed;
        left: -250px;
        transition: left 0.3s ease;
        z-index: 1001;
    }
    
    .admin-sidebar.active {
        left: 0;
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}
</style>

<script>
// Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Close other dropdowns
            dropdownToggles.forEach(otherToggle => {
                if (otherToggle !== toggle) {
                    otherToggle.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            this.classList.toggle('active');
        });
    });
    
    // Set active dropdown based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const activeSubmenuLink = document.querySelector(`.submenu a[href="${currentPage}"]`);
    
    if (activeSubmenuLink) {
        activeSubmenuLink.classList.add('active');
        const parentDropdown = activeSubmenuLink.closest('.submenu').previousElementSibling;
        if (parentDropdown) {
            parentDropdown.classList.add('active');
        }
    }
});

// Mobile menu functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(e.target) && 
        !e.target.closest('.menu-toggle')) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }
});
</script>
