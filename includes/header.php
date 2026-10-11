<?php
/**
 * Games Arena - Global Page Header
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pdo = get_db();
$user = current_user();
$userId = current_user_id();

$cartCount = $pdo ? get_cart_count($pdo, $userId) : 0;
$favCount = $pdo ? get_favorites_count($pdo, $userId) : 0;

$pageTitle = $pageTitle ?? APP_NAME . ' — Premium Game Store';
$activeNav = $activeNav ?? 'home';
$userTheme = $_SESSION['user_theme'] ?? ($user['theme'] ?? 'light');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($userTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <script>
        window.BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>

<div class="app-layout">
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebar-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:95;display:none;"></div>
