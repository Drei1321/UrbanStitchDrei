<?php
// check_gd.php
echo "<h2>GD Extension Check</h2>";

if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✅ GD extension is loaded and ready!</p>";
    
    // Show GD info
    $gdInfo = gd_info();
    echo "<h3>GD Information:</h3>";
    echo "<ul>";
    foreach ($gdInfo as $key => $value) {
        echo "<li><strong>$key:</strong> " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ GD extension is NOT loaded</p>";
    echo "<p>You need to install/enable the GD extension for image processing to work.</p>";
}

echo "<h3>All Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";
?>