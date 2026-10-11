<?php
/**
 * Games Arena - Admin: Add New Game & System Requirements
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
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

        // Normalise YouTube URL to embed format if needed
        if (str_contains($trailerUrl, 'watch?v=')) {
            $trailerUrl = str_replace('watch?v=', 'embed/', $trailerUrl);
        }

        // Image upload handling
        $gameImage = sanitize($_POST['game_image_url'] ?? '');
        if (isset($_FILES['game_image_file']) && $_FILES['game_image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['game_image_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $targetDir = __DIR__ . '/../uploads/games/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $fileName = 'game_' . time() . '_' . rand(100, 999) . '.' . $ext;
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

                // Insert Game
                $stmt = $pdo->prepare("
                    INSERT INTO games (category_id, game_name, genre, description, developer, publisher, release_date, price, game_image, trailer_url, is_featured, rating, total_reviews)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 5.0, 0)
                ");
                $stmt->execute([$categoryId, $gameName, $genre, $description, $developer, $publisher, $releaseDate, $price, $gameImage, $trailerUrl, $isFeatured]);
                $newGameId = $pdo->lastInsertId();

                // Insert Minimum System Requirements
                $reqStmt = $pdo->prepare("
                    INSERT INTO game_requirements (game_id, requirement_type, operating_system, processor, memory, graphics, storage, directx)
                    VALUES (?, 'minimum', ?, ?, ?, ?, ?, ?)
                ");
                $reqStmt->execute([
                    $newGameId,
                    sanitize($_POST['min_os'] ?? 'Windows 10 64-bit'),
                    sanitize($_POST['min_cpu'] ?? 'Intel Core i5 / AMD Ryzen 3'),
                    sanitize($_POST['min_ram'] ?? '8 GB RAM'),
                    sanitize($_POST['min_gpu'] ?? 'NVIDIA GTX 960 / AMD RX 470'),
                    sanitize($_POST['min_storage'] ?? '70 GB available space'),
                    sanitize($_POST['min_dx'] ?? 'DirectX 11')
                ]);

                // Insert Recommended System Requirements
                $recStmt = $pdo->prepare("
                    INSERT INTO game_requirements (game_id, requirement_type, operating_system, processor, memory, graphics, storage, directx)
                    VALUES (?, 'recommended', ?, ?, ?, ?, ?, ?)
                ");
                $recStmt->execute([
                    $newGameId,
                    sanitize($_POST['rec_os'] ?? 'Windows 10 / 11 64-bit'),
                    sanitize($_POST['rec_cpu'] ?? 'Intel Core i7 / AMD Ryzen 7'),
                    sanitize($_POST['rec_ram'] ?? '16 GB RAM'),
                    sanitize($_POST['rec_gpu'] ?? 'NVIDIA RTX 2060 / AMD RX 5700'),
                    sanitize($_POST['rec_storage'] ?? '70 GB SSD space'),
                    sanitize($_POST['rec_dx'] ?? 'DirectX 12')
                ]);

                $pdo->commit();
                set_flash('success', 'Game "' . $gameName . '" added successfully!');
                header("Location: " . BASE_URL . "/admin/games.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to add game: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Add New Game — Games Arena Admin';
$activeNav = 'add_game';
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
                <i class="fa-solid fa-circle-plus text-blue-500"></i> Add New Game
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
            <h1 style="font-size:26px; font-weight:800;">Add New Game</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Enter game details, pricing, media assets, and hardware requirements</p>
        </div>

        <?php if ($error): ?>
            <div style="background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add-game.php" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:32px; align-items:flex-start;">
                
                <!-- Left: Core Game Metadata & Description -->
                <div>
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            Game Details
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Game Title *</label>
                            <input type="text" name="game_name" class="form-control" placeholder="e.g. Grand Theft Auto V" required value="<?= e($_POST['game_name'] ?? '') ?>">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Primary Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                            <?= e($cat['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Genre(s)</label>
                                <input type="text" name="genre" class="form-control" placeholder="e.g. Action, Open World, RPG" value="<?= e($_POST['genre'] ?? '') ?>">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Developer Studio</label>
                                <input type="text" name="developer" class="form-control" placeholder="e.g. Rockstar North" value="<?= e($_POST['developer'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Publisher</label>
                                <input type="text" name="publisher" class="form-control" placeholder="e.g. Rockstar Games" value="<?= e($_POST['publisher'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Comprehensive game overview, story, and gameplay mechanics..." required><?= e($_POST['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- System Requirements Section -->
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-microchip text-blue-500"></i> Minimum System Requirements
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Operating System</label>
                                <input type="text" name="min_os" class="form-control" value="Windows 10 64-bit">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Processor (CPU)</label>
                                <input type="text" name="min_cpu" class="form-control" value="Intel Core i5 / AMD Ryzen 3">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Memory (RAM)</label>
                                <input type="text" name="min_ram" class="form-control" value="8 GB RAM">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Graphics (GPU)</label>
                                <input type="text" name="min_gpu" class="form-control" value="NVIDIA GTX 960 / AMD RX 470">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Storage</label>
                                <input type="text" name="min_storage" class="form-control" value="70 GB available space">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DirectX</label>
                                <input type="text" name="min_dx" class="form-control" value="DirectX 11">
                            </div>
                        </div>
                    </div>

                    <!-- Recommended System Requirements Section -->
                    <div class="card" style="padding:28px; margin-bottom:28px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-gauge-high text-blue-500"></i> Recommended System Requirements
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Operating System</label>
                                <input type="text" name="rec_os" class="form-control" value="Windows 10 / 11 64-bit">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Processor (CPU)</label>
                                <input type="text" name="rec_cpu" class="form-control" value="Intel Core i7 / AMD Ryzen 7">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Memory (RAM)</label>
                                <input type="text" name="rec_ram" class="form-control" value="16 GB RAM">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Graphics (GPU)</label>
                                <input type="text" name="rec_gpu" class="form-control" value="NVIDIA RTX 2060 / AMD RX 5700">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Storage</label>
                                <input type="text" name="rec_storage" class="form-control" value="70 GB SSD space">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DirectX</label>
                                <input type="text" name="rec_dx" class="form-control" value="DirectX 12">
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
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="1499.00" required value="<?= e($_POST['price'] ?? '1499.00') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Official Release Date</label>
                            <input type="date" name="release_date" class="form-control" value="<?= e($_POST['release_date'] ?? date('Y-m-d')) ?>">
                        </div>

                        <div class="form-group">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600;">
                                <input type="checkbox" name="is_featured" value="1" style="accent-color:var(--primary); transform:scale(1.2);">
                                <span>Featured Hero Game</span>
                            </label>
                        </div>
                    </div>

                    <div class="card" style="padding:28px; margin-bottom:24px;">
                        <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                            Media & Artwork
                        </h3>

                        <div class="form-group">
                            <label class="form-label">Cover Artwork Upload</label>
                            <input type="file" name="game_image_file" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Or Image URL / Path</label>
                            <input type="text" name="game_image_url" class="form-control" placeholder="https://... or uploads/game.jpg">
                        </div>

                        <div class="form-group">
                            <label class="form-label">YouTube Trailer URL</label>
                            <input type="url" name="trailer_url" class="form-control" placeholder="https://www.youtube.com/embed/...">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%; font-size:16px;">
                        <i class="fa-solid fa-floppy-disk"></i> Publish Game
                    </button>
                </div>

            </div>
        </form>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
