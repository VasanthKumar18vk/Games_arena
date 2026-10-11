<?php
/**
 * Games Arena - Main Storefront & Discovery
 */
$pageTitle = 'Games Arena — Buy & Discover Top PC Games';
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/customer-sidebar.php';

$pdo = get_db();

// Fallback if DB not ready
if (!$pdo) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

// Fetch Featured Game for Hero
$stmt = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.is_featured = 1 
    ORDER BY g.rating DESC, g.total_reviews DESC LIMIT 1
");
$featuredGame = $stmt->fetch() ?: [
    'game_id' => 35,
    'game_name' => 'Black Myth: Wukong',
    'category_name' => 'Action',
    'genre' => 'Action RPG, Mythological',
    'description' => 'Black Myth: Wukong is an action RPG rooted in Chinese mythology. Venture into challenges and marvels to uncover the truth of a glorious legend.',
    'price' => 3599.00,
    'rating' => 4.9,
    'game_image' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80'
];

// Fetch 15 Years Game of the Year Data
$gotyList = [];
try {
    $stmt = $pdo->query("
        SELECT goty.year, goty.description as goty_desc, g.*, c.category_name 
        FROM game_of_the_year goty
        JOIN games g ON goty.game_id = g.game_id
        JOIN categories c ON g.category_id = c.category_id
        ORDER BY goty.year DESC
    ");
    $gotyList = $stmt->fetchAll();
} catch (Exception $e) {
    // If not seeded yet
}

// Selected GOTY Year (default latest)
$selectedGotyYear = isset($_GET['goty_year']) ? (int)$_GET['goty_year'] : ($gotyList[0]['year'] ?? 2025);
$selectedGoty = null;
foreach ($gotyList as $g) {
    if ($g['year'] == $selectedGotyYear) {
        $selectedGoty = $g;
        break;
    }
}
if (!$selectedGoty && !empty($gotyList)) {
    $selectedGoty = $gotyList[0];
}

// Popular Games (Top rated)
$popularGames = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY g.rating DESC, g.total_reviews DESC 
    LIMIT 6
")->fetchAll();

// Recently Added Games
$recentGames = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY g.release_date DESC, g.game_id DESC 
    LIMIT 6
")->fetchAll();

// Categories with Count
$categories = $pdo->query("
    SELECT c.*, COUNT(g.game_id) as game_count 
    FROM categories c 
    LEFT JOIN games g ON c.category_id = g.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name ASC
")->fetchAll();

// Recommended Games
$recommendedGames = $pdo->query("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY RAND() 
    LIMIT 6
")->fetchAll();
?>

<main class="app-main">
    <!-- Top Navigation Header -->
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

    <!-- Page Content -->
    <div class="page-body">

        <!-- Welcome Flash Notification if any -->
        <?php $flash = get_flash(); if ($flash): ?>
            <div style="background:<?= $flash['type'] === 'success' ? '#ECFDF5' : '#EFF6FF' ?>; border:1px solid <?= $flash['type'] === 'success' ? '#A7F3D0' : '#BFDBFE' ?>; color:<?= $flash['type'] === 'success' ? '#065F46' : '#1E40AF' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; display:flex; align-items:center; gap:12px; font-weight:600;">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-info' ?>"></i>
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- 1. Hero Featured Banner -->
        <section class="hero-banner">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-fire"></i> FEATURED SPOTLIGHT • <?= e($featuredGame['category_name']) ?>
                </div>
                <h1 class="hero-title"><?= e($featuredGame['game_name']) ?></h1>
                <p class="hero-desc"><?= e($featuredGame['description']) ?></p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $featuredGame['game_id'] ?>" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-gamepad"></i> Explore Game
                    </a>
                    <?php if (is_game_owned($pdo, $userId, $featuredGame['game_id'])): ?>
                        <span class="badge badge-success" style="padding:10px 18px; font-size:14px;"><i class="fa-solid fa-check"></i> In Your Library</span>
                    <?php elseif (is_game_in_cart($pdo, $userId, $featuredGame['game_id'])): ?>
                        <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-outline-primary btn-lg" style="background:rgba(255,255,255,0.2); color:#FFF; border-color:rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-cart-shopping"></i> In Cart
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-primary btn-add-cart btn-lg" data-game-id="<?= $featuredGame['game_id'] ?>" style="background:rgba(255,255,255,0.15); color:#FFF; border-color:rgba(255,255,255,0.4);">
                            <i class="fa-solid fa-cart-shopping"></i> Add <?= format_price($featuredGame['price']) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div style="max-width:380px; width:100%; border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 12px 30px rgba(0,0,0,0.3); border:2px solid rgba(255,255,255,0.2);">
                <img src="<?= get_game_cover($featuredGame['game_image'], $featuredGame['game_name']) ?>" alt="<?= e($featuredGame['game_name']) ?>" style="width:100%; height:220px; object-fit:cover;">
            </div>
        </section>

        <!-- 2. 🎮 Browse Categories -->
        <section style="margin-bottom:40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><?= __('explore_categories') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Select any category to view all games in that genre</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/categories.php" style="font-weight:700; font-size:14px;">View All Categories <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/customer/categories.php?id=<?= $cat['category_id'] ?>" class="category-card" title="Browse all <?= e($cat['category_name']) ?> games">
                    <div class="category-icon">
                        <i class="fa-solid <?= e($cat['icon'] ?: 'fa-gamepad') ?>"></i>
                    </div>
                    <div>
                        <div class="category-name"><?= e($cat['category_name']) ?></div>
                        <div class="category-count"><?= $cat['game_count'] ?> Titles Available</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 3. 🏆 Game of the Year 15-Year Showcase -->
        <?php if (!empty($gotyList) && $selectedGoty): ?>
        <section class="goty-section" id="goty-showcase" style="margin-bottom:40px;">
            <div class="goty-header">
                <div>
                    <h2 style="font-size:24px; font-weight:800; display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-trophy text-amber-500"></i> Game of the Year Hall of Fame
                    </h2>
                    <p style="color:var(--text-muted); font-size:14px; margin-top:2px;">Celebrating the greatest award-winning PC masterpieces from 2011 to 2025</p>
                </div>
                <div class="badge badge-primary" style="font-size:13px; padding:6px 14px;">
                    Winner of <?= e($selectedGoty['year']) ?>
                </div>
            </div>

            <!-- 15-Year Selector Pills -->
            <div class="goty-years-pills">
                <?php foreach ($gotyList as $item): ?>
                    <a href="?goty_year=<?= $item['year'] ?>#goty-showcase" class="year-pill <?= $item['year'] == $selectedGoty['year'] ? 'active' : '' ?>">
                        <i class="fa-solid fa-crown" style="font-size:11px; margin-right:4px;"></i> <?= $item['year'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Selected GOTY Game Spotlight -->
            <div class="goty-feature-card">
                <div style="border-radius:var(--radius-md); overflow:hidden; box-shadow:var(--shadow-md);">
                    <img src="<?= get_game_cover($selectedGoty['game_image'], $selectedGoty['game_name']) ?>" alt="<?= e($selectedGoty['game_name']) ?>" style="width:100%; max-height:300px; object-fit:cover;">
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                        <span class="badge badge-warning"><i class="fa-solid fa-trophy"></i> <?= $selectedGoty['year'] ?> GOTY Winner</span>
                        <span style="color:var(--text-muted); font-size:13px; font-weight:600;"><?= e($selectedGoty['category_name']) ?></span>
                    </div>
                    <h3 style="font-size:26px; font-weight:800; margin-bottom:10px;"><?= e($selectedGoty['game_name']) ?></h3>
                    <p style="color:var(--text-secondary); font-size:14.5px; margin-bottom:16px; line-height:1.6;">
                        <?= e($selectedGoty['goty_desc'] ?: $selectedGoty['description']) ?>
                    </p>
                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
                        <div style="font-size:20px; font-weight:800; color:var(--primary);">
                            <?= format_price($selectedGoty['price']) ?>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <?= render_stars($selectedGoty['rating'], true) ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $selectedGoty['game_id'] ?>" class="btn btn-primary">
                            <i class="fa-solid fa-circle-info"></i> View GOTY Details
                        </a>
                        <?php if (is_game_owned($pdo, $userId, $selectedGoty['game_id'])): ?>
                            <span class="badge badge-success" style="padding:10px 16px; font-size:14px;"><i class="fa-solid fa-check"></i> Owned in Library</span>
                        <?php elseif (is_game_in_cart($pdo, $userId, $selectedGoty['game_id'])): ?>
                            <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-secondary">
                                <i class="fa-solid fa-cart-shopping"></i> View in Cart
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-add-cart" data-game-id="<?= $selectedGoty['game_id'] ?>">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 4. 🔥 Popular Games -->
        <section style="margin-bottom:40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><?= __('popular_games') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Highest rated and best-selling titles</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/games.php?sort=rating" style="font-weight:700; font-size:14px;">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="games-grid">
                <?php foreach ($popularGames as $g): 
                    $isFav = is_game_favorited($pdo, $userId, $g['game_id']);
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                    $isInCart = is_game_in_cart($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img" loading="lazy">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn <?= $isFav ? 'active' : '' ?>" data-game-id="<?= $g['game_id'] ?>" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                            <i class="<?= $isFav ? 'fa-solid fa-heart text-red-500' : 'fa-regular fa-heart' ?>"></i>
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
                            <?php elseif ($isInCart): ?>
                                <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-check"></i> In Cart
                                </a>
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

        <!-- 5. 🆕 Recently Added Games -->
        <section style="margin-bottom:40px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><?= __('recent_games') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Fresh releases and new store additions</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/games.php?sort=newest" style="font-weight:700; font-size:14px;">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="games-grid">
                <?php foreach ($recentGames as $g): 
                    $isFav = is_game_favorited($pdo, $userId, $g['game_id']);
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                    $isInCart = is_game_in_cart($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img" loading="lazy">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn <?= $isFav ? 'active' : '' ?>" data-game-id="<?= $g['game_id'] ?>" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                            <i class="<?= $isFav ? 'fa-solid fa-heart text-red-500' : 'fa-regular fa-heart' ?>"></i>
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
                            <?php elseif ($isInCart): ?>
                                <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-check"></i> In Cart
                                </a>
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

        <!-- 6. ⭐ Recommended Games -->
        <section style="margin-bottom:30px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;"><?= __('recommended') ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Curated titles picked for you</p>
                </div>
            </div>

            <div class="games-grid">
                <?php foreach ($recommendedGames as $g): 
                    $isFav = is_game_favorited($pdo, $userId, $g['game_id']);
                    $isOwned = is_game_owned($pdo, $userId, $g['game_id']);
                    $isInCart = is_game_in_cart($pdo, $userId, $g['game_id']);
                ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>">
                            <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" class="game-card-img" loading="lazy">
                        </a>
                        <span class="game-card-badge"><?= e($g['category_name']) ?></span>
                        <button class="game-card-fav-btn <?= $isFav ? 'active' : '' ?>" data-game-id="<?= $g['game_id'] ?>" title="<?= $isFav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                            <i class="<?= $isFav ? 'fa-solid fa-heart text-red-500' : 'fa-regular fa-heart' ?>"></i>
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
                            <?php elseif ($isInCart): ?>
                                <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-check"></i> In Cart
                                </a>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
