<?php
/**
 * Games Arena - Customer Sidebar Navigation
 */
$currentRole = $_SESSION['user_role'] ?? 'guest';
$activePage = $activeNav ?? 'home';
?>
<aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
            <div class="logo-icon">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <span>GAMES <span class="accent-text">ARENA</span></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Discover</div>
        
        <a href="<?= BASE_URL ?>/index.php" class="nav-item <?= $activePage === 'home' ? 'active' : '' ?>">
            <i class="fa-solid fa-store"></i>
            <span>Store Front</span>
        </a>

        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/customer/dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-house-chimney"></i>
                <span><?= __('home') ?> (Dashboard)</span>
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/customer/games.php" class="nav-item <?= $activePage === 'search' ? 'active' : '' ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span><?= __('search') ?></span>
        </a>

        <a href="<?= BASE_URL ?>/customer/categories.php" class="nav-item <?= $activePage === 'categories' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span><?= __('categories') ?></span>
        </a>

        <a href="<?= BASE_URL ?>/index.php#goty-showcase" class="nav-item <?= $activePage === 'goty' ? 'active' : '' ?>">
            <i class="fa-solid fa-trophy"></i>
            <span><?= __('goty') ?></span>
        </a>

        <div class="nav-section-title">Store & Library</div>

        <a href="<?= BASE_URL ?>/customer/favorites.php" class="nav-item <?= $activePage === 'favorites' ? 'active' : '' ?>">
            <i class="fa-solid fa-heart"></i>
            <span><?= __('favorites') ?></span>
            <span class="nav-badge" id="favorites-badge-count" style="<?= ($favCount ?? 0) > 0 ? '' : 'display:none;' ?>"><?= $favCount ?? 0 ?></span>
        </a>

        <a href="<?= BASE_URL ?>/customer/cart.php" class="nav-item <?= $activePage === 'cart' ? 'active' : '' ?>">
            <i class="fa-solid fa-cart-shopping"></i>
            <span><?= __('cart') ?></span>
            <span class="nav-badge" id="cart-badge-count" style="<?= ($cartCount ?? 0) > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?? 0 ?></span>
        </a>

        <a href="<?= BASE_URL ?>/customer/orders.php" class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>">
            <i class="fa-solid fa-box-archive"></i>
            <span><?= __('orders') ?></span>
        </a>

        <a href="<?= BASE_URL ?>/customer/purchased.php" class="nav-item <?= $activePage === 'purchased' ? 'active' : '' ?>">
            <i class="fa-solid fa-dice-d20"></i>
            <span><?= __('purchased') ?></span>
        </a>

        <div class="nav-section-title">Account</div>

        <?php if (is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/customer/profile.php" class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i>
                <span><?= __('profile') ?></span>
            </a>

            <a href="<?= BASE_URL ?>/customer/settings.php" class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i>
                <span><?= __('settings') ?></span>
            </a>

            <?php if (is_admin()): ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item" style="color:var(--primary); font-weight:bold;">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Admin Panel</span>
                </a>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="color:var(--danger);">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span><?= __('logout') ?></span>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="nav-item <?= $activePage === 'login' ? 'active' : '' ?>">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span><?= __('login') ?></span>
            </a>
            <a href="<?= BASE_URL ?>/register.php" class="nav-item <?= $activePage === 'register' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-plus"></i>
                <span><?= __('register') ?></span>
            </a>
        <?php endif; ?>
    </nav>

    <?php if (is_logged_in() && $user): ?>
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/customer/profile.php" class="user-snippet">
                <img src="<?= get_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User') ?>" alt="User Avatar" class="user-avatar">
                <div class="user-info">
                    <div class="user-name"><?= e($user['name'] ?? 'Customer') ?></div>
                    <div class="user-role"><?= e($user['role'] ?? 'customer') ?></div>
                </div>
            </a>
        </div>
    <?php endif; ?>
</aside>
