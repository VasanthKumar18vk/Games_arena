<?php
/**
 * Games Arena - Administrator Dashboard & KPI Analytics
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$user = current_user();

// KPI Stats
$totalGames = (int)($pdo->query("SELECT COUNT(*) FROM games")->fetchColumn() ?: 0);
$totalUsers = (int)($pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn() ?: 0);
$totalOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0);
$totalRevenue = (float)($pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0);

// Recent Orders
$recentOrders = $pdo->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           COUNT(oi.order_item_id) as item_count 
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll();

// Top Rated Games
$topGames = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY g.rating DESC, g.total_reviews DESC 
    LIMIT 5
")->fetchAll();

// Pending / Recent Reviews
$recentReviews = $pdo->query("
    SELECT r.*, g.game_name, u.name as reviewer_name 
    FROM reviews r
    JOIN games g ON r.game_id = g.game_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard — Games Arena';
$activeNav = 'dashboard';
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
                <i class="fa-solid fa-chart-pie text-blue-500"></i> Store Overview & Analytics
            </div>
        </div>

        <div class="header-actions">
            <button class="action-btn" id="theme-toggle-btn" title="Toggle Theme">
                <i class="fa-solid <?= $userTheme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>

            <a href="<?= BASE_URL ?>/admin/add-game.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add New Game
            </a>

            <a href="<?= BASE_URL ?>/customer/dashboard.php" class="btn btn-secondary btn-sm" title="View Customer Storefront">
                <i class="fa-solid fa-eye"></i> Store
            </a>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Administrator Dashboard</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Live metrics and management control center for Games Arena</p>
        </div>

        <!-- 1. KPI Metric Summary Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <div class="kpi-info">
                    <h3>Total Games</h3>
                    <div class="kpi-value"><?= number_format($totalGames) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon emerald">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="kpi-info">
                    <h3>Registered Users</h3>
                    <div class="kpi-value"><?= number_format($totalUsers) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon amber">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div class="kpi-info">
                    <h3>Orders Placed</h3>
                    <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon purple">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>
                <div class="kpi-info">
                    <h3>Total Revenue</h3>
                    <div class="kpi-value"><?= format_price($totalRevenue) ?></div>
                </div>
            </div>
        </div>

        <!-- 2. Main Analytics & Orders Grid -->
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:32px; margin-bottom:32px;">
            
            <!-- Left: Recent Orders -->
            <div class="card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color);">
                    <h3 style="font-size:17px; font-weight:800;">Recent Orders</h3>
                    <a href="<?= BASE_URL ?>/admin/orders.php" style="font-size:13px; font-weight:700;">View All Orders <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div style="padding:32px; text-align:center; color:var(--text-muted);">No orders placed yet.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="text-align:right;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/orders.php?order_id=<?= $o['order_id'] ?>" style="font-family:monospace; font-weight:700;">
                                            #<?= e($o['order_number']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= e($o['customer_name']) ?></div>
                                        <div style="font-size:11.5px; color:var(--text-muted);"><?= e($o['customer_email']) ?></div>
                                    </td>
                                    <td style="font-family:'Outfit',sans-serif; font-weight:800; color:var(--primary);">
                                        <?= format_price($o['total_amount']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= ucfirst(e($o['order_status'])) ?></span>
                                    </td>
                                    <td style="text-align:right; font-size:12.5px; color:var(--text-muted);">
                                        <?= date('M d, Y', strtotime($o['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Right: Top Rated Games -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:17px; font-weight:800; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                    Top Rated Games
                </h3>

                <div style="display:flex; flex-direction:column; gap:14px;">
                    <?php foreach ($topGames as $g): ?>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" style="width:50px; height:32px; object-fit:cover; border-radius:4px;">
                            <div style="flex:1; min-width:0;">
                                <a href="<?= BASE_URL ?>/admin/edit-game.php?id=<?= $g['game_id'] ?>" style="font-weight:700; font-size:13.5px; color:var(--text-primary); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= e($g['game_name']) ?>
                                </a>
                                <div style="font-size:11.5px; color:var(--text-muted);"><?= e($g['category_name']) ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:800; font-size:13px; color:#F59E0B;"><i class="fa-solid fa-star"></i> <?= number_format($g['rating'], 1) ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?= $g['total_reviews'] ?> rev</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- 3. Recent Reviews Moderation Stream -->
        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding:20px 24px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color);">
                <h3 style="font-size:17px; font-weight:800;">Recent Customer Reviews</h3>
                <a href="<?= BASE_URL ?>/admin/reviews.php" style="font-size:13px; font-weight:700;">Moderate All Reviews <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <?php if (empty($recentReviews)): ?>
                <div style="padding:32px; text-align:center; color:var(--text-muted);">No reviews posted yet.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Game</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Review Snippet</th>
                            <th>Date</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReviews as $r): ?>
                            <tr>
                                <td style="font-weight:700;"><?= e($r['game_name']) ?></td>
                                <td><?= e($r['reviewer_name']) ?></td>
                                <td><?= render_stars($r['rating']) ?></td>
                                <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text-secondary);">
                                    "<?= e($r['review_text']) ?>"
                                </td>
                                <td style="font-size:12.5px; color:var(--text-muted);"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                <td style="text-align:right;">
                                    <a href="<?= BASE_URL ?>/admin/reviews.php" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-sliders"></i> Moderate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
