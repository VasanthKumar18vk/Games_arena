<?php
/**
 * Games Arena - Customer Dashboard
 */
$pageTitle = 'My Dashboard — Games Arena';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

require_login('login.php');

$pdo = get_db();
$userId = current_user_id();

// Fetch customer's purchased games
$ownedGamesStmt = $pdo->prepare("
    SELECT g.*, c.category_name, o.created_at as purchase_date 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN games g ON oi.game_id = g.game_id
    JOIN categories c ON g.category_id = c.category_id
    WHERE o.user_id = ? AND o.order_status IN ('completed', 'processing')
    ORDER BY o.created_at DESC
    LIMIT 4
");
$ownedGamesStmt->execute([$userId]);
$ownedGames = $ownedGamesStmt->fetchAll();

// Total games owned count
$totalOwnedStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT oi.game_id) as count 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE o.user_id = ? AND o.order_status IN ('completed', 'processing')
");
$totalOwnedStmt->execute([$userId]);
$totalOwned = (int)($totalOwnedStmt->fetch()['count'] ?? 0);

// Total orders count
$totalOrdersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$totalOrdersStmt->execute([$userId]);
$totalOrders = (int)($totalOrdersStmt->fetch()['count'] ?? 0);

// Customer favorites
$favoritesStmt = $pdo->prepare("
    SELECT g.*, c.category_name 
    FROM favorites f
    JOIN games g ON f.game_id = g.game_id
    JOIN categories c ON g.category_id = c.category_id
    WHERE f.user_id = ?
    LIMIT 4
");
$favoritesStmt->execute([$userId]);
$customerFavorites = $favoritesStmt->fetchAll();

// Recommended Store Games
$recommendedGames = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY RAND() 
    LIMIT 4
")->fetchAll();
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
        <!-- Personalized Banner -->
        <section class="hero-banner" style="background:linear-gradient(135deg, #1E40AF 0%, #2563EB 60%, #38BDF8 100%);">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-gamepad"></i> GAMER PROFILE
                </div>
                <h1 class="hero-title"><?= __('welcome_back') ?>, <?= e($user['name']) ?>!</h1>
                <p class="hero-desc">You have <?= $totalOwned ?> games in your digital library and <?= $favCount ?> saved in your wishlist.</p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/customer/purchased.php" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-dice-d20"></i> My Game Library
                    </a>
                    <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-outline-primary btn-lg" style="background:rgba(255,255,255,0.15); color:#FFF; border-color:rgba(255,255,255,0.4);">
                        <i class="fa-solid fa-magnifying-glass"></i> Explore Store
                    </a>
                </div>
            </div>
            <div style="display:flex; gap:16px;">
                <div class="card" style="background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.25); color:#FFF; text-align:center; padding:20px 28px; border-radius:var(--radius-md);">
                    <div style="font-size:32px; font-weight:800;"><?= $totalOwned ?></div>
                    <div style="font-size:13px; font-weight:600; opacity:0.85;">Owned Games</div>
                </div>
                <div class="card" style="background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.25); color:#FFF; text-align:center; padding:20px 28px; border-radius:var(--radius-md);">
                    <div style="font-size:32px; font-weight:800;"><?= $totalOrders ?></div>
                    <div style="font-size:13px; font-weight:600; opacity:0.85;">Orders Made</div>
                </div>
            </div>
        </section>

        <!-- 1. Continue Playing / Owned Games Library -->
        <?php if (!empty($ownedGames)): ?>
        <section style="margin-bottom:40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><i class="fa-solid fa-dice-d20 text-blue-500"></i> <?= __('purchased') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Instant access to all your digital licenses</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/purchased.php" style="font-weight:700; font-size:14px;">Full Library <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="games-grid">
                <?php foreach ($ownedGames as $g): ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img">
                        </a>
                        <span class="game-card-badge" style="background:#059669;"><i class="fa-solid fa-check"></i> Owned</span>
                    </div>
                    <div class="game-card-body">
                        <div class="game-card-category"><?= e($g['genre']) ?></div>
                        <h3 class="game-card-title">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" style="color:inherit;"><?= e($g['game_name']) ?></a>
                        </h3>
                        <div class="game-card-footer">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" class="btn btn-primary btn-sm" style="width:100%;">
                                <i class="fa-solid fa-play"></i> Play / Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- 2. Customer Favorites / Wishlist Preview -->
        <?php if (!empty($customerFavorites)): ?>
        <section style="margin-bottom:40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><i class="fa-solid fa-heart text-red-500"></i> <?= __('favorites') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Your saved games and wishlist</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/favorites.php" style="font-weight:700; font-size:14px;">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="games-grid">
                <?php foreach ($customerFavorites as $g): 
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card" id="fav-card-<?= $g['game_id'] ?>">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn active" data-game-id="<?= $g['game_id'] ?>" title="Remove Favorite">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                    </div>
                    <div class="game-card-body">
                        <div class="game-card-category"><?= e($g['genre']) ?></div>
                        <h3 class="game-card-title">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" style="color:inherit;"><?= e($g['game_name']) ?></a>
                        </h3>
                        <div class="game-card-footer">
                            <div class="game-card-price"><?= format_price($g['price']) ?></div>
                            <?php if ($isOwned): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Owned</span>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm btn-add-cart" data-game-id="<?= $g['game_id'] ?>">
                                    <i class="fa-solid fa-cart-shopping"></i> Add
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- 3. Recommended Games -->
        <section style="margin-bottom:30px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><?= __('recommended') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Top trending selections for you</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/games.php" style="font-weight:700; font-size:14px;">Browse Store <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="games-grid">
                <?php foreach ($recommendedGames as $g): 
                    $isFav = is_game_favorited($pdo, $userId, $g['game_id']);
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn <?= $isFav ? 'active' : '' ?>" data-game-id="<?= $g['game_id'] ?>" title="Add to Favorites">
                            <i class="<?= $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
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
                                    <i class="fa-solid fa-cart-shopping"></i> Add
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
