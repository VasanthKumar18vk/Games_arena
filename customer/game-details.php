<?php
/**
 * Games Arena - Dedicated Game Details Dashboard
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/customer-sidebar.php';

$pdo = get_db();
if (!$pdo) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

$gameId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($gameId <= 0) {
    header("Location: " . BASE_URL . "/customer/games.php");
    exit;
}

// Fetch Game Data
$stmt = $pdo->prepare("
    SELECT g.*, c.category_name 
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.game_id = ?
");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    echo "<div class='app-main'><div class='page-body'><h2>Game not found</h2><a href='games.php'>Back to store</a></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch System Requirements
$reqStmt = $pdo->prepare("SELECT * FROM game_requirements WHERE game_id = ?");
$reqStmt->execute([$gameId]);
$requirementsRaw = $reqStmt->fetchAll();
$minReq = null;
$recReq = null;
foreach ($requirementsRaw as $r) {
    if ($r['requirement_type'] === 'minimum') $minReq = $r;
    if ($r['requirement_type'] === 'recommended') $recReq = $r;
}

// Handle Review Submission / Edit / Delete
$reviewMessage = '';
$reviewMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    if (!validate_csrf()) {
        $reviewMessage = 'Security validation failed. Please try again.';
        $reviewMessageType = 'danger';
    } elseif ($_POST['action'] === 'submit_review') {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $reviewText = sanitize($_POST['review_text'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        if (empty($reviewText)) {
            $reviewMessage = 'Please enter your review text.';
            $reviewMessageType = 'danger';
        } else {
            // Upsert review (1 review per game rule)
            $stmt = $pdo->prepare("
                INSERT INTO reviews (game_id, user_id, rating, review_text, status) 
                VALUES (?, ?, ?, ?, 'visible') 
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), status = 'visible', updated_at = NOW()
            ");
            $stmt->execute([$gameId, $userId, $rating, $reviewText]);

            recalculate_game_rating($pdo, $gameId);
            $reviewMessage = 'Thank you! Your review has been published.';
            $reviewMessageType = 'success';

            // Refresh game rating in current session
            $stmt = $pdo->prepare("SELECT rating, total_reviews FROM games WHERE game_id = ?");
            $stmt->execute([$gameId]);
            $updatedStats = $stmt->fetch();
            $game['rating'] = $updatedStats['rating'];
            $game['total_reviews'] = $updatedStats['total_reviews'];
        }
    } elseif ($_POST['action'] === 'delete_review') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE game_id = ? AND user_id = ?");
        $stmt->execute([$gameId, $userId]);
        recalculate_game_rating($pdo, $gameId);
        $reviewMessage = 'Your review has been removed.';
        $reviewMessageType = 'info';
    }
}

// Fetch Game Reviews
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.profile_image 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.game_id = ? AND r.status = 'visible' 
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$gameId]);
$reviews = $reviewsStmt->fetchAll();

// User's own review
$userReview = null;
if ($userId) {
    foreach ($reviews as $rev) {
        if ($rev['user_id'] == $userId) {
            $userReview = $rev;
            break;
        }
    }
}

// Rating breakdown (5,4,3,2,1 star count)
$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rev) {
    $r = (int)$rev['rating'];
    if (isset($starCounts[$r])) {
        $starCounts[$r]++;
    }
}

$isFav = is_game_favorited($pdo, $userId, $gameId);
$isOwned = is_game_owned($pdo, $userId, $gameId);
$inCart = is_game_in_cart($pdo, $userId, $gameId);
?>

<main class="app-main">
    <header class="app-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <button class="action-btn menu-toggle-btn" id="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="<?= BASE_URL ?>/customer/games.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Games
            </a>
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

        <?php if ($reviewMessage): ?>
            <div style="background:<?= $reviewMessageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $reviewMessageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $reviewMessageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; display:flex; align-items:center; gap:12px; font-weight:600;">
                <i class="fa-solid <?= $reviewMessageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($reviewMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Game Showcase Header -->
        <div class="game-details-header">
            <!-- Left Cover Showcase & Trailer -->
            <div>
                <div class="game-cover-showcase" style="margin-bottom:16px;">
                    <img src="<?= get_game_cover($game['game_image'], $game['game_name']) ?>" alt="<?= e($game['game_name']) ?>">
                </div>

                <?php if (!empty($game['trailer_url'])): ?>
                    <div class="card" style="padding:16px;">
                        <h4 style="font-size:15px; font-weight:700; margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-film text-red-500"></i> Official Video Preview
                        </h4>
                        <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:var(--radius-md);">
                            <iframe src="<?= e($game['trailer_url']) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen></iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Details & Buy Actions -->
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <span class="badge badge-primary"><?= e($game['category_name']) ?></span>
                    <span style="color:var(--text-muted); font-size:14px; font-weight:600;"><?= e($game['genre']) ?></span>
                </div>

                <h1 style="font-size:36px; font-weight:800; margin-bottom:12px; line-height:1.2;"><?= e($game['game_name']) ?></h1>

                <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <?= render_stars($game['rating'], true) ?>
                    </div>
                    <span style="color:var(--text-muted); font-size:14px;">(<?= $game['total_reviews'] ?> customer reviews)</span>
                </div>

                <!-- Price & Purchase Action Card -->
                <div class="card" style="background:var(--primary-light); border-color:var(--primary-subtle); padding:24px; margin-bottom:28px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                        <div>
                            <span style="font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Digital License Price</span>
                            <div style="font-family:'Outfit',sans-serif; font-size:32px; font-weight:800; color:var(--primary);">
                                <?= format_price($game['price']) ?>
                            </div>
                        </div>
                        <button class="game-card-fav-btn <?= $isFav ? 'active' : '' ?>" data-game-id="<?= $game['game_id'] ?>" style="position:static; width:46px; height:46px; font-size:20px;">
                            <i class="<?= $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                        </button>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <?php if ($isOwned): ?>
                            <div class="badge badge-success" style="padding:14px 24px; font-size:15px; width:100%; justify-content:center;">
                                <i class="fa-solid fa-circle-check" style="font-size:18px;"></i> In Your Purchased Library
                            </div>
                        <?php else: ?>
                            <button class="btn btn-primary btn-add-cart btn-lg" data-game-id="<?= $game['game_id'] ?>" style="flex:1;">
                                <i class="fa-solid fa-cart-shopping"></i> <?= $inCart ? 'Already in Cart' : 'Add to Cart' ?>
                            </button>
                            <a href="<?= BASE_URL ?>/customer/checkout.php?direct_game=<?= $game['game_id'] ?>" class="btn btn-secondary btn-lg" style="font-weight:700;">
                                <i class="fa-solid fa-bolt text-amber-500"></i> Instant Buy
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Game Meta Info Grid -->
                <div class="card" style="padding:20px; margin-bottom:28px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; font-size:14px;">
                        <div>
                            <strong style="color:var(--text-muted); font-size:12px; text-transform:uppercase; display:block;">Developer</strong>
                            <span style="font-weight:600;"><?= e($game['developer'] ?: 'Studio') ?></span>
                        </div>
                        <div>
                            <strong style="color:var(--text-muted); font-size:12px; text-transform:uppercase; display:block;">Publisher</strong>
                            <span style="font-weight:600;"><?= e($game['publisher'] ?: 'Publisher') ?></span>
                        </div>
                        <div>
                            <strong style="color:var(--text-muted); font-size:12px; text-transform:uppercase; display:block;">Release Date</strong>
                            <span style="font-weight:600;"><?= !empty($game['release_date']) ? date('M d, Y', strtotime($game['release_date'])) : 'Available Now' ?></span>
                        </div>
                        <div>
                            <strong style="color:var(--text-muted); font-size:12px; text-transform:uppercase; display:block;">Category</strong>
                            <span style="font-weight:600;"><?= e($game['category_name']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- About Game Description -->
                <div>
                    <h3 style="font-size:20px; font-weight:800; margin-bottom:12px;">About the Game</h3>
                    <p style="color:var(--text-secondary); font-size:15px; line-height:1.7;">
                        <?= nl2br(e($game['description'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Tabbed System Requirements -->
        <section class="card tabs-wrapper" style="margin-bottom:36px; padding:32px;">
            <h2 style="font-size:22px; font-weight:800; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-microchip text-blue-500"></i> <?= __('system_req') ?>
            </h2>

            <div class="tabs-nav">
                <button class="tab-btn active" data-tab-target="tab-min">
                    <i class="fa-solid fa-gauge-simple"></i> <?= __('minimum') ?> Requirements
                </button>
                <button class="tab-btn" data-tab-target="tab-rec">
                    <i class="fa-solid fa-gauge-high"></i> <?= __('recommended_req') ?> Requirements
                </button>
            </div>

            <!-- Minimum Requirements Pane -->
            <div class="tab-pane" id="tab-min">
                <div class="req-grid">
                    <div class="req-item">
                        <div class="req-label">Operating System</div>
                        <div class="req-val"><?= e($minReq['operating_system'] ?? 'Windows 10 64-bit') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Processor (CPU)</div>
                        <div class="req-val"><?= e($minReq['processor'] ?? 'Intel Core i5 / AMD Ryzen 3') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Memory (RAM)</div>
                        <div class="req-val"><?= e($minReq['memory'] ?? '8 GB RAM') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Graphics Card (GPU)</div>
                        <div class="req-val"><?= e($minReq['graphics'] ?? 'NVIDIA GTX 960 / AMD Radeon RX 470') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Storage Space</div>
                        <div class="req-val"><?= e($minReq['storage'] ?? '70 GB available space') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">DirectX</div>
                        <div class="req-val"><?= e($minReq['directx'] ?? 'DirectX 11') ?></div>
                    </div>
                </div>
            </div>

            <!-- Recommended Requirements Pane -->
            <div class="tab-pane" id="tab-rec" style="display:none;">
                <div class="req-grid">
                    <div class="req-item">
                        <div class="req-label">Operating System</div>
                        <div class="req-val"><?= e($recReq['operating_system'] ?? 'Windows 10 / 11 64-bit') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Processor (CPU)</div>
                        <div class="req-val"><?= e($recReq['processor'] ?? 'Intel Core i7 / AMD Ryzen 7') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Memory (RAM)</div>
                        <div class="req-val"><?= e($recReq['memory'] ?? '16 GB RAM') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Graphics Card (GPU)</div>
                        <div class="req-val"><?= e($recReq['graphics'] ?? 'NVIDIA RTX 2060 / AMD RX 5700') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">Storage Space</div>
                        <div class="req-val"><?= e($recReq['storage'] ?? '70 GB SSD space') ?></div>
                    </div>
                    <div class="req-item">
                        <div class="req-label">DirectX</div>
                        <div class="req-val"><?= e($recReq['directx'] ?? 'DirectX 12') ?></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Ratings & Reviews System -->
        <section style="margin-bottom:40px;">
            <h2 style="font-size:22px; font-weight:800; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-star text-amber-400"></i> Customer Reviews & Ratings
            </h2>

            <!-- Rating Overview & Breakdown -->
            <div class="review-stats-card">
                <div style="text-align:center; border-right:1px solid var(--border-color); padding-right:24px;">
                    <div class="big-rating-number"><?= number_format($game['rating'], 1) ?></div>
                    <?= render_stars($game['rating']) ?>
                    <div style="color:var(--text-muted); font-size:13px; margin-top:8px;"><?= $game['total_reviews'] ?> total ratings</div>
                </div>

                <div>
                    <?php 
                    $totalRevCount = max($game['total_reviews'], 1);
                    for ($s = 5; $s >= 1; $s--): 
                        $pct = ($starCounts[$s] / $totalRevCount) * 100;
                    ?>
                    <div class="rating-bar-row">
                        <span style="width:50px; font-weight:600;"><?= $s ?> Stars</span>
                        <div class="rating-progress-bar">
                            <div class="rating-progress-fill" style="width: <?= $pct ?>%;"></div>
                        </div>
                        <span style="width:30px; text-align:right; color:var(--text-muted);"><?= $starCounts[$s] ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Review Submission Form -->
            <?php if (is_logged_in()): ?>
                <div class="card" style="margin-bottom:28px; padding:28px;">
                    <h3 style="font-size:18px; font-weight:700; margin-bottom:16px;">
                        <?= $userReview ? 'Edit Your Review' : 'Write a Review for ' . e($game['game_name']) ?>
                    </h3>

                    <form method="POST" action="game-details.php?id=<?= $game['game_id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="rating" id="rating-input-value" value="<?= $userReview['rating'] ?? 5 ?>">

                        <div class="form-group">
                            <label class="form-label">Your Rating *</label>
                            <div id="review-star-picker" style="display:flex; gap:8px; font-size:24px; cursor:pointer;">
                                <?php for ($i = 1; $i <= 5; $i++): 
                                    $isSelected = $i <= ($userReview['rating'] ?? 5);
                                ?>
                                    <span class="star-option" data-value="<?= $i ?>">
                                        <i class="fa-<?= $isSelected ? 'solid text-amber-400' : 'regular text-slate-300' ?> fa-star"></i>
                                    </span>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Review Details *</label>
                            <textarea name="review_text" class="form-control" placeholder="Share your experience with gameplay, graphics, performance, and story..." required><?= e($userReview['review_text'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex; gap:12px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> <?= $userReview ? 'Update Review' : 'Submit Review' ?>
                            </button>
                            <?php if ($userReview): ?>
                                <button type="submit" name="action" value="delete_review" class="btn btn-secondary" style="color:var(--danger);" onclick="return confirm('Are you sure you want to delete your review?');">
                                    <i class="fa-solid fa-trash"></i> Delete Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card" style="text-align:center; padding:24px; margin-bottom:28px; background:var(--primary-light); border-color:var(--primary-subtle);">
                    <p style="font-weight:600; margin-bottom:12px;">Want to rate or review <?= e($game['game_name']) ?>?</p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Write a Review
                    </a>
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div>
                <?php if (empty($reviews)): ?>
                    <p style="color:var(--text-muted); font-style:italic;">No customer reviews yet. Be the first to leave a review!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <img src="<?= get_avatar($rev['profile_image'], $rev['user_name']) ?>" alt="Avatar" style="width:42px; height:42px; border-radius:var(--radius-full);">
                                    <div>
                                        <div style="font-weight:700; font-size:15px;"><?= e($rev['user_name']) ?></div>
                                        <div style="color:var(--text-muted); font-size:12px;"><?= date('F d, Y', strtotime($rev['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div>
                                    <?= render_stars($rev['rating']) ?>
                                </div>
                            </div>
                            <p style="color:var(--text-secondary); font-size:14.5px; line-height:1.6;">
                                "<?= nl2br(e($rev['review_text'])) ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- 4. 🎮 More Games in this Category -->
        <?php
        $relatedStmt = $pdo->prepare("
            SELECT g.*, c.category_name 
            FROM games g 
            JOIN categories c ON g.category_id = c.category_id 
            WHERE g.category_id = ? AND g.game_id != ? 
            ORDER BY g.rating DESC 
            LIMIT 4
        ");
        $relatedStmt->execute([$game['category_id'], $gameId]);
        $relatedGames = $relatedStmt->fetchAll();
        ?>
        <?php if (!empty($relatedGames)): ?>
        <section style="margin-top:40px; margin-bottom:20px; padding-top:28px; border-top:1px solid var(--border-color);">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:22px; font-weight:800;">More in <?= e($game['category_name']) ?></h2>
                    <p style="color:var(--text-muted); font-size:14px;">Other top-rated titles you might enjoy</p>
                </div>
                <a href="<?= BASE_URL ?>/customer/categories.php?id=<?= $game['category_id'] ?>" style="font-weight:700; font-size:14px;">
                    View All in <?= e($game['category_name']) ?> <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="games-grid">
                <?php foreach ($relatedGames as $rg): 
                    $isRgFav = is_game_favorited($pdo, $userId, $rg['game_id']);
                    $isRgOwned = is_game_owned($pdo, $userId, $rg['game_id']);
                    $isRgInCart = is_game_in_cart($pdo, $userId, $rg['game_id']);
                ?>
                <div class="game-card">
                    <div class="game-card-media">
                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $rg['game_id'] ?>">
                            <img src="<?= get_game_cover($rg['game_image'], $rg['game_name']) ?>" alt="<?= e($rg['game_name']) ?>" class="game-card-img" loading="lazy">
                        </a>
                        <span class="game-card-badge"><?= e($rg['category_name']) ?></span>
                        <button class="game-card-fav-btn <?= $isRgFav ? 'active' : '' ?>" data-game-id="<?= $rg['game_id'] ?>" title="<?= $isRgFav ? 'Remove Favorite' : 'Add Favorite' ?>">
                            <i class="<?= $isRgFav ? 'fa-solid fa-heart text-red-500' : 'fa-regular fa-heart' ?>"></i>
                        </button>
                    </div>
                    <div class="game-card-body">
                        <div class="game-card-category"><?= e($rg['genre']) ?></div>
                        <h3 class="game-card-title">
                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $rg['game_id'] ?>" style="color:inherit;"><?= e($rg['game_name']) ?></a>
                        </h3>
                        <div class="game-card-rating">
                            <?= render_stars($rg['rating']) ?>
                            <span>(<?= $rg['total_reviews'] ?>)</span>
                        </div>
                        <div class="game-card-footer">
                            <div class="game-card-price"><?= format_price($rg['price']) ?></div>
                            <?php if ($isRgOwned): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> In Library</span>
                            <?php elseif ($isRgInCart): ?>
                                <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-check"></i> In Cart
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm btn-add-cart" data-game-id="<?= $rg['game_id'] ?>">
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

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
