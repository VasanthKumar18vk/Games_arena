<?php
/**
 * Games Arena - Categories Showcase & Category Filtering
 */
$pageTitle = 'Game Categories — Games Arena';
$activeNav = 'categories';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

$pdo = get_db();
if (!$pdo) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

$selectedCategory = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch all categories with real game counts
$categories = $pdo->query("
    SELECT c.*, COUNT(g.game_id) as game_count 
    FROM categories c 
    LEFT JOIN games g ON c.category_id = g.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name ASC
")->fetchAll();

$totalAllGames = 0;
foreach ($categories as $cat) {
    $totalAllGames += (int)$cat['game_count'];
}

// Fetch games based on category selection
$categoryGames = [];
$currentCatInfo = null;

if ($selectedCategory > 0) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $selectedCategory) {
            $currentCatInfo = $cat;
            break;
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT g.*, c.category_name 
        FROM games g 
        JOIN categories c ON g.category_id = c.category_id 
        WHERE g.category_id = ? 
        ORDER BY g.rating DESC, g.total_reviews DESC
    ");
    $stmt->execute([$selectedCategory]);
    $categoryGames = $stmt->fetchAll();
} else {
    // If no category selected (All Categories), fetch all games
    $stmt = $pdo->query("
        SELECT g.*, c.category_name 
        FROM games g 
        JOIN categories c ON g.category_id = c.category_id 
        ORDER BY g.rating DESC, g.total_reviews DESC
    ");
    $categoryGames = $stmt->fetchAll();
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
        <div style="margin-bottom:28px;">
            <div style="display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:13px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">
                <i class="fa-solid fa-layer-group text-primary"></i> Store Exploration
            </div>
            <h1 style="font-size:30px; font-weight:900;"><?= __('explore_categories') ?></h1>
            <p style="color:var(--text-muted); font-size:15px; max-width:650px;">Select any game category below to instantly discover all titles, ratings, trailers, and editions available in that genre.</p>
        </div>

        <!-- Categories Visual Grid Selector -->
        <div class="categories-grid" style="margin-bottom:36px;">
            <!-- All Categories Card -->
            <a href="categories.php" class="category-card <?= $selectedCategory === 0 ? 'active' : '' ?>" style="<?= $selectedCategory === 0 ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                <div class="category-icon" style="<?= $selectedCategory === 0 ? 'background:var(--primary); color:#FFF;' : '' ?>">
                    <i class="fa-solid fa-border-all"></i>
                </div>
                <div>
                    <div class="category-name">All Categories</div>
                    <div class="category-count"><?= $totalAllGames ?> Total Titles</div>
                </div>
            </a>

            <?php foreach ($categories as $cat): 
                $isSelected = ($selectedCategory == $cat['category_id']);
            ?>
            <a href="categories.php?id=<?= $cat['category_id'] ?>" class="category-card <?= $isSelected ? 'active' : '' ?>" style="<?= $isSelected ? 'border-color:var(--primary); background:var(--primary-light);' : '' ?>">
                <div class="category-icon" style="<?= $isSelected ? 'background:var(--primary); color:#FFF;' : '' ?>">
                    <i class="fa-solid <?= e($cat['icon'] ?: 'fa-gamepad') ?>"></i>
                </div>
                <div>
                    <div class="category-name"><?= e($cat['category_name']) ?></div>
                    <div class="category-count"><?= $cat['game_count'] ?> Games Available</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Category Showcase Header & Filter Bar -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:24px; padding-top:20px; border-top:1px solid var(--border-color);">
            <div>
                <?php if ($currentCatInfo): ?>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                        <span class="badge badge-primary" style="font-size:13px;">
                            <i class="fa-solid <?= e($currentCatInfo['icon'] ?: 'fa-gamepad') ?>"></i> <?= e($currentCatInfo['category_name']) ?>
                        </span>
                        <span style="color:var(--text-muted); font-size:14px; font-weight:600;"><?= count($categoryGames) ?> Games in this category</span>
                    </div>
                    <h2 style="font-size:26px; font-weight:800;"><?= e($currentCatInfo['category_name']) ?> Games Catalog</h2>
                    <p style="color:var(--text-muted); font-size:14px; margin-top:2px; max-width:700px;"><?= e($currentCatInfo['description']) ?></p>
                <?php else: ?>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                        <span class="badge badge-primary" style="font-size:13px;">
                            <i class="fa-solid fa-border-all"></i> Complete Storefront
                        </span>
                        <span style="color:var(--text-muted); font-size:14px; font-weight:600;"><?= count($categoryGames) ?> Games Available</span>
                    </div>
                    <h2 style="font-size:26px; font-weight:800;">All Featured Games & Categories</h2>
                    <p style="color:var(--text-muted); font-size:14px; margin-top:2px;">Showing all curated PC games across every genre. Select a category card above to isolate specific genres.</p>
                <?php endif; ?>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <?php if ($currentCatInfo): ?>
                    <a href="games.php?category=<?= $currentCatInfo['category_id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in Catalog Search
                    </a>
                    <a href="categories.php" class="btn btn-outline-primary btn-sm" title="View All Categories">
                        <i class="fa-solid fa-rotate-left"></i> View All
                    </a>
                <?php else: ?>
                    <a href="games.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-sliders"></i> Advanced Search & Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Games Grid for Selected Category -->
        <?php if (empty($categoryGames)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:48px; color:var(--text-muted); margin-bottom:16px;"><i class="fa-solid fa-gamepad"></i></div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">No games found in this category</h2>
                <p style="color:var(--text-muted); max-width:400px; margin:0 auto 20px;">We are currently adding new titles to this genre. Check back soon or explore other categories!</p>
                <a href="categories.php" class="btn btn-primary">
                    <i class="fa-solid fa-layer-group"></i> Explore All Categories
                </a>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($categoryGames as $g): 
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
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> In Library</span>
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
        <?php endif; ?>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
