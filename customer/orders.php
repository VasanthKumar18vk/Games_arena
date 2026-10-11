<?php
/**
 * Games Arena - Customer Order History & Invoices
 */
$pageTitle = 'My Orders — Games Arena';
$activeNav = 'orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

require_login('login.php');

$pdo = get_db();
$userId = current_user_id();

// Fetch customer's orders with item summaries
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.order_item_id) as total_items,
           GROUP_CONCAT(g.game_name SEPARATOR ', ') as game_titles
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN games g ON oi.game_id = g.game_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// If viewing a specific order modal/details
$viewOrderId = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$activeOrderDetails = null;
if ($viewOrderId > 0) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$viewOrderId, $userId]);
    $activeOrderDetails = $stmt->fetch();

    if ($activeOrderDetails) {
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, g.game_name, g.game_image, c.category_name
            FROM order_items oi
            JOIN games g ON oi.game_id = g.game_id
            JOIN categories c ON g.category_id = c.category_id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$viewOrderId]);
        $activeOrderDetails['items'] = $itemsStmt->fetchAll();
    }
}
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
                <i class="fa-solid fa-box-archive text-blue-500"></i> <?= __('orders') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Track your past purchases, licenses, and download invoices</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:48px; color:var(--text-muted); margin-bottom:16px;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">No orders found</h2>
                <p style="color:var(--text-muted); max-width:420px; margin:0 auto 20px;">
                    You haven't placed any game orders yet. Browse our catalog and start building your game collection!
                </p>
                <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-primary">
                    <i class="fa-solid fa-gamepad"></i> Browse Store
                </a>
            </div>
        <?php else: ?>
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Games Included</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th style="text-align:right;">Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--primary); font-family:monospace; font-size:14.5px;">#<?= e($o['order_number']) ?></strong>
                                </td>
                                <td style="color:var(--text-secondary);">
                                    <?= date('M d, Y', strtotime($o['created_at'])) ?>
                                </td>
                                <td>
                                    <div style="max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= e($o['game_titles']) ?>">
                                        <?= e($o['game_titles'] ?: 'Games') ?>
                                    </div>
                                    <small style="color:var(--text-muted);"><?= $o['total_items'] ?> <?= $o['total_items'] == 1 ? 'game license' : 'game licenses' ?></small>
                                </td>
                                <td style="font-family:'Outfit',sans-serif; font-size:16px; font-weight:800; color:var(--text-primary);">
                                    <?= format_price($o['total_amount']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Paid</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= ucfirst(e($o['order_status'])) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="orders.php?order_id=<?= $o['order_id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-file-invoice"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Printable / Detailed Order Invoice Modal -->
        <?php if ($activeOrderDetails): ?>
            <div class="modal-overlay active">
                <div class="modal-card" style="max-width:620px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:40px; height:40px; background:var(--primary-light); color:var(--primary); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px;">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div>
                                <h3 style="font-size:20px; font-weight:800;">Invoice #<?= e($activeOrderDetails['order_number']) ?></h3>
                                <div style="color:var(--text-muted); font-size:12.5px;"><?= date('F d, Y h:i A', strtotime($activeOrderDetails['created_at'])) ?></div>
                            </div>
                        </div>
                        <a href="orders.php" class="btn btn-secondary btn-sm" style="padding:6px 12px;"><i class="fa-solid fa-xmark"></i></a>
                    </div>

                    <div style="background:var(--bg-input); padding:16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:13.5px;">
                        <div><strong>Customer:</strong> <?= e($activeOrderDetails['user_name']) ?> (<?= e($activeOrderDetails['user_email']) ?>)</div>
                        <div style="margin-top:4px;"><strong>Payment Method:</strong> <?= e($activeOrderDetails['payment_method']) ?></div>
                        <div style="margin-top:4px;"><strong>Status:</strong> <span class="badge badge-success"><?= ucfirst(e($activeOrderDetails['order_status'])) ?></span></div>
                    </div>

                    <h4 style="font-size:15px; font-weight:700; margin-bottom:12px;">Purchased Licenses</h4>
                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:24px;">
                        <?php foreach ($activeOrderDetails['items'] as $it): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-sm);">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?= get_game_cover($it['game_image'], $it['game_name']) ?>" style="width:50px; height:32px; object-fit:cover; border-radius:4px;">
                                    <div>
                                        <div style="font-weight:700; font-size:14px;"><?= e($it['game_name']) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted);"><?= e($it['category_name']) ?></div>
                                    </div>
                                </div>
                                <span style="font-weight:800; font-family:'Outfit',sans-serif;"><?= format_price($it['price']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px dashed var(--border-color); margin-bottom:24px;">
                        <span style="font-weight:800; font-size:16px;">Total Amount Paid:</span>
                        <span style="font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--primary);">
                            <?= format_price($activeOrderDetails['total_amount']) ?>
                        </span>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <button onclick="window.print()" class="btn btn-secondary" style="flex:1;">
                            <i class="fa-solid fa-print"></i> Print Receipt
                        </button>
                        <a href="<?= BASE_URL ?>/customer/purchased.php" class="btn btn-primary" style="flex:1;">
                            <i class="fa-solid fa-dice-d20"></i> Go to Library
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
