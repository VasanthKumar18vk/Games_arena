<?php
/**
 * Games Arena - Global Page Footer
 */
?>
        <footer style="margin-top:auto; padding:32px; border-top:1px solid var(--border-color); background:var(--bg-header); display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:16px; font-size:13.5px; color:var(--text-muted);">
            <div>
                <strong><?= APP_NAME ?></strong> &copy; <?= date('Y') ?>. Built for Gaming Excellence (PHP + MySQL / XAMPP).
            </div>
            <div style="display:flex; gap:20px; font-weight:600;">
                <a href="<?= BASE_URL ?>/customer/games.php" style="color:var(--text-secondary);">Browse Games</a>
                <a href="<?= BASE_URL ?>/customer/categories.php" style="color:var(--text-secondary);">Categories</a>
                <a href="<?= BASE_URL ?>/customer/settings.php" style="color:var(--text-secondary);">Preferences</a>
            </div>
        </footer>
    </main>
</div>

<!-- Toast Feedback Notifications Container -->
<div id="toast-container" class="toast-container"></div>

<!-- Application JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

</body>
</html>
