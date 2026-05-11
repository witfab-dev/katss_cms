<?php
echo "<h2>PHP Upload Configuration Check</h2>";

// Check PHP upload limits
echo "<h3>Current PHP Upload Limits:</h3>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . " seconds</p>";

// Convert to bytes for comparison
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');

function toBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

$upload_max_bytes = toBytes($upload_max);
$post_max_bytes = toBytes($post_max);

echo "<h3>Converted to Bytes:</h3>";
echo "<p><strong>upload_max_filesize:</strong> " . number_format($upload_max_bytes) . " bytes</p>";
echo "<p><strong>post_max_size:</strong> " . number_format($post_max_bytes) . " bytes</p>";

// Check if limits are reasonable
$recommended_limit = 5 * 1024 * 1024; // 5MB

echo "<h3>Recommendations:</h3>";
if ($upload_max_bytes < $recommended_limit) {
    echo "<p style='color: red;'>⚠ upload_max_filesize is less than 5MB. Consider increasing in php.ini</p>";
} else {
    echo "<p style='color: green;'>✓ upload_max_filesize is sufficient for 5MB uploads</p>";
}

if ($post_max_bytes < $recommended_limit) {
    echo "<p style='color: red;'>⚠ post_max_size is less than 5MB. Consider increasing in php.ini</p>";
} else {
    echo "<p style='color: green;'>✓ post_max_size is sufficient for 5MB uploads</p>";
}

echo "<h3>Upload Directory Permissions:</h3>";
$upload_dirs = ['uploads/', 'uploads/avatars/', 'uploads/events/', 'uploads/gallery/'];

foreach ($upload_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (file_exists($full_path)) {
        if (is_writable($full_path)) {
            echo "<p style='color: green;'>✓ $dir is writable</p>";
        } else {
            echo "<p style='color: red;'>✗ $dir is NOT writable</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ $dir does not exist</p>";
    }
}
?>
