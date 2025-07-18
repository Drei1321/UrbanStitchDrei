<?php
/**
 * Simple Upload Directory Setup Script
 * Save this as "setup_uploads.php" in your project root directory
 * Run it once by visiting: http://yourdomain.com/setup_uploads.php
 */

// Define upload directories needed
$uploadDirs = [
    'uploads/',
    'uploads/avatars/',
    'uploads/products/',
    'uploads/temp/'
];

$results = [];
$allGood = true;

// Create directories
foreach ($uploadDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0777, true)) {
            chmod($fullPath, 0777);
            $results[$dir] = ['status' => 'created', 'message' => 'Created successfully'];
        } else {
            $results[$dir] = ['status' => 'error', 'message' => 'Failed to create'];
            $allGood = false;
        }
    } else {
        if (is_writable($fullPath)) {
            $results[$dir] = ['status' => 'exists', 'message' => 'Already exists and writable'];
        } else {
            chmod($fullPath, 0777);
            $results[$dir] = ['status' => 'fixed', 'message' => 'Permissions updated to 777'];
        }
    }
}

// Create security files
foreach ($uploadDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    
    // Create index.php to prevent directory listing
    $indexFile = $fullPath . 'index.php';
    if (!file_exists($indexFile)) {
        file_put_contents($indexFile, "<?php header('HTTP/1.0 403 Forbidden'); exit; ?>");
    }
    
    // Create .htaccess for avatars (allow images)
    if ($dir === 'uploads/avatars/') {
        $htaccessContent = "# Allow image access
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Deny access to other files
<FilesMatch \"\\.(php|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>";
        
        file_put_contents($fullPath . '.htaccess', $htaccessContent);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Setup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-created, .status-exists, .status-fixed { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .next-steps { background: #f0f8ff; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .important { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <h1>🚀 Upload Directory Setup</h1>
    
    <?php if ($allGood): ?>
        <div class="success">
            <h2>✅ Setup Complete!</h2>
            <p>All upload directories have been created successfully.</p>
        </div>
    <?php else: ?>
        <div class="error">
            <h2>⚠️ Setup Issues Detected</h2>
            <p>Some directories could not be created. Check the details below.</p>
        </div>
    <?php endif; ?>
    
    <h3>Directory Status:</h3>
    <table>
        <tr>
            <th>Directory</th>
            <th>Status</th>
            <th>Full Path</th>
            <th>Writable</th>
        </tr>
        <?php foreach ($uploadDirs as $dir): ?>
        <tr>
            <td><?php echo htmlspecialchars($dir); ?></td>
            <td class="status-<?php echo $results[$dir]['status']; ?>">
                <?php echo ucfirst($results[$dir]['status']); ?>
            </td>
            <td style="font-size: 12px; font-family: monospace;">
                <?php echo htmlspecialchars(__DIR__ . '/' . $dir); ?>
            </td>
            <td class="<?php echo is_writable(__DIR__ . '/' . $dir) ? 'success' : 'error'; ?>">
                <?php echo is_writable(__DIR__ . '/' . $dir) ? '✅ Yes' : '❌ No'; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <div class="next-steps">
        <h3>📋 Next Steps:</h3>
        <ol>
            <li><strong>Test the upload:</strong> Go to your profile page and try uploading a profile picture</li>
            <li><strong>Check your updated profile.php:</strong> Make sure you're using the updated PHP code I provided</li>
            <li><strong>Monitor the logs:</strong> Check your server error logs if uploads still fail</li>
            <li><strong>Delete this file:</strong> Remove <code>setup_uploads.php</code> after setup is complete</li>
        </ol>
    </div>
    
    <div class="important">
        <h3>🔧 If uploads still don't work:</h3>
        <ul>
            <li>Check your server's error logs</li>
            <li>Verify your web server user has write permissions</li>
            <li>Make sure PHP file uploads are enabled</li>
            <li>Contact your hosting provider if you're on shared hosting</li>
        </ul>
    </div>
    
    <h3>🔍 Server Info:</h3>
    <ul>
        <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
        <li><strong>Upload Max Filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
        <li><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></li>
        <li><strong>File Uploads Enabled:</strong> <?php echo ini_get('file_uploads') ? 'Yes' : 'No'; ?></li>
        <li><strong>Current Directory:</strong> <code><?php echo __DIR__; ?></code></li>
    </ul>
    
    <?php if ($allGood): ?>
    <div class="success">
        <h3>🎉 Ready to Go!</h3>
        <p>Your upload system is now configured. You can:</p>
        <ul>
            <li><a href="profile.php">Test profile picture upload</a></li>
            <li><a href="index.php">Go back to main site</a></li>
        </ul>
    </div>
    <?php endif; ?>
    
    <hr>
    <p style="text-align: center; color: #666; font-size: 12px;">
        Setup completed at <?php echo date('Y-m-d H:i:s'); ?> | 
        <strong>Remember to delete this setup file after use!</strong>
    </p>
</body>
</html>