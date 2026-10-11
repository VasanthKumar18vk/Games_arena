<?php
/**
 * Games Arena - Admin Sidebar Navigation
 */
$activePage = $activeNav ?? 'dashboard';
?>
<aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="brand-logo">
            <div class="logo-icon" style="background: linear-gradient(135deg, #1E40AF 0%, #2563EB 100%);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span>ADMIN <span class="accent-text">PORTAL</span></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Store Management</div>

        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/games.php" class="nav-item <?= in_array($activePage, ['games', 'add_game', 'edit_game']) ? 'active' : '' ?>">
            <i class="fa-solid fa-gamepad"></i>
            <span>Games</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/categories.php" class="nav-item <?= $activePage === 'categories' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Categories</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/orders.php" class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-flatbed"></i>
            <span>Orders</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/reviews.php" class="nav-item <?= $activePage === 'reviews' ? 'active' : '' ?>">
            <i class="fa-solid fa-star-half-stroke"></i>
            <span>Reviews</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/goty.php" class="nav-item <?= $activePage === 'goty' ? 'active' : '' ?>">
            <i class="fa-solid fa-trophy"></i>
            <span>Game of the Year</span>
        </a>

        <div class="nav-section-title">System & View</div>

        <a href="<?= BASE_URL ?>/admin/settings.php" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders"></i>
            <span>Settings</span>
        </a>

        <a href="<?= BASE_URL ?>/customer/dashboard.php" class="nav-item" style="color:var(--primary);">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Customer View</span>
        </a>

        <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="color:var(--danger);">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>

    <?php if ($user): ?>
        <div class="sidebar-footer">
            <div class="user-snippet">
                <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'Admin') ?>" alt="Admin Avatar" class="user-avatar">
                <div class="user-info">
                    <div class="user-name"><?= e($user['name'] ?? 'Administrator') ?></div>
                    <div class="user-role" style="color:var(--primary); font-weight:700;">Admin</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</aside>
