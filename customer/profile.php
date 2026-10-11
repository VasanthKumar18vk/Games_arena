<?php
/**
 * Games Arena - Customer Profile Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login('login.php');

$pdo = get_db();
$userId = current_user_id();
$user = current_user();

$message = '';
$messageType = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security token invalid.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        // Avatar Image Upload
        $profileImagePath = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed) && $file['size'] <= 3 * 1024 * 1024) {
                $targetDir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = $targetDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $profileImagePath = 'uploads/profiles/' . $fileName;
                }
            }
        }

        if (!empty($name)) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, profile_image = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $profileImagePath, $userId]);
            $_SESSION['user_name'] = $name;
            $user = current_user();
            $message = 'Your profile details have been updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Name cannot be empty.';
            $messageType = 'danger';
        }

    } elseif ($_POST['action'] === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currHash = $stmt->fetch()['password'] ?? '';

        if (!password_verify($currentPass, $currHash)) {
            $message = 'The current password you entered is incorrect.';
            $messageType = 'danger';
        } elseif (strlen($newPass) < 6) {
            $message = 'New password must be at least 6 characters in length.';
            $messageType = 'danger';
        } elseif ($newPass !== $confirmPass) {
            $message = 'New passwords do not match.';
            $messageType = 'danger';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$newHash, $userId]);
            $message = 'Your password has been changed successfully!';
            $messageType = 'success';
        }
    }
}

$pageTitle = 'Edit Profile — Games Arena';
$activeNav = 'profile';
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
                <i class="fa-solid fa-user-pen text-blue-500"></i> <?= __('profile') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Manage your account details and password security</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:28px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:flex-start;">
            
            <!-- Left: Personal Information & Avatar -->
            <div class="card" style="padding:32px;">
                <h3 style="font-size:18px; font-weight:800; margin-bottom:24px; padding-bottom:12px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-address-card text-blue-500"></i> Personal Information
                </h3>

                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div style="display:flex; align-items:center; gap:20px; margin-bottom:24px;">
                        <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="Profile Preview" style="width:74px; height:74px; border-radius:var(--radius-full); object-fit:cover; border:3px solid var(--primary-light);">
                        <div>
                            <label class="form-label">Profile Avatar</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*" style="font-size:13px; padding:8px 12px;">
                            <small style="color:var(--text-muted); font-size:11.5px;">JPG, PNG, or WebP. Max 3MB.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address (Read-Only)</label>
                        <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly style="background:var(--border-color); cursor:not-allowed;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="+91 9876543210">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- Right: Change Password -->
            <div class="card" style="padding:32px;">
                <h3 style="font-size:18px; font-weight:800; margin-bottom:24px; padding-bottom:12px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-key text-blue-500"></i> Change Password
                </h3>

                <form method="POST" action="profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password *</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="margin-top:8px;">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </form>
            </div>

        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
