<?php
/**
 * Games Arena - Customer Digital Game Library (Purchased Games)
 */
$pageTitle = 'My Purchased Games — Games Arena';
$activeNav = 'purchased';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

require_login('login.php');

$pdo = get_db();
$userId = current_user_id();

// Fetch all distinct games owned by user
$stmt = $pdo->prepare("
    SELECT DISTINCT g.*, c.category_name, o.created_at as purchase_date, o.order_number 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    JOIN games g ON oi.game_id = g.game_id 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE o.user_id = ? AND o.order_status IN ('completed', 'processing') 
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$ownedGames = $stmt->fetchAll();
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
                <i class="fa-solid fa-dice-d20 text-blue-500"></i> <?= __('purchased') ?>
            </h1>
            <p style="color:var(--text-muted); font-size:15px;">Your digital PC game collection & activation licenses</p>
        </div>

        <?php if (empty($ownedGames)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:52px; color:var(--text-muted); margin-bottom:16px;">
                    <i class="fa-solid fa-gamepad"></i>
                </div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">Your game library is empty</h2>
                <p style="color:var(--text-muted); max-width:420px; margin:0 auto 20px;">
                    Games you purchase will appear here permanently with instant launch access, cloud saves, and verified license tokens.
                </p>
                <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-primary">
                    <i class="fa-solid fa-store"></i> Browse Games to Buy
                </a>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($ownedGames as $g): ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img">
                        </a>
                        <span class="game-card-badge" style="background:#059669;"><i class="fa-solid fa-circle-check"></i> Activated</span>
                    </div>
                    <div class="game-card-body">
                        <div class="game-card-category"><?= e($g['genre']) ?></div>
                        <h3 class="game-card-title">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" style="color:inherit;"><?= e($g['game_name']) ?></a>
                        </h3>
                        <div style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                            Purchased <?= date('M d, Y', strtotime($g['purchase_date'])) ?>
                        </div>

                        <div class="game-card-footer" style="display:flex; flex-direction:column; gap:8px;">
                            <button class="btn btn-primary btn-sm" style="width:100%;" onclick="launchGame('<?= addslashes(e($g['game_name'])) ?>')">
                                <i class="fa-solid fa-play"></i> Launch Game
                            </button>
                            <div style="display:flex; gap:8px; width:100%;">
                                <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" class="btn btn-secondary btn-sm" style="flex:1;">
                                    <i class="fa-solid fa-circle-info"></i> Details
                                </a>
                                <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>#review-star-picker" class="btn btn-secondary btn-sm" style="flex:1;">
                                    <i class="fa-solid fa-star text-amber-400"></i> Review
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

<script>
function launchGame(gameTitle) {
    showToast(`Launching ${gameTitle}... Initializing Games Arena Cloud Client 🚀`, 'primary');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
