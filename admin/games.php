<?php
/**
 * Games Arena - Admin Games List & Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();

$search = sanitize($_GET['search'] ?? '');
$categoryFilter = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(g.game_name LIKE ? OR g.genre LIKE ? OR g.developer LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($categoryFilter > 0) {
    $where[] = "g.category_id = ?";
    $params[] = $categoryFilter;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$sql = "
    SELECT g.*, c.category_name,
           (SELECT COUNT(*) FROM order_items WHERE game_id = g.game_id) as sales_count
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY g.game_id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();

$pageTitle = 'Manage Games — Games Arena Admin';
$activeNav = 'games';
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
                <i class="fa-solid fa-gamepad text-blue-500"></i> Games Catalog Management
            </div>
        </div>

        <div class="header-actions">
            <button class="action-btn" id="theme-toggle-btn" title="Toggle Theme">
                <i class="fa-solid <?= $userTheme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>

            <a href="<?= BASE_URL ?>/admin/add-game.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add New Game
            </a>
        </div>
    </header>

    <div class="page-body">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:28px;">
            <div>
                <h1 style="font-size:26px; font-weight:800;">Games Catalog</h1>
                <p style="color:var(--text-muted); font-size:14.5px;">Add, edit, update system requirements, or delete games</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/add-game.php" class="btn btn-primary">
                <i class="fa-solid fa-circle-plus"></i> Add New Game
            </a>
        </div>

        <?php $flash = get_flash(); if ($flash): ?>
            <div style="background:<?= $flash['type'] === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $flash['type'] === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $flash['type'] === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Controls -->
        <div class="card" style="margin-bottom:24px; padding:16px 20px;">
            <form method="GET" action="games.php" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <div style="flex:2; min-width:200px;">
                    <input type="text" name="search" value="<?= e($search) ?>" class="form-control" placeholder="Search by game name, developer...">
                </div>

                <div style="flex:1; min-width:160px;">
                    <select name="category" class="form-control">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= $categoryFilter == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="height:42px; padding:0 20px;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="games.php" class="btn btn-secondary btn-sm" style="height:42px;" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </form>
        </div>

        <!-- Games Table -->
        <div class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Game Cover & Title</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Rating</th>
                        <th>Sales</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($games)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">No games found matching filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($games as $g): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:14px;">
                                        <img src="<?= get_game_cover($g['game_image'], $g['game_name']) ?>" alt="<?= e($g['game_name']) ?>" style="width:64px; height:40px; object-fit:cover; border-radius:6px;">
                                        <div>
                                            <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $g['game_id'] ?>" target="_blank" style="font-weight:700; color:var(--text-primary);">
                                                <?= e($g['game_name']) ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px; opacity:0.6;"></i>
                                            </a>
                                            <div style="font-size:12px; color:var(--text-muted);"><?= e($g['genre']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-primary"><?= e($g['category_name']) ?></span></td>
                                <td style="font-family:'Outfit',sans-serif; font-size:16px; font-weight:800; color:var(--primary);">
                                    <?= format_price($g['price']) ?>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span style="font-weight:700; color:#F59E0B;"><i class="fa-solid fa-star"></i> <?= number_format($g['rating'], 1) ?></span>
                                        <small style="color:var(--text-muted);">(<?= $g['total_reviews'] ?>)</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?= $g['sales_count'] ?> sales</span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:8px;">
                                        <a href="<?= BASE_URL ?>/admin/edit-game.php?id=<?= $g['game_id'] ?>" class="btn btn-secondary btn-sm" title="Edit Game">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/delete-game.php?id=<?= $g['game_id'] ?>" class="btn btn-secondary btn-sm" style="color:var(--danger);" onclick="return confirm('Are you sure you want to delete <?= addslashes(e($g['game_name'])) ?>?');" title="Delete Game">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
