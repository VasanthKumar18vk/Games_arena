<?php
/**
 * Games Arena - Admin: Edit Game & System Requirements
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$gameId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($gameId <= 0) {
    header("Location: " . BASE_URL . "/admin/games.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE game_id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    set_flash('danger', 'Game not found.');
    header("Location: " . BASE_URL . "/admin/games.php");
    exit;
}

// Fetch System Requirements
$reqStmt = $pdo->prepare("SELECT * FROM game_requirements WHERE game_id = ?");
$reqStmt->execute([$gameId]);
$reqs = $reqStmt->fetchAll();
$minReq = null;
$recReq = null;
foreach ($reqs as $r) {
    if ($r['requirement_type'] === 'minimum') $minReq = $r;
    if ($r['requirement_type'] === 'recommended') $recReq = $r;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Security validation failed.';
    } else {
        $gameName = sanitize($_POST['game_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $genre = sanitize($_POST['genre'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $developer = sanitize($_POST['developer'] ?? '');
        $publisher = sanitize($_POST['publisher'] ?? '');
        $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
        $price = (float)($_POST['price'] ?? 0.0);
        $trailerUrl = sanitize($_POST['trailer_url'] ?? '');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        if (str_contains($trailerUrl, 'watch?v=')) {
            $trailerUrl = str_replace('watch?v=', 'embed/', $trailerUrl);
        }

        // Image upload handling
        $gameImage = $game['game_image'];
        if (!empty($_POST['game_image_url'])) {
            $gameImage = sanitize($_POST['game_image_url']);
        }
        if (isset($_FILES['game_image_file']) && $_FILES['game_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['game_image_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $targetDir = __DIR__ . '/../uploads/games/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $fileName = 'game_' . $gameId . '_' . time() . '.' . $ext;
                $targetPath = $targetDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $gameImage = 'uploads/games/' . $fileName;
                }
            }
        }

        if (empty($gameName) || $categoryId <= 0) {
            $error = 'Please enter a game name and select a valid category.';
        } else {
            try {
                $pdo->beginTransaction();

                // Update Game
                $stmt = $pdo->prepare("
                    UPDATE games 
                    SET category_id = ?, game_name = ?, genre = ?, description = ?, developer = ?, publisher = ?, 
                        release_date = ?, price = ?, game_image = ?, trailer_url = ?, is_featured = ?
                    WHERE game_id = ?
                ");
                $stmt->execute([$categoryId, $gameName, $genre, $description, $developer, $publisher, $releaseDate, $price, $gameImage, $trailerUrl, $isFeatured, $gameId]);

                // Upsert Minimum System Requirements
                $stmt = $pdo->prepare("
                    INSERT INTO game_requirements (game_id, requirement_type, operating_system, processor, memory, graphics, storage, directx)
                    VALUES (?, 'minimum', ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        operating_system = VALUES(operating_system), processor = VALUES(processor),
                        memory = VALUES(memory), graphics = VALUES(graphics),
                        storage = VALUES(storage), directx = VALUES(directx)
                ");
                $stmt->execute([
                    $gameId,
                    sanitize($_POST['min_os'] ?? ''),
                    sanitize($_POST['min_cpu'] ?? ''),
                    sanitize($_POST['min_ram'] ?? ''),
                    sanitize($_POST['min_gpu'] ?? ''),
                    sanitize($_POST['min_storage'] ?? ''),
                    sanitize($_POST['min_dx'] ?? '')
                ]);

                // Upsert Recommended System Requirements
                $stmt = $pdo->prepare("
                    INSERT INTO game_requirements (game_id, requirement_type, operating_system, processor, memory, graphics, storage, directx)
                    VALUES (?, 'recommended', ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        operating_system = VALUES(operating_system), processor = VALUES(processor),
                        memory = VALUES(memory), graphics = VALUES(graphics),
                        storage = VALUES(storage), directx = VALUES(directx)
                ");
                $stmt->execute([
                    $gameId,
                    sanitize($_POST['rec_os'] ?? ''),
                    sanitize($_POST['rec_cpu'] ?? ''),
                    sanitize($_POST['rec_ram'] ?? ''),
                    sanitize($_POST['rec_gpu'] ?? ''),
                    sanitize($_POST['rec_storage'] ?? ''),
                    sanitize($_POST['rec_dx'] ?? '')
                ]);

                $pdo->commit();
                set_flash('success', 'Game "' . $gameName . '" updated successfully!');
                header("Location: " . BASE_URL . "/admin/games.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to update game: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit ' . e($game['game_name']) . ' — Admin';
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
                <i class="fa-solid fa-pen-to-square text-blue-500"></i> Edit Game: <?= e($game['game_name']) ?>
            </div>
        </div>

        <div class="header-actions">
            <a href="<?= BASE_URL ?>/admin/games.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to Games
            </a>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Edit Game: <?= e($game['game_name']) ?></h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Modify game metadata, pricing, cover image, and hardware requirements</p>
        </div>

        <?php if ($error): ?>
            <div style="background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit-game.php?id=<?= $gameId ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:32px; align-items:flex-start;">
                
                <!-- Left: Core Game Info -->
                <div>
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            Game Details
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Game Title *</label>
                            <input type="text" name="game_name" class="form-control" required value="<?= e($game['game_name']) ?>">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Primary Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>" <?= $game['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                            <?= e($cat['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Genre(s)</label>
                                <input type="text" name="genre" class="form-control" value="<?= e($game['genre']) ?>">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Developer Studio</label>
                                <input type="text" name="developer" class="form-control" value="<?= e($game['developer']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Publisher</label>
                                <input type="text" name="publisher" class="form-control" value="<?= e($game['publisher']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required><?= e($game['description']) ?></textarea>
                        </div>
                    </div>

                    <!-- Minimum System Requirements -->
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-microchip text-blue-500"></i> Minimum System Requirements
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Operating System</label>
                                <input type="text" name="min_os" class="form-control" value="<?= e($minReq['operating_system'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Processor (CPU)</label>
                                <input type="text" name="min_cpu" class="form-control" value="<?= e($minReq['processor'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Memory (RAM)</label>
                                <input type="text" name="min_ram" class="form-control" value="<?= e($minReq['memory'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Graphics (GPU)</label>
                                <input type="text" name="min_gpu" class="form-control" value="<?= e($minReq['graphics'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Storage</label>
                                <input type="text" name="min_storage" class="form-control" value="<?= e($minReq['storage'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DirectX</label>
                                <input type="text" name="min_dx" class="form-control" value="<?= e($minReq['directx'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Recommended System Requirements -->
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-gauge-high text-blue-500"></i> Recommended System Requirements
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Operating System</label>
                                <input type="text" name="rec_os" class="form-control" value="<?= e($recReq['operating_system'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Processor (CPU)</label>
                                <input type="text" name="rec_cpu" class="form-control" value="<?= e($recReq['processor'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Memory (RAM)</label>
                                <input type="text" name="rec_ram" class="form-control" value="<?= e($recReq['memory'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Graphics (GPU)</label>
                                <input type="text" name="rec_gpu" class="form-control" value="<?= e($recReq['graphics'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Storage</label>
                                <input type="text" name="rec_storage" class="form-control" value="<?= e($recReq['storage'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DirectX</label>
                                <input type="text" name="rec_dx" class="form-control" value="<?= e($recReq['directx'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Pricing, Media, Actions -->
                <div>
                    <div class="card" style="padding:28px; margin-bottom:24px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            Pricing & Release
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Price (in ₹ INR) *</label>
                            <input type="number" step="0.01" name="price" class="form-control" required value="<?= e($game['price']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Official Release Date</label>
                            <input type="date" name="release_date" class="form-control" value="<?= e($game['release_date']) ?>">
                        </div>

                        <div class="form-group">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600;">
                                <input type="checkbox" name="is_featured" value="1" <?= $game['is_featured'] ? 'checked' : '' ?> style="accent-color:var(--primary); transform:scale(1.2);">
                                <span>Featured Hero Game</span>
                            </label>
                        </div>
                    </div>

                    <div class="card" style="padding:28px; margin-bottom:24px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            Media & Artwork
                        </h3>

                        <div style="margin-bottom:16px;">
                            <img src="<?= get_game_cover($game['game_image'], $game['game_name']) ?>" style="width:100%; height:140px; object-fit:cover; border-radius:var(--radius-sm); margin-bottom:8px;">
                            <small style="color:var(--text-muted);">Current Cover Artwork</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Replace Artwork File</label>
                            <input type="file" name="game_image_file" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Or Image URL / Path</label>
                            <input type="text" name="game_image_url" class="form-control" value="<?= e($game['game_image']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">YouTube Trailer URL</label>
                            <input type="url" name="trailer_url" class="form-control" value="<?= e($game['trailer_url']) ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-size:16px;">
                        <i class="fa-solid fa-floppy-disk"></i> Update Game
                    </button>
                </div>

            </div>
        </form>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
