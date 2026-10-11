<?php
/**
 * Games Arena - Customer Wishlist / Favorites
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db();
$userId = current_user_id();

// Handle AJAX Favorite Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    header('Content-Type: application/json');

    $gameId = isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0;

    if ($gameId > 0) {
        if ($userId) {
            $checkStmt = $pdo->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND game_id = ?");
            $checkStmt->execute([$userId, $gameId]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                $delStmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND game_id = ?");
                $delStmt->execute([$userId, $gameId]);
                $isFav = false;
            } else {
                $addStmt = $pdo->prepare("INSERT INTO favorites (user_id, game_id) VALUES (?, ?)");
                $addStmt->execute([$userId, $gameId]);
                $isFav = true;
            }

            $totalFavs = get_favorites_count($pdo, $userId);
            echo json_encode(['status' => 'success', 'is_favorited' => $isFav, 'total_favorites' => $totalFavs]);
            exit;
        } else {
            // Guest session favorites
            if (!isset($_SESSION['favorites']) || !is_array($_SESSION['favorites'])) {
                $_SESSION['favorites'] = [];
            }

            if (!empty($_SESSION['favorites'][$gameId])) {
                unset($_SESSION['favorites'][$gameId]);
                $isFav = false;
            } else {
                $_SESSION['favorites'][$gameId] = true;
                $isFav = true;
            }

            $totalFavs = count($_SESSION['favorites']);
            echo json_encode(['status' => 'success', 'is_favorited' => $isFav, 'total_favorites' => $totalFavs, 'is_guest' => true]);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

$pageTitle = 'My Favorites — Games Arena';
$activeNav = 'favorites';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

// Fetch all favorites (authenticated or guest session)
$favorites = [];
if ($userId) {
    $stmt = $pdo->prepare("
        SELECT g.*, c.category_name 
        FROM favorites f 
        JOIN games g ON f.game_id = g.game_id 
        JOIN categories c ON g.category_id = c.category_id 
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);
    $favorites = $stmt->fetchAll();
} elseif (!empty($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
    $gameIds = array_map('intval', array_keys($_SESSION['favorites']));
    $gameIds = array_filter($gameIds, fn($id) => $id > 0);
    if (!empty($gameIds)) {
        $placeholders = implode(',', array_fill(0, count($gameIds), '?'));
        $stmt = $pdo->prepare("
            SELECT g.*, c.category_name 
            FROM games g 
            JOIN categories c ON g.category_id = c.category_id 
            WHERE g.game_id IN ($placeholders) 
            ORDER BY g.game_name ASC
        ");
        $stmt->execute($gameIds);
        $favorites = $stmt->fetchAll();
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

            <a href="<?= BASE_URL ?>/customer/favorites.php" class="action-btn" title="Favorites" style="background:var(--primary-light); color:var(--primary);">
                <i class="fa-solid fa-heart text-red-500"></i>
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
                <i class="fa-solid fa-heart text-red-500"></i> <?= __('favorites') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Games you have saved for later</p>
        </div>

        <?php if (!is_logged_in() && !empty($favorites)): ?>
            <div style="background:var(--primary-light); border:1px solid var(--primary-subtle); color:var(--text-primary); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px; font-size:14px;">
                    <i class="fa-solid fa-cloud-arrow-up text-primary" style="font-size:18px;"></i>
                    <span><strong>Guest Wishlist:</strong> Log in or register to sync and save your favorites permanently across all your devices.</span>
                </div>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Log In to Sync
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($favorites)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:48px; color:var(--text-muted); margin-bottom:16px;">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">Your wishlist is empty</h2>
                <p style="color:var(--text-muted); max-width:420px; margin:0 auto 20px;">
                    Explore our store and click the heart icon on any game card to add it to your saved favorites list.
                </p>
                <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-primary">
                    <i class="fa-solid fa-store"></i> Browse Store Games
                </a>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($favorites as $g): 
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card" id="fav-card-<?= $g['game_id'] ?>">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn active" data-game-id="<?= $g['game_id'] ?>" title="Remove from Favorites">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                    </div>
                    <div class="game-card-body">
                        <div class="game-card-category"><?= e($g['genre']) ?></div>
                        <h3 class="game-card-title">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" style="color:inherit;"><?= e($g['game_name']) ?></a>
                        </h3>
                        <div class="game-card-rating">
                            <?= render_stars($g['rating']) ?>
                            <span>(<?= $g['total_reviews'] ?>)</span>
                        </div>
                        <div class="game-card-footer">
                            <div class="game-card-price"><?= format_price($g['price']) ?></div>
                            <?php if ($isOwned): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Owned</span>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm btn-add-cart" data-game-id="<?= $g['game_id'] ?>">
                                    <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
