<?php
// includes/display-content.php - Functions to display CMS content on main site

require_once dirname(__DIR__) . '/config/database.php';

function getEvents($limit = null, $featuredOnly = false) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT * FROM events WHERE status = 'published'";
    if ($featuredOnly) {
        $sql .= " AND is_featured = 1";
    }
    $sql .= " ORDER BY event_date DESC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getSingleEvent($slug) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT * FROM events WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getGalleryItems($limit = null, $type = null, $category = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT * FROM gallery_items WHERE status = 'active'";
    $params = [];
    if ($type) {
        $sql .= " AND media_type = ?";
        $params[] = $type;
    }
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    $sql .= " ORDER BY sort_order ASC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentEvents($limit = 5) {
    return getEvents($limit, false);
}

function getFeaturedEvents($limit = 3) {
    return getEvents($limit, true);
}

function getGalleryCategories() {
    $database = new Database();
    $db = $database->getConnection();
    return $db->query("SELECT DISTINCT category FROM gallery_items WHERE status = 'active' AND category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
}

function incrementEventViews($id) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("UPDATE events SET views = views + 1 WHERE id = ?");
    $stmt->execute([$id]);
}
?>