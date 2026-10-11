<?php
/**
 * Games Arena - Helper & Utility Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Format currency price (e.g. ₹1,499)
 */
function format_price($amount): string {
    $num = (float)$amount;
    if ($num <= 0) {
        return 'Free to Play';
    }
    return APP_CURRENCY . number_format($num, 2);
}

/**
 * Render visual star icons for ratings
 */
function render_stars(float $rating, bool $showNumber = false): string {
    $rating = round($rating, 1);
    $fullStars = floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);

    $html = '<div class="star-rating" title="' . $rating . ' / 5">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fa-solid fa-star text-amber-400"></i>';
    }
    if ($hasHalf) {
        $html .= '<i class="fa-solid fa-star-half-stroke text-amber-400"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="fa-regular fa-star text-slate-300"></i>';
    }
    if ($showNumber) {
        $html .= '<span class="rating-number font-bold ml-1">' . number_format($rating, 1) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Recalculate average rating & review count for a game
 */
function recalculate_game_rating(PDO $pdo, int $gameId): void {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE game_id = ? AND status = 'visible'");
    $stmt->execute([$gameId]);
    $stats = $stmt->fetch();

    $avg = $stats ? round((float)($stats['avg_rating'] ?? 0), 1) : 0.0;
    $count = $stats ? (int)($stats['total_reviews'] ?? 0) : 0;

    $update = $pdo->prepare("UPDATE games SET rating = ?, total_reviews = ? WHERE game_id = ?");
    $update->execute([$avg, $count, $gameId]);
}

/**
 * Check if a game is in customer's favorites (authenticated or guest session)
 */
function is_game_favorited(PDO $pdo, ?int $userId, int $gameId): bool {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$userId, $gameId]);
        return (bool)$stmt->fetch();
    }
    return !empty($_SESSION['favorites'][$gameId]);
}

/**
 * Check if a user already owns a game (digital license)
 */
function is_game_owned(PDO $pdo, ?int $userId, int $gameId): bool {
    if (!$userId) return false;
    $stmt = $pdo->prepare("
        SELECT 1 FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.user_id = ? AND oi.game_id = ? AND o.order_status IN ('completed', 'processing')
    ");
    $stmt->execute([$userId, $gameId]);
    return (bool)$stmt->fetch();
}

/**
 * Check if a game is in customer's cart (authenticated or guest session)
 */
function is_game_in_cart(PDO $pdo, ?int $userId, int $gameId): bool {
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT 1 FROM cart_items ci
            JOIN carts c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ? AND ci.game_id = ?
        ");
        $stmt->execute([$userId, $gameId]);
        return (bool)$stmt->fetch();
    }
    return !empty($_SESSION['cart'][$gameId]);
}

/**
 * Get total items count in user's cart (authenticated or guest session)
 */
function get_cart_count(PDO $pdo, ?int $userId): int {
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(ci.cart_item_id) as count
            FROM cart_items ci
            JOIN carts c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $res = $stmt->fetch();
        return $res ? (int)$res['count'] : 0;
    }
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}

/**
 * Get total favorite items count (authenticated or guest session)
 */
function get_favorites_count(PDO $pdo, ?int $userId): int {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $res = $stmt->fetch();
        return $res ? (int)$res['count'] : 0;
    }
    return isset($_SESSION['favorites']) && is_array($_SESSION['favorites']) ? count($_SESSION['favorites']) : 0;
}

/**
 * Get all cart items with game details (for logged in or guest session)
 */
