<?php
/**
 * Games Arena - Checkout & Payment Simulation
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login('login.php');

$pdo = get_db();
$userId = current_user_id();

// Direct single game purchase or Cart checkout
$directGameId = isset($_GET['direct_game']) && is_numeric($_GET['direct_game']) ? (int)$_GET['direct_game'] : 0;
$itemsToPurchase = [];
$totalAmount = 0.0;

if ($directGameId > 0) {
    // Single direct purchase
    if (is_game_owned($pdo, $userId, $directGameId)) {
        set_flash('info', 'You already own this game in your library!');
        header("Location: " . BASE_URL . "/customer/purchased.php");
        exit;
    }
    $stmt = $pdo->prepare("SELECT game_id, game_name, genre, price, game_image, '1' as quantity FROM games WHERE game_id = ?");
    $stmt->execute([$directGameId]);
    $g = $stmt->fetch();
    if ($g) {
        $itemsToPurchase[] = $g;
        $totalAmount = (float)$g['price'];
    }
} else {
    // Normal Cart checkout
    $cartItems = get_user_cart($pdo, $userId);
    if (empty($cartItems)) {
        header("Location: " . BASE_URL . "/customer/cart.php");
        exit;
    }
    foreach ($cartItems as $ci) {
        // Skip if somehow already owned
        if (!is_game_owned($pdo, $userId, $ci['game_id'])) {
            $itemsToPurchase[] = $ci;
            $totalAmount += (float)$ci['price'];
        }
    }
}

if (empty($itemsToPurchase)) {
    set_flash('info', 'All items selected are already owned.');
    header("Location: " . BASE_URL . "/customer/purchased.php");
    exit;
}

$orderSuccess = null;
$error = '';

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!validate_csrf()) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'Card / UPI');
        
        try {
            $pdo->beginTransaction();

            // Generate unique Order Number #GA10001
            $stmt = $pdo->query("SELECT MAX(order_id) as last_id FROM orders");
            $lastId = (int)($stmt->fetch()['last_id'] ?? 0);
            $orderNumber = 'GA' . (10000 + $lastId + 1);

            // Create Order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, order_status)
                VALUES (?, ?, ?, ?, 'paid', 'completed')
            ");
            $stmt->execute([$userId, $orderNumber, $totalAmount, $paymentMethod]);
            $orderId = $pdo->lastInsertId();

            // Insert Order Items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, game_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($itemsToPurchase as $item) {
                $itemStmt->execute([$orderId, $item['game_id'], 1, $item['price']]);
            }

            // If checked out from cart, clear cart items
            if ($directGameId === 0) {
                $delCart = $pdo->prepare("
                    DELETE ci FROM cart_items ci
                    JOIN carts c ON ci.cart_id = c.cart_id
                    WHERE c.user_id = ?
                ");
                $delCart->execute([$userId]);
            }

            $pdo->commit();

            $orderSuccess = [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'items' => $itemsToPurchase,
                'date' => date('F d, Y h:i A')
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Secure Checkout — Games Arena';
$activeNav = 'cart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';
?>

<main class="app-main">
    <header class="app-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <button class="action-btn menu-toggle-btn" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div style="font-weight:700; font-size:16px;">
                <i class="fa-solid fa-lock text-emerald-500"></i> Secure Checkout
            </div>
        </div>

        <div class="header-actions">
            <a href="<?= BASE_URL ?>/customer/profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
                <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="Avatar" class="user-avatar" style="width:38px; height:38px;">
            </a>
        </div>
    </header>

    <div class="page-body">

        <?php if ($orderSuccess): ?>
            <!-- Order Success Confirmation Screen -->
            <div class="card" style="max-width:680px; margin:0 auto; padding:40px; text-align:center;">
                <div style="width:80px; height:80px; background:#ECFDF5; color:#059669; border-radius:var(--radius-full); display:inline-flex; align-items:center; justify-content:center; font-size:40px; margin-bottom:20px; box-shadow:0 8px 20px rgba(16,185,129,0.2);">
                    <i class="fa-solid fa-check"></i>
                </div>

                <h1 style="font-size:30px; font-weight:800; margin-bottom:8px; color:var(--text-primary);">Payment Successful!</h1>
                <p style="color:var(--text-muted); font-size:15px; margin-bottom:28px;">
                    Thank you for your purchase. Your digital game licenses have been activated immediately!
                </p>

                <!-- Order Receipt Box -->
                <div style="background:var(--bg-input); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:24px; text-align:left; margin-bottom:32px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:14px;">
                        <span style="color:var(--text-muted);">Order Number:</span>
                        <strong style="color:var(--primary); font-family:monospace; font-size:16px;"><?= e($orderSuccess['order_number']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:14px;">
                        <span style="color:var(--text-muted);">Order Date:</span>
                        <span><?= e($orderSuccess['date']) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:16px; font-size:14px;">
                        <span style="color:var(--text-muted);">Payment Method:</span>
                        <span><i class="fa-solid fa-credit-card text-blue-500"></i> <?= e($orderSuccess['payment_method']) ?></span>
                    </div>

                    <div style="border-top:1px solid var(--border-color); padding-top:14px; margin-bottom:14px;">
                        <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; display:block; margin-bottom:10px;">Licensed Titles</span>
                        <?php foreach ($orderSuccess['items'] as $it): ?>
                            <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:8px;">
                                <span><i class="fa-solid fa-gamepad" style="color:var(--primary); margin-right:6px;"></i> <?= e($it['game_name']) ?></span>
                                <strong style="font-family:'Outfit',sans-serif;"><?= format_price($it['price']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top:1px dashed var(--border-color); padding-top:14px; display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:800; font-size:16px;">Total Paid:</span>
                        <span style="font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--primary);">
                            <?= format_price($orderSuccess['total_amount']) ?>
                        </span>
                    </div>
                </div>

                <div style="display:flex; gap:16px; justify-content:center;">
                    <a href="<?= BASE_URL ?>/customer/purchased.php" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-dice-d20"></i> Go to My Purchased Games
                    </a>
                    <a href="<?= BASE_URL ?>/customer/orders.php" class="btn btn-secondary btn-lg">
                        <i class="fa-solid fa-receipt"></i> View My Orders
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div style="margin-bottom:32px;">
                <h1 style="font-size:28px; font-weight:800;">Complete Your Purchase</h1>
                <p style="color:var(--text-muted); font-size:15px;">Review items and choose your payment method</p>
            </div>

            <?php if ($error): ?>
                <div style="background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php<?= $directGameId > 0 ? '?direct_game=' . $directGameId : '' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="place_order">

                <div style="display:grid; grid-template-columns:1fr 380px; gap:32px; align-items:flex-start;">
                    
                    <!-- Left: Payment Method Selection -->
                    <div>
                        <div class="card" style="margin-bottom:24px; padding:28px;">
                            <h3 style="font-size:18px; font-weight:800; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                                <i class="fa-solid fa-credit-card text-blue-500"></i> Select Payment Method
                            </h3>

                            <div style="display:flex; flex-direction:column; gap:14px;">
                                <label class="card" style="display:flex; align-items:center; gap:16px; padding:16px 20px; cursor:pointer; border-color:var(--primary); background:var(--primary-light);">
                                    <input type="radio" name="payment_method" value="UPI / Instant Pay" checked style="accent-color:var(--primary); transform:scale(1.2);">
                                    <div style="font-size:24px; color:var(--primary);"><i class="fa-solid fa-mobile-screen"></i></div>
                                    <div style="flex:1;">
                                        <strong style="display:block; font-size:15px; color:var(--text-primary);">UPI / Instant Pay (Google Pay, PhonePe, Paytm)</strong>
                                        <span style="font-size:12.5px; color:var(--text-muted);">Fast, zero fees instant digital activation</span>
                                    </div>
                                    <span class="badge badge-success">Recommended</span>
                                </label>

                                <label class="card" style="display:flex; align-items:center; gap:16px; padding:16px 20px; cursor:pointer;">
                                    <input type="radio" name="payment_method" value="Credit / Debit Card" style="accent-color:var(--primary); transform:scale(1.2);">
                                    <div style="font-size:24px; color:var(--text-secondary);"><i class="fa-solid fa-credit-card"></i></div>
                                    <div style="flex:1;">
                                        <strong style="display:block; font-size:15px; color:var(--text-primary);">Credit / Debit Card</strong>
                                        <span style="font-size:12.5px; color:var(--text-muted);">Visa, MasterCard, RuPay</span>
                                    </div>
                                </label>

                                <label class="card" style="display:flex; align-items:center; gap:16px; padding:16px 20px; cursor:pointer;">
                                    <input type="radio" name="payment_method" value="Net Banking" style="accent-color:var(--primary); transform:scale(1.2);">
                                    <div style="font-size:24px; color:var(--text-secondary);"><i class="fa-solid fa-building-columns"></i></div>
                                    <div style="flex:1;">
                                        <strong style="display:block; font-size:15px; color:var(--text-primary);">Net Banking</strong>
                                        <span style="font-size:12.5px; color:var(--text-muted);">All major Indian banks supported</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Customer Account Confirmation -->
                        <div class="card" style="padding:24px;">
                            <h3 style="font-size:16px; font-weight:700; margin-bottom:12px;">Licensed Customer Account</h3>
                            <div style="display:flex; align-items:center; gap:14px;">
                                <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="Avatar" style="width:48px; height:48px; border-radius:var(--radius-full);">
                                <div>
                                    <div style="font-weight:700; font-size:15px;"><?= e($user['name']) ?></div>
                                    <div style="color:var(--text-muted); font-size:13px;"><?= e($user['email']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Items & Pay Button -->
                    <div class="card" style="padding:28px;">
                        <h3 style="font-size:18px; font-weight:800; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border-color);">
                            Order Review (<?= count($itemsToPurchase) ?> <?= count($itemsToPurchase) === 1 ? 'Game' : 'Games' ?>)
                        </h3>

                        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                            <?php foreach ($itemsToPurchase as $it): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-size:14px;">
                                    <div style="display:flex; align-items:center; gap:10px; max-width:200px;">
                                        <i class="fa-solid fa-gamepad text-blue-500"></i>
                                        <span style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($it['game_name']) ?></span>
                                    </div>
                                    <span style="font-weight:700; font-family:'Outfit',sans-serif;"><?= format_price($it['price']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="border-top:1px dashed var(--border-color); padding-top:16px; margin-bottom:24px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-weight:800; font-size:16px;">Total Amount:</span>
                                <span style="font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; color:var(--primary);">
                                    <?= format_price($totalAmount) ?>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-size:16px;">
                            <i class="fa-solid fa-lock"></i> Pay <?= format_price($totalAmount) ?> Now
                        </button>

                        <div style="text-align:center; margin-top:16px; font-size:12px; color:var(--text-muted);">
                            By completing your order, you agree to the Games Arena Terms of Digital Distribution.
                        </div>
                    </div>

                </div>
            </form>

        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
