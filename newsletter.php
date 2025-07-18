<?php
// UrbanStitch E-commerce - Newsletter Subscription Handler
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // In a real application, you would save this to a database
        // For now, we'll just simulate success
        $_SESSION['newsletter_message'] = 'Successfully subscribed to newsletter!';
        $_SESSION['newsletter_type'] = 'success';
    } else {
        $_SESSION['newsletter_message'] = 'Please enter a valid email address.';
        $_SESSION['newsletter_type'] = 'error';
    }
}

// Redirect back to the referring page or home
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referer);
exit;
?>