<?php
/**
 * Games Arena - Admin: User Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$message = '';
$messageType = '';

// Handle Toggle Status (Active / Blocked)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'toggle_status') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'active');

        if ($targetUserId > 0 && in_array($newStatus, ['active', 'blocked'])) {
            // Cannot block self
            if ($targetUserId === current_user_id()) {
                $message = 'You cannot change the status of your own account.';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                $stmt->execute([$newStatus, $targetUserId]);
                $message = 'User status updated to ' . ucfirst($newStatus) . '.';
                $messageType = 'success';
            }
        }
    }
}

$search = sanitize($_GET['search'] ?? '');
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT u.*, 
           COUNT(DISTINCT o.order_id) as total_orders,
           COALESCE(SUM(o.total_amount), 0) as total_spend
    FROM users u 
    LEFT JOIN orders o ON u.user_id = o.user_id AND o.payment_status = 'paid'
    WHERE " . implode(' AND ', $where) . "
    GROUP BY u.user_id
    ORDER BY u.user_id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users — Games Arena Admin';
$activeNav = 'users';
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
                <i class="fa-solid fa-users text-blue-500"></i> User Account Management
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:28px;">
            <div>
                <h1 style="font-size:26px; font-weight:800;">Registered Users</h1>
                <p style="color:var(--text-muted); font-size:14.5px;">Monitor customer profiles, order metrics, and account status</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom:24px; padding:16px 20px;">
            <form method="GET" action="users.php" style="display:flex; gap:12px; align-items:center;">
                <div style="flex:1;">
                    <input type="text" name="search" value="<?= e($search) ?>" class="form-control" placeholder="Search by customer name, email, or phone...">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:42px; padding:0 20px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
                <a href="users.php" class="btn btn-secondary btn-sm" style="height:42px;" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User Profile</th>
                        <th>Role</th>
                        <th>Orders</th>
                        <th>Total Spend</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:14px;">
                                    <img src="<?= get_avatar($u['profile_image'], $u['name']) ?>" alt="Avatar" style="width:42px; height:42px; border-radius:var(--radius-full);">
                                    <div>
                                        <div style="font-weight:700; color:var(--text-primary);"><?= e($u['name']) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted);"><?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>" style="<?= $u['role'] === 'admin' ? 'font-weight:800;' : '' ?>">
                                    <?= ucfirst(e($u['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-weight:600;"><?= $u['total_orders'] ?> orders</span>
                            </td>
                            <td style="font-family:'Outfit',sans-serif; font-weight:800; color:var(--primary);">
                                <?= format_price($u['total_spend']) ?>
                            </td>
                            <td style="font-size:12.5px; color:var(--text-secondary);">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst(e($u['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($u['user_id'] != current_user_id()): ?>
                                    <form method="POST" action="users.php" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'blocked' : 'active' ?>">
                                        
                                        <?php if ($u['status'] === 'active'): ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);" onclick="return confirm('Block this customer account?');">
                                                <i class="fa-solid fa-ban"></i> Block
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--success);">
                                                <i class="fa-solid fa-check"></i> Unblock
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--text-muted); font-style:italic;">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
