<?php
/**
 * Games Arena - Admin: Review Moderation System
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$pdo = get_db();
$message = '';
$messageType = '';

// Handle Moderation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf()) {
        $message = 'Security validation failed.';
        $messageType = 'danger';
    } elseif ($_POST['action'] === 'toggle_visibility') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'visible');

        if ($reviewId > 0 && in_array($newStatus, ['visible', 'hidden'])) {
            $stmt = $pdo->prepare("SELECT game_id FROM reviews WHERE review_id = ?");
            $stmt->execute([$reviewId]);
            $gameId = (int)$stmt->fetchColumn();

            $update = $pdo->prepare("UPDATE reviews SET status = ? WHERE review_id = ?");
            $update->execute([$newStatus, $reviewId]);

            if ($gameId > 0) {
                recalculate_game_rating($pdo, $gameId);
            }

            $message = 'Review visibility updated to ' . ucfirst($newStatus) . '.';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'delete_review') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $stmt = $pdo->prepare("SELECT game_id FROM reviews WHERE review_id = ?");
            $stmt->execute([$reviewId]);
            $gameId = (int)$stmt->fetchColumn();

            $del = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
            $del->execute([$reviewId]);

            if ($gameId > 0) {
                recalculate_game_rating($pdo, $gameId);
            }

            $message = 'Inappropriate review deleted permanently and game ratings re-computed.';
            $messageType = 'info';
        }
    }
}

// Fetch all reviews with user and game details
$sql = "
    SELECT r.*, g.game_name, g.game_image, u.name as reviewer_name, u.email as reviewer_email 
    FROM reviews r 
    JOIN games g ON r.game_id = g.game_id 
    JOIN users u ON r.user_id = u.user_id 
    ORDER BY r.created_at DESC
";
$reviews = $pdo->query($sql)->fetchAll();

$pageTitle = 'Moderate Reviews — Games Arena Admin';
$activeNav = 'reviews';
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
                <i class="fa-solid fa-star-half-stroke text-amber-500"></i> Customer Reviews Moderation
            </div>
        </div>
    </header>

    <div class="page-body">
        <div style="margin-bottom:28px;">
            <h1 style="font-size:26px; font-weight:800;">Review Moderation</h1>
            <p style="color:var(--text-muted); font-size:14.5px;">Moderate user reviews, hide spam, or delete inappropriate submissions</p>
        </div>

        <?php if ($message): ?>
            <div style="background:<?= $messageType === 'success' ? '#ECFDF5' : '#FEF2F2' ?>; border:1px solid <?= $messageType === 'success' ? '#A7F3D0' : '#FECACA' ?>; color:<?= $messageType === 'success' ? '#065F46' : '#991B1B' ?>; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:600; display:flex; align-items:center; gap:12px;">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <!-- Reviews Table -->
        <div class="card" style="padding:0; overflow:hidden;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Game Title</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Review Details</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted);">No reviews submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?= get_game_cover($r['game_image'], $r['game_name']) ?>" alt="<?= e($r['game_name']) ?>" style="width:50px; height:32px; object-fit:cover; border-radius:4px;">
                                        <a href="<?= BASE_URL ?>/customer/game-details.php?id=<?= $r['game_id'] ?>" target="_blank" style="font-weight:700; color:var(--text-primary);">
                                            <?= e($r['game_name']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?= e($r['reviewer_name']) ?></div>
                                    <div style="font-size:11.5px; color:var(--text-muted);"><?= e($r['reviewer_email']) ?></div>
                                </td>
                                <td>
                                    <?= render_stars($r['rating']) ?>
                                </td>
                                <td style="max-width:320px; font-size:13.5px; color:var(--text-secondary); line-height:1.5;">
                                    "<?= nl2br(e($r['review_text'])) ?>"
                                </td>
                                <td style="font-size:12.5px; color:var(--text-muted);">
                                    <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $r['status'] === 'visible' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst(e($r['status'])) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:8px;">
                                        <!-- Toggle Visibility Form -->
                                        <form method="POST" action="reviews.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_visibility">
                                            <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                            <input type="hidden" name="status" value="<?= $r['status'] === 'visible' ? 'hidden' : 'visible' ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="<?= $r['status'] === 'visible' ? 'Hide Review' : 'Unhide Review' ?>">
                                                <i class="fa-solid <?= $r['status'] === 'visible' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete Review Form -->
                                        <form method="POST" action="reviews.php" style="display:inline;" onsubmit="return confirm('Permanently delete this review?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_review">
                                            <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger);" title="Delete Review">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
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
