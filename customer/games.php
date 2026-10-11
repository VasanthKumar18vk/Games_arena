<?php
/**
 * Games Arena - Game Catalog, Search & Category Filtering
 */
$pageTitle = 'Browse Games — Games Arena';
$activeNav = 'search';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

$pdo = get_db();
if (!$pdo) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

$searchQuery = sanitize($_GET['search'] ?? '');
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;
$sortBy = sanitize($_GET['sort'] ?? 'popular');
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Fetch all categories for filter pills and dropdown
$categories = $pdo->query("
    SELECT c.*, COUNT(g.game_id) as count 
    FROM categories c 
    LEFT JOIN games g ON c.category_id = g.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name ASC
")->fetchAll();

$currentCatName = '';
if ($categoryId > 0) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $categoryId) {
            $currentCatName = $cat['category_name'];
            break;
        }
    }
}

// Build query
$where = ["1=1"];
$params = [];

if (!empty($searchQuery)) {
    $aliases = [
        'gta' => 'Grand Theft Auto',
        'cod' => 'Call of Duty',
        'rdr' => 'Red Dead',
        're' => 'Resident Evil',
        'civ' => 'Civilization',
        'forza' => 'Forza',
        'spiderman' => 'Spider-Man',
        'wukong' => 'Black Myth',
        'nfs' => 'Need for Speed',
        'bg3' => 'Baldur'
    ];
    $expandedTerm = $aliases[strtolower($searchQuery)] ?? $searchQuery;

    $where[] = "(g.game_name LIKE ? OR g.game_name LIKE ? OR g.genre LIKE ? OR g.developer LIKE ? OR g.publisher LIKE ? OR g.description LIKE ? OR c.category_name LIKE ?)";
    $term1 = "%{$searchQuery}%";
    $term2 = "%{$expandedTerm}%";
    $params[] = $term1;
    $params[] = $term2;
    $params[] = $term1;
    $params[] = $term1;
    $params[] = $term1;
    $params[] = $term1;
    $params[] = $term1;
}

if ($categoryId > 0) {
    $where[] = "g.category_id = ?";
    $params[] = $categoryId;
}

if ($maxPrice > 0) {
    $where[] = "g.price <= ?";
    $params[] = $maxPrice;
}

$orderBy = "g.rating DESC, g.total_reviews DESC";
if ($sortBy === 'newest') {
    $orderBy = "g.release_date DESC, g.game_id DESC";
} elseif ($sortBy === 'price_asc') {
    $orderBy = "g.price ASC";
} elseif ($sortBy === 'price_desc') {
    $orderBy = "g.price DESC";
} elseif ($sortBy === 'name_asc') {
    $orderBy = "g.game_name ASC";
} elseif ($sortBy === 'rating') {
    $orderBy = "g.rating DESC";
}

$sql = "
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY {$orderBy}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();
?>

<main class="app-main">
    <header class="app-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <button class="action-btn menu-toggle-btn" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <form action="<?= BASE_URL ?>/customer/games.php" method="GET" class="header-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" value="<?= e($searchQuery) ?>" placeholder="Search games by title, genre, publisher..." autocomplete="off">
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= $categoryId ?>">
                <?php endif; ?>
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
        
        <!-- Category Filter Pills Bar -->
        <div style="margin-bottom:20px; overflow-x:auto; padding-bottom:8px;">
            <div style="display:flex; gap:8px; align-items:center; min-width:max-content;">
                <span style="font-size:13px; font-weight:700; color:var(--text-muted); margin-right:4px;">CATEGORIES:</span>
                <a href="games.php?sort=<?= urlencode($sortBy) ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" 
                   class="badge <?= $categoryId === 0 ? 'badge-primary' : 'badge-secondary' ?>" 
                   style="padding:8px 16px; font-size:13.5px; border-radius:20px; text-decoration:none; cursor:pointer;">
                    All (<?= count($pdo->query("SELECT game_id FROM games")->fetchAll()) ?>)
                </a>
                <?php foreach ($categories as $cat): 
                    $isActive = ($categoryId == $cat['category_id']);
                ?>
                <a href="games.php?category=<?= $cat['category_id'] ?>&sort=<?= urlencode($sortBy) ?><?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '' ?>" 
                   class="badge <?= $isActive ? 'badge-primary' : 'badge-secondary' ?>" 
                   style="padding:8px 16px; font-size:13.5px; border-radius:20px; text-decoration:none; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa-solid <?= e($cat['icon'] ?: 'fa-gamepad') ?>" style="font-size:12px;"></i>
                    <?= e($cat['category_name']) ?> (<?= $cat['count'] ?>)
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filter Controls Bar -->
        <div class="card" style="margin-bottom:28px; padding:20px 24px;">
            <form method="GET" action="games.php" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
                <div style="flex:2; min-width:200px;">
                    <label class="form-label" style="font-size:12.5px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Search Query</label>
                    <div style="position:relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                        <input type="text" name="search" value="<?= e($searchQuery) ?>" class="form-control" style="padding-left:40px;" placeholder="Search title, genre, studio...">
                    </div>
                </div>

                <div style="flex:1; min-width:160px;">
                    <label class="form-label" style="font-size:12.5px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Category</label>
                    <select name="category" class="form-control">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= $categoryId == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?> (<?= $cat['count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1; min-width:160px;">
                    <label class="form-label" style="font-size:12.5px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Sort By</label>
                    <select name="sort" class="form-control">
                        <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Release Date</option>
                        <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="height:44px; padding:0 24px;">
                        <i class="fa-solid fa-filter"></i> Apply Filters
                    </button>
                    <a href="games.php" class="btn btn-secondary" style="height:44px; margin-left:8px;" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <div>
                <h1 style="font-size:24px; font-weight:800;">
                    <?php if (!empty($searchQuery) && $categoryId > 0): ?>
                        Results for "<span style="color:var(--primary);"><?= e($searchQuery) ?></span>" in <?= e($currentCatName) ?>
                    <?php elseif (!empty($searchQuery)): ?>
                        Search results for "<span style="color:var(--primary);"><?= e($searchQuery) ?></span>"
                    <?php elseif ($categoryId > 0): ?>
                        <i class="fa-solid fa-gamepad text-primary"></i> <?= e($currentCatName) ?> Games
                    <?php else: ?>
                        All PC Games Catalog
                    <?php endif; ?>
                </h1>
                <p style="color:var(--text-muted); font-size:14px;"><?= count($games) ?> games found</p>
            </div>
        </div>

        <!-- Games Grid -->
        <?php if (empty($games)): ?>
            <div class="card" style="text-align:center; padding:60px 24px;">
                <div style="font-size:48px; color:var(--text-muted); margin-bottom:16px;"><i class="fa-solid fa-ghost"></i></div>
                <h2 style="font-size:20px; font-weight:700; margin-bottom:8px;">No games match your search</h2>
                <p style="color:var(--text-muted); max-width:400px; margin:0 auto 20px;">Try adjusting your search terms or clearing category filters to find what you are looking for.</p>
                <a href="games.php" class="btn btn-primary">
                    <i class="fa-solid fa-layer-group"></i> Browse All Games
                </a>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($games as $g): 
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
