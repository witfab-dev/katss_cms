<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Please login first";
    exit();
}

// Simulate the API call for management team
$_GET['action'] = 'list';
$_GET['type'] = 'management_team';

// Include the API file
require_once 'api.php';
?>