function get_user_cart(PDO $pdo, ?int $userId): array {
    if ($userId) {
        // Ensure cart exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO carts (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("
            SELECT ci.cart_item_id, ci.quantity, ci.added_at,
                   g.game_id, g.game_name, g.genre, g.price, g.game_image, g.rating,
                   c.category_name
            FROM cart_items ci
            JOIN carts ct ON ci.cart_id = ct.cart_id
            JOIN games g ON ci.game_id = g.game_id
            JOIN categories c ON g.category_id = c.category_id
            WHERE ct.user_id = ?
            ORDER BY ci.added_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Guest cart from session
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    $gameIds = array_map('intval', array_keys($_SESSION['cart']));
    $gameIds = array_filter($gameIds, fn($id) => $id > 0);
    if (empty($gameIds)) return [];

    $placeholders = implode(',', array_fill(0, count($gameIds), '?'));
    $stmt = $pdo->prepare("
        SELECT g.game_id as cart_item_id, 1 as quantity, NOW() as added_at,
               g.game_id, g.game_name, g.genre, g.price, g.game_image, g.rating,
               c.category_name
        FROM games g
        JOIN categories c ON g.category_id = c.category_id
        WHERE g.game_id IN ($placeholders)
        ORDER BY g.game_name ASC
    ");
    $stmt->execute($gameIds);
    return $stmt->fetchAll();
}

/**
 * Get cart subtotal
 */
function get_cart_total(PDO $pdo, ?int $userId): float {
    $items = get_user_cart($pdo, $userId);
    $total = 0.0;
    foreach ($items as $item) {
        $total += (float)$item['price'] * (int)($item['quantity'] ?? 1);
    }
    return $total;
}

/**
 * Sync guest session cart and favorites to user's database records upon login/registration
 */
function sync_guest_data_to_user(PDO $pdo, int $userId): void {
    if (!$userId) return;

    // 1. Sync session cart
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO carts (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            $cartId = (int)$cart['cart_id'];
            $itemStmt = $pdo->prepare("
                INSERT INTO cart_items (cart_id, game_id, quantity) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = 1
            ");
            foreach ($_SESSION['cart'] as $gameId => $qty) {
                $gId = (int)$gameId;
                if ($gId > 0 && !is_game_owned($pdo, $userId, $gId)) {
                    $itemStmt->execute([$cartId, $gId]);
                }
            }
        }
        $_SESSION['cart'] = [];
    }

    // 2. Sync session favorites
    if (!empty($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
        $favStmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, game_id) VALUES (?, ?)");
        foreach ($_SESSION['favorites'] as $gameId => $val) {
            $gId = (int)$gameId;
            if ($gId > 0 && $val) {
                $favStmt->execute([$userId, $gId]);
            }
        }
        $_SESSION['favorites'] = [];
    }
}

/**
 * Get game cover URL or dynamic modern SVG fallback
 */
function get_game_cover(?string $imagePath, string $gameName = 'Game'): string {
    if (!empty($imagePath)) {
        // If external URL
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }
        $localPath = __DIR__ . '/../' . ltrim($imagePath, '/');
        if (file_exists($localPath)) {
            return BASE_URL . '/' . ltrim($imagePath, '/');
        }
    }
    
    // Dynamic stylized SVG gaming cover with gradient
    $colors = [
        ['#1E3A8A', '#3B82F6'],
        ['#312E81', '#6366F1'],
        ['#14532D', '#22C55E'],
        ['#701A75', '#EC4899'],
        ['#7C2D12', '#F97316'],
        ['#164E63', '#06B6D4'],
    ];
    $idx = abs(crc32($gameName)) % count($colors);
    $c1 = $colors[$idx][0];
    $c2 = $colors[$idx][1];
    $shortName = htmlspecialchars(mb_strimwidth($gameName, 0, 24, '...'));

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">'
         . '<defs>'
         . '<linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">'
         . '<stop offset="0%" style="stop-color:' . $c1 . ';stop-opacity:1" />'
         . '<stop offset="100%" style="stop-color:' . $c2 . ';stop-opacity:1" />'
         . '</linearGradient>'
         . '<pattern id="pattern" width="40" height="40" patternUnits="userSpaceOnUse">'
         . '<path d="M 40 0 L 0 40 M 0 0 L 40 40" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="1.5" />'
         . '</pattern>'
         . '</defs>'
         . '<rect width="600" height="400" fill="url(#grad)" />'
         . '<rect width="600" height="400" fill="url(#pattern)" />'
         . '<circle cx="300" cy="170" r="60" fill="rgba(255,255,255,0.12)" />'
         . '<text x="300" y="185" font-family="Arial, sans-serif" font-size="50" font-weight="bold" fill="#ffffff" text-anchor="middle" opacity="0.9">🎮</text>'
         . '<text x="300" y="270" font-family="system-ui, sans-serif" font-size="24" font-weight="bold" fill="#ffffff" text-anchor="middle">' . $shortName . '</text>'
         . '<text x="300" y="300" font-family="system-ui, sans-serif" font-size="14" font-weight="600" fill="rgba(255,255,255,0.7)" text-anchor="middle">GAMES ARENA</text>'
         . '</svg>';

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/**
 * Get user avatar URL or initials avatar fallback
 */
function get_avatar(?string $avatarPath, string $name = 'User'): string {
    if (!empty($avatarPath)) {
        if (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')) {
            return $avatarPath;
        }
        $localPath = __DIR__ . '/../' . ltrim($avatarPath, '/');
        if (file_exists($localPath)) {
            return BASE_URL . '/' . ltrim($avatarPath, '/');
        }
    }
    
    // Generate initials SVG avatar
    $initials = strtoupper(substr($name, 0, 2));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">'
         . '<rect width="100" height="100" rx="50" fill="#2563EB" />'
         . '<text x="50" y="62" font-family="system-ui, sans-serif" font-size="38" font-weight="bold" fill="#FFFFFF" text-anchor="middle">' . $initials . '</text>'
         . '</svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/**
 * Set flash alert message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve & clear flash alert message
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
