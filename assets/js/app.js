/**
 * Games Arena - Frontend Application Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initThemeToggle();
    initToastSystem();
    initFavorites();
    initCartActions();
    initRatingStars();
    initTabs();
    initModals();
});

// Toast System
function showToast(message, type = 'primary') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    let icon = 'fa-circle-check';
    if (type === 'danger') icon = 'fa-circle-xmark';
    if (type === 'info') icon = 'fa-circle-info';

    toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}
window.showToast = showToast;

// Sidebar & Mobile Toggle
function initSidebar() {
    const toggleBtn = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('app-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay && sidebar) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

// Theme Switcher
function initThemeToggle() {
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (!themeBtn) return;

    themeBtn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('ga_theme', newTheme);

        const icon = themeBtn.querySelector('i');
        if (icon) {
            icon.className = newTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }

        // Save preference to server
        fetch(`${window.BASE_URL || ''}/customer/settings.php?action=toggle_theme&theme=${newTheme}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(() => {});
        
        showToast(`Switched to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'info');
    });
}

// AJAX Favorites Toggle
function initFavorites() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.game-card-fav-btn, .btn-fav-toggle');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const gameId = btn.dataset.gameId;
        if (!gameId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'toggle_favorite');
            formData.append('game_id', gameId);

            const res = await fetch(`${window.BASE_URL || ''}/customer/favorites.php`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await res.json();
            if (data.status === 'unauthorized') {
                window.location.href = `${window.BASE_URL || ''}/login.php`;
                return;
            }

            if (data.status === 'success') {
                if (data.is_favorited) {
                    btn.classList.add('active');
                    btn.querySelector('i').className = 'fa-solid fa-heart';
                    showToast('Added to Favorites ❤️', 'primary');
                } else {
                    btn.classList.remove('active');
                    btn.querySelector('i').className = 'fa-regular fa-heart';
                    showToast('Removed from Favorites', 'info');
                    // If on favorites page, remove card smoothly
                    const favCard = document.getElementById(`fav-card-${gameId}`);
                    if (favCard) {
                        favCard.style.opacity = '0';
                        favCard.style.transform = 'scale(0.9)';
                        setTimeout(() => favCard.remove(), 250);
                    }
                }

                // Update badge
                const badge = document.getElementById('favorites-badge-count');
                if (badge && data.total_favorites !== undefined) {
                    badge.textContent = data.total_favorites;
                    badge.style.display = data.total_favorites > 0 ? 'flex' : 'none';
                }
            }
        } catch (err) {
            console.error('Favorites toggle error:', err);
        }
    });
}

// AJAX Cart Actions
function initCartActions() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-add-cart');
        if (!btn) return;

        e.preventDefault();
        const gameId = btn.dataset.gameId;
        if (!gameId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('game_id', gameId);

            const res = await fetch(`${window.BASE_URL || ''}/customer/cart.php`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const data = await res.json();
            if (data.status === 'unauthorized') {
                window.location.href = `${window.BASE_URL || ''}/login.php`;
                return;
            }

            if (data.status === 'success') {
                showToast('Added to Cart 🛒', 'primary');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> In Cart';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');

                // Update cart badge
                const badge = document.getElementById('cart-badge-count');
                if (badge && data.cart_count !== undefined) {
                    badge.textContent = data.cart_count;
                    badge.style.display = data.cart_count > 0 ? 'flex' : 'none';
                }
            } else if (data.message) {
                showToast(data.message, 'info');
            }
        } catch (err) {
            console.error('Add to cart error:', err);
        }
    });
}

// Interactive Review Star Rating Picker
function initRatingStars() {
    const starContainer = document.getElementById('review-star-picker');
    if (!starContainer) return;

    const stars = starContainer.querySelectorAll('.star-option');
    const input = document.getElementById('rating-input-value');

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.value);
            highlightStars(val);
        });

        star.addEventListener('click', () => {
            const val = parseInt(star.dataset.value);
            input.value = val;
            highlightStars(val);
        });
    });

    starContainer.addEventListener('mouseleave', () => {
        const selectedVal = parseInt(input.value) || 5;
        highlightStars(selectedVal);
    });

    function highlightStars(val) {
        stars.forEach(s => {
            const sVal = parseInt(s.dataset.value);
            const icon = s.querySelector('i');
            if (sVal <= val) {
                icon.className = 'fa-solid fa-star text-amber-400';
            } else {
                icon.className = 'fa-regular fa-star text-slate-300';
            }
        });
    }
}

// System Requirements & General Tab Switcher
function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.tabTarget;
            const parent = btn.closest('.tabs-wrapper') || document;

            parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parent.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');

            btn.classList.add('active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.style.display = 'block';
            }
        });
    });
}

// Modals Helper
function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalOpen;
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });
}
