<?php
header('Content-Type: text/plain');

echo "=== UPLOAD DEBUG INFORMATION ===\n\n";

// Check PHP configuration
echo "PHP Version: " . PHP_VERSION . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n\n";

// Function to convert PHP ini values to bytes
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

$upload_max = toBytes(ini_get('upload_max_filesize'));
$post_max = toBytes(ini_get('post_max_size'));
$recommended = 5 * 1024 * 1024; // 5MB

echo "UPLOAD LIMITS (in bytes):\n";
echo "upload_max_filesize: " . number_format($upload_max) . " bytes\n";
echo "post_max_size: " . number_format($post_max) . " bytes\n";
echo "Recommended (5MB): " . number_format($recommended) . " bytes\n\n";

echo "UPLOAD DIRECTORY CHECKS:\n";
$dirs = ['uploads/', 'uploads/avatars/', 'uploads/events/', 'uploads/gallery/'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = file_exists($path);
    $writable = $exists && is_writable($path);
    echo sprintf("%-25s: %s (Writable: %s)\n", $dir, $writable ? 'YES' : 'NO');
}

echo "\n=== TEST UPLOAD SIMULATION ===\n";
// Simulate a 4MB file upload
$test_size = 4 * 1024 * 1024; // 4MB
echo "Simulating 4MB upload:\n";
echo "Test file size: " . number_format($test_size) . " bytes\n";

if ($test_size > $upload_max) {
    echo "❌ EXCEEDS upload_max_filesize\n";
} else {
    echo "✅ WITHIN upload_max_filesize\n";
}

if ($test_size > $post_max) {
    echo "❌ EXCEEDS post_max_size\n";
} else {
    echo "✅ WITHIN post_max_size\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
if ($upload_max < $recommended) {
    echo "⚠️  INCREASE upload_max_filesize in php.ini\n";
    echo "   Recommended: upload_max_filesize = 10M\n";
} else {
    echo "✅ upload_max_filesize is OK\n";
}

if ($post_max < $recommended) {
    echo "⚠️  INCREASE post_max_size in php.ini\n";
    echo "   Recommended: post_max_size = 10M\n";
} else {
    echo "✅ post_max_size is OK\n";
}
?>
