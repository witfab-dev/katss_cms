<?php
// admin/includes/header.php
?>
<header class="admin-header">
    <div class="admin-logo">
        <img src="../images/logo.jpeg" alt="KATSS Logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzAwMzM2NiIvPgo8dGV4dCB4PSIyMCIgeT0iMjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPktBU1M8L3RleHQ+Cjwvc3ZnPgo=';">
        <h1>KATSS CMS</h1>
    </div>
    
    <nav class="admin-nav">
        <div class="admin-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></div>
            </div>
        </div>
        
        <div class="admin-actions">
            <a href="../public/" target="_blank" class="btn btn-sm btn-outline" title="View Website">
                <i class="bi bi-globe"></i>
            </a>
            <a href="logout.php" class="btn btn-sm btn-danger" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>
</header>

<style>
.user-info {
    text-align: right;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
}

.user-role {
    font-size: 12px;
    opacity: 0.8;
}

.admin-actions {
    display: flex;
    gap: 8px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    color: var(--primary-color);
    font-size: 28px;
    font-weight: 600;
}

.page-header p {
    color: var(--gray-color);
    margin: 5px 0 0 0;
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    color: var(--primary-color);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.form-text {
    font-size: 12px;
    color: var(--gray-color);
    margin-top: 5px;
    display: block;
}
</style>
