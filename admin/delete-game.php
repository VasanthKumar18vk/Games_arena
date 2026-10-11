<?php
/**
 * Games Arena - Admin: Delete Game Handler
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

// Check if game has been purchased in any past orders (Rule 7)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE game_id = ?");
$stmt->execute([$gameId]);
$orderedCount = (int)$stmt->fetchColumn();

if ($orderedCount > 0) {
    set_flash('danger', 'Cannot delete this game because customers have already purchased digital licenses for it (Rule 7: Preserving order history).');
} else {
    try {
        $stmt = $pdo->prepare("DELETE FROM games WHERE game_id = ?");
        $stmt->execute([$gameId]);
        set_flash('success', 'Game has been permanently removed from the store.');
    } catch (Exception $e) {
        set_flash('danger', 'Failed to delete game: ' . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "/admin/games.php");
exit;
