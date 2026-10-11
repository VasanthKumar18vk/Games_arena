<?php
/**
 * Games Arena - Admin: Order Management & Fulfillment
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$message = '';
$messageType = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    if (!validate_csrf()) {
        $message = 'Security token invalid.';
        $messageType = 'danger';
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $orderStatus = sanitize($_POST['order_status'] ?? 'completed');
        $paymentStatus = sanitize($_POST['payment_status'] ?? 'paid');

        if ($orderId > 0) {
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE order_id = ?");
            $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
            $message = 'Order #' . $orderId . ' status updated successfully!';
            $messageType = 'success';
        }
    }
}

$statusFilter = sanitize($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT o.*, u.name as customer_name, u.email as customer_email,
           COUNT(oi.order_item_id) as item_count,
           GROUP_CONCAT(g.game_name SEPARATOR ', ') as game_titles
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN games g ON oi.game_id = g.game_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// View specific order details
$viewOrderId = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$activeOrderDetails = null;
if ($viewOrderId > 0) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$viewOrderId]);
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

$pageTitle = 'Manage Orders — Games Arena Admin';
$activeNav = 'orders';
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
                <i class="fa-solid fa-cart-flatbed text-blue-500"></i> Store Orders & Invoices
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Customer Orders</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Review transactions, update order status, and inspect customer invoice items</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div style="display:flex; gap:10px; margin-bottom:24px; overflow-x:auto;">
            <a href="orders.php" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                All Orders (<?= count($orders) ?>)
            </a>
            <a href="orders.php?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-check"></i> Completed
            </a>
            <a href="orders.php?status=processing" class="btn <?= $statusFilter === 'processing' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-spinner"></i> Processing
            </a>
            <a href="orders.php?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-clock"></i> Pending
            </a>
            <a href="orders.php?status=cancelled" class="btn <?= $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fa-solid fa-ban"></i> Cancelled
            </a>
        </div>

        <!-- Orders Table -->
        <div class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Games</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted);">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--primary); font-family:monospace; font-size:14.5px;">#<?= e($o['order_number']) ?></strong>
                                    <div style="font-size:11.5px; color:var(--text-muted);"><?= date('M d, Y', strtotime($o['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:var(--text-primary);"><?= e($o['customer_name']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= e($o['customer_email']) ?></div>
                                </td>
                                <td>
                                    <div style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= e($o['game_titles']) ?>">
                                        <?= e($o['game_titles']) ?>
                                    </div>
                                    <small style="color:var(--text-muted);"><?= $o['item_count'] ?> <?= $o['item_count'] == 1 ? 'game' : 'games' ?></small>
                                </td>
                                <td style="font-family:'Outfit',sans-serif; font-size:16px; font-weight:800; color:var(--primary);">
                                    <?= format_price($o['total_amount']) ?>
                                </td>
                                <td>
                                    <span style="font-size:13px; color:var(--text-secondary);"><i class="fa-solid fa-credit-card text-blue-500"></i> <?= e($o['payment_method']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $o['order_status'] === 'completed' ? 'badge-success' : ($o['order_status'] === 'cancelled' ? 'badge-danger' : 'badge-warning') ?>">
                                        <?= ucfirst(e($o['order_status'])) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="orders.php?order_id=<?= $o['order_id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Details & Status Updater Modal -->
        <?php if ($activeOrderDetails): ?>
            <div class="modal-overlay active">
                <div class="modal-card" style="max-width:680px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div>
                            <h3 style="font-size:22px; font-weight:800;">Order Details #<?= e($activeOrderDetails['order_number']) ?></h3>
                            <div style="color:var(--text-muted); font-size:13px;">Placed on <?= date('F d, Y h:i A', strtotime($activeOrderDetails['created_at'])) ?></div>
                        </div>
                        <a href="orders.php" class="btn btn-secondary btn-sm" style="padding:6px 12px;"><i class="fa-solid fa-xmark"></i></a>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; background:var(--bg-input); padding:16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:13.5px;">
                        <div>
                            <strong>Customer Name:</strong> <?= e($activeOrderDetails['user_name']) ?><br>
                            <strong>Email:</strong> <?= e($activeOrderDetails['user_email']) ?><br>
                            <strong>Phone:</strong> <?= e($activeOrderDetails['user_phone'] ?: 'N/A') ?>
                        </div>
                        <div>
                            <strong>Payment Method:</strong> <?= e($activeOrderDetails['payment_method']) ?><br>
                            <strong>Payment Status:</strong> <span class="badge badge-success"><?= ucfirst(e($activeOrderDetails['payment_status'])) ?></span><br>
                            <strong>Order Status:</strong> <span class="badge badge-primary"><?= ucfirst(e($activeOrderDetails['order_status'])) ?></span>
                        </div>
                    </div>

                    <h4 style="font-size:15px; font-weight:700; margin-bottom:12px;">Line Items</h4>
                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
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

                    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 0; border-top:1px dashed var(--border-color); margin-bottom:24px;">
                        <span style="font-weight:800; font-size:16px;">Total Amount:</span>
                        <span style="font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--primary);">
                            <?= format_price($activeOrderDetails['total_amount']) ?>
                        </span>
                    </div>

                    <!-- Update Status Form -->
                    <form method="POST" action="orders.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= $activeOrderDetails['order_id'] ?>">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Order Fulfillment Status</label>
                                <select name="order_status" class="form-control">
                                    <option value="completed" <?= $activeOrderDetails['order_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="processing" <?= $activeOrderDetails['order_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="pending" <?= $activeOrderDetails['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="cancelled" <?= $activeOrderDetails['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-control">
                                    <option value="paid" <?= $activeOrderDetails['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="pending" <?= $activeOrderDetails['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="refunded" <?= $activeOrderDetails['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                    <option value="failed" <?= $activeOrderDetails['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fa-solid fa-floppy-disk"></i> Update Order Status
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
