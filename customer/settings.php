<?php
/**
 * Games Arena - Customer Settings (Theme & Language)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();

// Handle AJAX Theme Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_theme') {
    $theme = sanitize($_GET['theme'] ?? 'light');
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['user_theme'] = $theme;
        if (is_logged_in()) {
            $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE user_id = ?");
            $stmt->execute([$theme, current_user_id()]);
        }
    }
    exit;
}

require_login('login.php');

$userId = current_user_id();
$user = current_user();
$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } else {
        $theme = sanitize($_POST['theme'] ?? 'light');
        $language = sanitize($_POST['language'] ?? 'English');

        if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
        if (!in_array($language, ['English', 'Tamil', 'Hindi'])) $language = 'English';

        $stmt = $pdo->prepare("UPDATE users SET theme = ?, language = ? WHERE user_id = ?");
        $stmt->execute([$theme, $language, $userId]);

        $_SESSION['user_theme'] = $theme;
        $_SESSION['user_language'] = $language;

        $message = 'Your preferences have been saved successfully!';
        $messageType = 'success';
        $user = current_user();
    }
}

$pageTitle = 'Settings — Games Arena';
$activeNav = 'settings';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';
?>

<main class="app-main">
    <header class="app-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <button class="action-btn menu-toggle-btn" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <form action="<?= BASE_URL ?>/customer/games.php" method="GET" class="header-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search games by title, genre, publisher..." autocomplete="off">
            </form>
        </div>

        <div class="header-actions">
            <button class="action-btn" id="theme-toggle-btn" title="Toggle Theme">
                <i class="fa-solid <?= $userTheme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>

            <a href="<?= BASE_URL ?>/customer/favorites.php" class="action-btn" title="Favorites">
                <i class="fa-regular fa-heart"></i>
                <?php if ($favCount > 0): ?>
                    <span class="badge-count" id="favorites-badge-count"><?= $favCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/customer/cart.php" class="action-btn" title="Shopping Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="badge-count" id="cart-badge-count"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/customer/profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
                <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="Avatar" class="user-avatar" style="width:38px; height:38px;">
            </a>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:32px;">
            <h1 style="font-size:28px; font-weight:800; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid fa-gear text-blue-500"></i> <?= __('settings') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Customize your theme, language, and interface preferences</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:28px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:720px; padding:36px;">
            <form method="POST" action="settings.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_settings">

                <!-- Theme Selection -->
                <div style="margin-bottom:32px;">
                    <h3 style="font-size:17px; font-weight:800; margin-bottom:6px; display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-palette text-blue-500"></i> Interface Theme
                    </h3>
                    <p style="color:var(--text-muted); font-size:13.5px; margin-bottom:16px;">Choose between the vibrant White + Blue theme or Sleek Dark Navy theme</p>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <label class="card" style="display:flex; align-items:center; gap:16px; padding:18px; cursor:pointer; border-width:2px; <?= ($user['theme'] ?? 'light') === 'light' ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                            <input type="radio" name="theme" value="light" <?= ($user['theme'] ?? 'light') === 'light' ? 'checked' : '' ?> style="accent-color:var(--primary); transform:scale(1.2);">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:15px;">
                                    <i class="fa-solid fa-sun text-amber-500"></i> Light Mode
                                </div>
                                <span style="font-size:12.5px; color:var(--text-muted);">White + Royal Blue Brand Styling</span>
                            </div>
                        </label>

                        <label class="card" style="display:flex; align-items:center; gap:16px; padding:18px; cursor:pointer; border-width:2px; <?= ($user['theme'] ?? 'light') === 'dark' ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                            <input type="radio" name="theme" value="dark" <?= ($user['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?> style="accent-color:var(--primary); transform:scale(1.2);">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:15px;">
                                    <i class="fa-solid fa-moon text-indigo-400"></i> Dark Mode
                                </div>
                                <span style="font-size:12.5px; color:var(--text-muted);">Dark Slate + Electric Blue Gaming Theme</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Language Selection -->
                <div style="margin-bottom:32px; padding-top:24px; border-top:1px solid var(--border-color);">
                    <h3 style="font-size:17px; font-weight:800; margin-bottom:6px; display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-language text-blue-500"></i> Language Preference
                    </h3>
                    <p style="color:var(--text-muted); font-size:13.5px; margin-bottom:16px;">Select your preferred language for store navigation and interface controls</p>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                        <label class="card" style="padding:16px; text-align:center; cursor:pointer; border-width:2px; <?= ($user['language'] ?? 'English') === 'English' ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                            <input type="radio" name="language" value="English" <?= ($user['language'] ?? 'English') === 'English' ? 'checked' : '' ?> style="accent-color:var(--primary);">
                            <div style="font-weight:700; font-size:15px; margin-top:6px;">English</div>
                            <small style="color:var(--text-muted); font-size:12px;">Default</small>
                        </label>

                        <label class="card" style="padding:16px; text-align:center; cursor:pointer; border-width:2px; <?= ($user['language'] ?? 'English') === 'Tamil' ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                            <input type="radio" name="language" value="Tamil" <?= ($user['language'] ?? 'English') === 'Tamil' ? 'checked' : '' ?> style="accent-color:var(--primary);">
                            <div style="font-weight:700; font-size:15px; margin-top:6px;">தமிழ்</div>
                            <small style="color:var(--text-muted); font-size:12px;">Tamil</small>
                        </label>

                        <label class="card" style="padding:16px; text-align:center; cursor:pointer; border-width:2px; <?= ($user['language'] ?? 'English') === 'Hindi' ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                            <input type="radio" name="language" value="Hindi" <?= ($user['language'] ?? 'English') === 'Hindi' ? 'checked' : '' ?> style="accent-color:var(--primary);">
                            <div style="font-weight:700; font-size:15px; margin-top:6px;">हिन्दी</div>
                            <small style="color:var(--text-muted); font-size:12px;">Hindi</small>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Apply Preferences
                </button>
            </form>
        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
