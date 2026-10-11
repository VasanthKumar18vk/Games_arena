<?php
/**
 * Games Arena - Shopping Cart & License Checks
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$userId = current_user_id();

// Handle AJAX & Form Cart Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_cart') {
        $gameId = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;

        if ($gameId > 0) {
            if ($userId) {
                // Check if user already owns this digital game
                if (is_game_owned($pdo, $userId, $gameId)) {
                    $response = ['status' => 'info', 'message' => 'You already own this game in your library!'];
                } else {
                    // Ensure cart exists
                    $stmt = $pdo->prepare("INSERT IGNORE INTO carts (user_id) VALUES (?)");
                    $stmt->execute([$userId]);

                    $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $cart = $stmt->fetch();
                    $cartId = $cart['cart_id'];

                    // Insert into cart_items
                    $stmt = $pdo->prepare("
                        INSERT INTO cart_items (cart_id, game_id, quantity) 
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE quantity = 1
                    ");
                    $stmt->execute([$cartId, $gameId]);

                    $cartCount = get_cart_count($pdo, $userId);
                    $response = ['status' => 'success', 'message' => 'Game added to your cart!', 'cart_count' => $cartCount];
                }
            } else {
                // Guest cart in session
                if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                $_SESSION['cart'][$gameId] = 1;
                $cartCount = count($_SESSION['cart']);
                $response = ['status' => 'success', 'message' => 'Game added to your cart!', 'cart_count' => $cartCount, 'is_guest' => true];
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            } else {
                set_flash($response['status'], $response['message']);
                header("Location: " . BASE_URL . "/customer/cart.php");
                exit;
            }
        }
    } elseif ($_POST['action'] === 'remove_item') {
        $cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
        if ($cartItemId > 0) {
            if ($userId) {
                $stmt = $pdo->prepare("
                    DELETE ci FROM cart_items ci
                    JOIN carts c ON ci.cart_id = c.cart_id
                    WHERE (ci.cart_item_id = ? OR ci.game_id = ?) AND c.user_id = ?
                ");
                $stmt->execute([$cartItemId, $cartItemId, $userId]);
            } else {
                if (isset($_SESSION['cart'][$cartItemId])) {
                    unset($_SESSION['cart'][$cartItemId]);
                }
            }
            set_flash('info', 'Item removed from your cart.');
            header("Location: " . BASE_URL . "/customer/cart.php");
            exit;
        }
    } elseif ($_POST['action'] === 'clear_cart') {
        if ($userId) {
            $stmt = $pdo->prepare("
                DELETE ci FROM cart_items ci
                JOIN carts c ON ci.cart_id = c.cart_id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
        }
        $_SESSION['cart'] = [];
        set_flash('info', 'Your cart has been cleared.');
        header("Location: " . BASE_URL . "/customer/cart.php");
        exit;
    }
}

$pageTitle = 'Shopping Cart — Games Arena';
$activeNav = 'cart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

$cartItems = get_user_cart($pdo, $userId);
$subtotal = get_cart_total($pdo, $userId);
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

            <a href="<?= BASE_URL ?>/customer/cart.php" class="action-btn" title="Shopping Cart" style="background:var(--primary-light); color:var(--primary);">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="badge-count" id="cart-badge-count"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (is_logged_in() && $user): ?>
                <a href="<?= BASE_URL ?>/customer/profile.php" style="display:flex; align-items:center; gap:10px; text-decoration:none;">
                    <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="Avatar" class="user-avatar" style="width:38px; height:38px;">
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:32px;">
            <h1 style="font-size:28px; font-weight:800; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid fa-cart-shopping text-blue-500"></i> <?= __('cart') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Review selected items and proceed to secure checkout</p>
        </div>

        <?php $flash = get_flash(); if ($flash): ?>
            <div style="background:<?= $flash['type'] === 'success' ? '#ECFDF5' : '#EFF6FF' ?>; border:1px solid <?= $flash['type'] === 'success' ? '#A7F3D0' : '#BFDBFE' ?>; color:<?= $flash['type'] === 'success' ? '#065F46' : '#1E40AF' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; display:flex; align-items:center; gap:12px; font-weight:600;">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info' ?>"></i>
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:52px; color:var(--text-muted); margin-bottom:16px;">
                    <i class="fa-solid fa-cart-arrow-down"></i>
                </div>
                <h2 style="font-size:22px; font-weight:700; margin-bottom:8px;"><?= __('empty_cart') ?></h2>
                <p style="color:var(--text-muted); max-width:420px; margin:0 auto 24px;">
                    You have not added any digital games to your cart yet. Explore our curated store to find your next adventure!
                </p>
                <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-gamepad"></i> Explore Games
                </a>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:1fr 340px; gap:32px; align-items:flex-start;">
                
                <!-- Items Table / List -->
                <div class="card" style="padding:0; overflow:hidden;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Game Title</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th style="text-align:right;">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:16px;">
                                            <img src="<?= get_game_cover($item['game_image'], $item['game_name']) ?>" alt="<?= e($item['game_name']) ?>" style="width:70px; height:45px; object-fit:cover; border-radius:var(--radius-sm);">
                                            <div>
                                                <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $item['game_id'] ?>" style="font-weight:700; color:var(--text-primary);">
                                                    <?= e($item['game_name']) ?>
                                                </a>
                                                <div style="font-size:12px; color:var(--text-muted);"><?= e($item['category_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight:600;"><?= format_price($item['price']) ?></td>
                                    <td>
                                        <span class="badge badge-primary">1 License</span>
                                    </td>
                                    <td style="font-weight:800; color:var(--primary); font-family:'Outfit',sans-serif; font-size:16px;">
                                        <?= format_price($item['price'] * $item['quantity']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="cart.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger); padding:6px 10px;" title="Remove from cart">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding:16px 20px; display:flex; justify-content:space-between; align-items:center; background:var(--bg-input);">
                        <a href="<?= BASE_URL ?>/customer/games.php" style="font-size:13.5px; font-weight:600; color:var(--text-secondary);">
                            <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                        </a>
                        <form method="POST" action="cart.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--text-muted);" onclick="return confirm('Clear all items from your cart?');">
                                <i class="fa-solid fa-broom"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order Summary Card -->
                <div class="card" style="padding:28px;">
                    <h3 style="font-size:18px; font-weight:800; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid var(--border-color);">
                        Order Summary
                    </h3>

                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:14px; color:var(--text-secondary);">
                        <span>Items Subtotal</span>
                        <span><?= format_price($subtotal) ?></span>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-bottom:16px; font-size:14px; color:var(--text-secondary);">
                        <span>Digital Delivery (Instant)</span>
                        <span style="color:var(--success); font-weight:700;">FREE</span>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px dashed var(--border-color); margin-bottom:24px;">
                        <span style="font-weight:800; font-size:16px;"><?= __('total') ?></span>
                        <span style="font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; color:var(--primary);">
                            <?= format_price($subtotal) ?>
                        </span>
                    </div>

                    <a href="<?= BASE_URL ?>/customer/checkout.php" class="btn btn-primary btn-lg" style="width:100%; font-size:16px;">
                        <i class="fa-solid fa-lock"></i> <?= __('checkout') ?>
                    </a>

                    <div style="text-align:center; margin-top:16px; font-size:12px; color:var(--text-muted); display:flex; align-items:center; justify-content:center; gap:6px;">
                        <i class="fa-solid fa-shield-halved text-emerald-500"></i> Instant 256-bit Encrypted Checkout
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
