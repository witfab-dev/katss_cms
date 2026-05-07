<?php
// admin/create_upload_dirs.php
$dirs = [
    'uploads/events',
    'uploads/gallery',
    'uploads/gallery/thumbnails'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created: $dir<br>";
    }
}
echo "Upload directories ready!";
?>