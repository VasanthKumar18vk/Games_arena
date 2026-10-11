<?php
/**
 * Games Arena - Admin: Game of the Year (15-Year Management)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$message = '';
$messageType = '';

// Handle GOTY Add / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'save_goty') {
        $year = (int)($_POST['year'] ?? 0);
        $gameId = (int)($_POST['game_id'] ?? 0);
        $desc = sanitize($_POST['description'] ?? '');

        if ($year >= 2000 && $year <= 2030 && $gameId > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO game_of_the_year (year, game_id, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE game_id = VALUES(game_id), description = VALUES(description)
            ");
            $stmt->execute([$year, $gameId, $desc]);
            $message = 'Game of the Year for ' . $year . ' saved successfully!';
            $messageType = 'success';
        } else {
            $message = 'Please specify a valid year and select a game.';
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete_goty') {
        $year = (int)($_POST['year'] ?? 0);
        if ($year > 0) {
            $stmt = $pdo->prepare("DELETE FROM game_of_the_year WHERE year = ?");
            $stmt->execute([$year]);
            $message = 'GOTY record for ' . $year . ' removed.';
            $messageType = 'info';
        }
    }
}

// Fetch GOTY entries
$gotyEntries = $pdo->query("
    SELECT goty.*, g.game_name, g.game_image, c.category_name 
    FROM game_of_the_year goty
    JOIN games g ON goty.game_id = g.game_id
    JOIN categories c ON g.category_id = c.category_id
    ORDER BY goty.year DESC
")->fetchAll();

$games = $pdo->query("SELECT game_id, game_name FROM games ORDER BY game_name ASC")->fetchAll();

$pageTitle = 'Manage GOTY Awards — Games Arena Admin';
$activeNav = 'goty';
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
                <i class="fa-solid fa-trophy text-amber-500"></i> Game of the Year (GOTY) Management
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Game of the Year Hall of Fame</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Assign, update, or write accolades for GOTY awards over the past 15 years</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:32px; align-items:flex-start;">
            
            <!-- Left: GOTY Table -->
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>GOTY Winner Game</th>
                            <th>Category</th>
                            <th>Accolade / Description</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gotyEntries as $goty): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-warning" style="font-size:14px; font-family:'Outfit',sans-serif;">
                                        <i class="fa-solid fa-crown"></i> <?= e($goty['year']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?= get_game_cover($goty['game_image'], $goty['game_name']) ?>" alt="<?= e($goty['game_name']) ?>" style="width:50px; height:32px; object-fit:cover; border-radius:4px;">
                                        <strong style="color:var(--text-primary);"><?= e($goty['game_name']) ?></strong>
                                    </div>
                                </td>
                                <td><span class="badge badge-primary"><?= e($goty['category_name']) ?></span></td>
                                <td style="max-width:280px; font-size:13px; color:var(--text-secondary);">
                                    <?= e($goty['description']) ?>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" action="goty.php" style="display:inline;" onsubmit="return confirm('Remove GOTY entry for <?= $goty['year'] ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_goty">
                                        <input type="hidden" name="year" value="<?= $goty['year'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right: Assign GOTY Form -->
            <div class="card" style="padding:28px;">
                <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-award text-amber-500"></i> Assign GOTY Winner
                </h3>

                <form method="POST" action="goty.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_goty">

                    <div class="form-group">
                        <label class="form-label">Award Year *</label>
                        <select name="year" class="form-control" required>
                            <?php for ($y = 2026; $y >= 2011; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Winning Game Title *</label>
                        <select name="game_id" class="form-control" required>
                            <option value="">Select Game</option>
                            <?php foreach ($games as $g): ?>
                                <option value="<?= $g['game_id'] ?>"><?= e($g['game_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Accolade Citation / Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Celebrated for groundbreaking story and artistic excellence..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fa-solid fa-trophy"></i> Save GOTY Winner
                    </button>
                </form>
            </div>

        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
