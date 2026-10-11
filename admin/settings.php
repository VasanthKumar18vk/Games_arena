<?php
/**
 * Games Arena - Admin Settings & System Configuration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$userId = current_user_id();
$user = current_user();

$message = '';
$messageType = '';

// Handle Admin Account Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'update_admin_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currHash = $stmt->fetch()['password'] ?? '';

        if (!empty($newPass)) {
            if (!password_verify($currentPass, $currHash)) {
                $message = 'Current administrator password incorrect.';
                $messageType = 'danger';
            } elseif (strlen($newPass) < 6) {
                $message = 'New password must be at least 6 characters.';
                $messageType = 'danger';
            } elseif ($newPass !== $confirmPass) {
                $message = 'New passwords do not match.';
                $messageType = 'danger';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE user_id = ?");
                $stmt->execute([$name, $newHash, $userId]);
                $_SESSION['user_name'] = $name;
                $message = 'Administrator profile and password updated successfully!';
                $messageType = 'success';
                $user = current_user();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $stmt->execute([$name, $userId]);
            $_SESSION['user_name'] = $name;
            $message = 'Administrator name updated!';
            $messageType = 'success';
            $user = current_user();
        }
    }
}

$pageTitle = 'Admin Settings — Games Arena';
$activeNav = 'settings';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-sidebar.php';
?>

<main class="app-main">
    <header class="app-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <button class="action-btn menu-toggle-btn" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div style="font-weight:800; font-size:18px;">
                <i class="fa-solid fa-sliders text-blue-500"></i> Store & Admin Configuration
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Settings & Server Diagnostics</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Store configuration, admin profile, and XAMPP MySQL environment status</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:flex-start;">
            
            <!-- Left: Admin Profile -->
            <div class="card" style="padding:32px;">
                <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-user-shield text-blue-500"></i> Admin Credentials
                </h3>

                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_admin_profile">

                    <div class="form-group">
                        <label class="form-label">Administrator Name</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Admin Email (Read-Only)</label>
                        <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly style="background:var(--border-color);">
                    </div>

                    <div style="margin:20px 0; border-top:1px dashed var(--border-color); padding-top:16px;">
                        <span style="font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Change Admin Password (Optional)</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Required only if changing password">
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                        <i class="fa-solid fa-floppy-disk"></i> Update Settings
                    </button>
                </form>
            </div>

            <!-- Right: System & Environment Information -->
            <div>
                <div class="card" style="padding:32px; margin-bottom:24px;">
                    <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-server text-blue-500"></i> Server & Database Environment
                    </h3>

                    <div style="display:flex; flex-direction:column; gap:12px; font-size:14px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">PHP Version:</span>
                            <strong><?= PHP_VERSION ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Database Server:</span>
                            <strong>MySQL / MariaDB (Port <?= DB_PORT ?>)</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Active Database:</span>
                            <strong style="color:var(--primary); font-family:monospace;"><?= DB_NAME ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Default Currency:</span>
                            <strong><?= APP_CURRENCY ?> (<?= APP_CURRENCY_CODE ?>)</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Local Server:</span>
                            <strong>XAMPP (Apache + MySQL)</strong>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding:28px;">
                    <h3 style="font-size:16px; font-weight:700; margin-bottom:12px;">Database Re-Initialization</h3>
                    <p style="color:var(--text-muted); font-size:13.5px; margin-bottom:16px;">
                        Need to re-seed demo data or reset the MySQL schema? You can run the database installer at any time.
                    </p>
                    <a href="<?= BASE_URL ?>/install.php" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-rotate-right"></i> Open Database Installer
                    </a>
                </div>
            </div>

        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
