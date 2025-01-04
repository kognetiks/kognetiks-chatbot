<?php
// Silence is golden.

// Load WordPress Environment
$wp_load_path = dirname(__FILE__, 5) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    exit('Could not find wp-load.php');
}

// Force a 404 error
status_header(404);
nocache_headers();
include(get_404_template());
exit;
?>
