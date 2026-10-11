<?php
/**
 * Games Arena - Admin: Category Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$message = '';
$messageType = '';

// Handle Category Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'add_category') {
        $name = sanitize($_POST['category_name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-gamepad');
        $desc = sanitize($_POST['description'] ?? '');

        if (empty($name)) {
            $message = 'Category name is required.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, icon, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $icon, $desc]);
                $message = 'Category "' . $name . '" created successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error creating category: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    } elseif ($_POST['action'] === 'edit_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['category_name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-gamepad');
        $desc = sanitize($_POST['description'] ?? '');

        if ($catId > 0 && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, icon = ?, description = ? WHERE category_id = ?");
            $stmt->execute([$name, $icon, $desc, $catId]);
            $message = 'Category updated successfully!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'delete_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            // Check if games exist in category
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM games WHERE category_id = ?");
            $stmt->execute([$catId]);
            $count = (int)$stmt->fetchColumn();

            if ($count > 0) {
                $message = 'Cannot delete category: ' . $count . ' game(s) are assigned to it. Re-assign or remove those games first.';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$catId]);
                $message = 'Category deleted successfully.';
                $messageType = 'info';
            }
        }
    }
}

// Fetch categories with game count
$categories = $pdo->query("
    SELECT c.*, COUNT(g.game_id) as game_count 
    FROM categories c 
    LEFT JOIN games g ON c.category_id = g.category_id 
    GROUP BY c.category_id 
    ORDER BY c.category_name ASC
")->fetchAll();

$pageTitle = 'Manage Categories — Games Arena Admin';
$activeNav = 'categories';
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
                <i class="fa-solid fa-layer-group text-blue-500"></i> Category Management
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Categories</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Manage game classifications, icons, and descriptions</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:32px; align-items:flex-start;">
            
            <!-- Left: Categories List -->
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Games Assigned</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="width:36px; height:36px; background:var(--primary-light); color:var(--primary); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px;">
                                            <i class="fa-solid <?= e($c['icon'] ?: 'fa-gamepad') ?>"></i>
                                        </div>
                                        <strong style="font-size:15px;"><?= e($c['category_name']) ?></strong>
                                    </div>
                                </td>
                                <td style="color:var(--text-secondary); max-width:280px; font-size:13px;">
                                    <?= e($c['description']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= $c['game_count'] ?> games</span>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" action="categories.php" style="display:inline;" onsubmit="return confirm('Delete category <?= addslashes(e($c['category_name'])) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);" <?= $c['game_count'] > 0 ? 'disabled title="Cannot delete category with games"' : '' ?>>
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Right: Add Category Form -->
            <div class="card" style="padding:28px;">
                <h3 style="font-size:17px; font-weight:800; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
                    Add New Category
                </h3>

                <form method="POST" action="categories.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_category">

                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Battle Royale, Stealth" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">FontAwesome Icon Class</label>
                        <input type="text" name="icon" class="form-control" value="fa-gamepad" placeholder="e.g. fa-fire, fa-dragon">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Category highlights..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fa-solid fa-plus"></i> Create Category
                    </button>
                </form>
            </div>

        </div>

    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
