<?php
/**
 * Games Arena - Database Auto-Installer & Health Check
 */
require_once __DIR__ . '/config/config.php';

$message = '';
$messageType = '';
$isInstalled = false;

// Check connection and tables
try {
    $rawDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $pdo = new PDO($rawDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Check if games_arena exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = (bool)$stmt->fetch();

    if ($dbExists) {
        $pdo->query("USE `" . DB_NAME . "`");
        $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
        $isInstalled = (bool)$stmt->fetch();
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// Handle Install / Reset Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'install' || $_POST['action'] === 'reinstall') {
        try {
            $sqlFile = __DIR__ . '/database/games_arena.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found at " . $sqlFile);
            }
            $sqlContent = file_get_contents($sqlFile);

            // Execute SQL commands
            $rawDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdo = new PDO($rawDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Execute multiple queries
            $pdo->exec($sqlContent);

            $message = "Database 'games_arena' installed successfully with all 35 blockbuster games, 10 categories, 70 system requirements, GOTY records, and demo accounts!";
            $messageType = "success";
            $isInstalled = true;
        } catch (Exception $e) {
            $message = "Installation failed: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games Arena - Database Installer & Setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563EB;
            --primary-dark: #1E40AF;
            --primary-light: #EFF6FF;
            --accent: #38BDF8;
            --bg: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --danger: #EF4444;
            --radius: 16px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #EFF6FF 0%, #F8FAFC 50%, #DBEAFE 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .installer-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 20px 40px -15px rgba(37, 99, 235, 0.15), 0 0 0 1px var(--border);
            max-width: 680px;
            width: 100%;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .installer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2563EB, #38BDF8, #1E40AF);
        }
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.12);
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        p.subtitle {
            color: var(--text-muted);
            font-size: 15px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .status-badge.installed {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .status-badge.pending {
            background: #FEF3C7;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-danger { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        .info-card {
            background: #F8FAFC;
            border: 1px solid var(--border);
            padding: 16px;
            border-radius: 12px;
        }
        .info-card h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }
        .credential-box {
            background: #FFFFFF;
            border: 1px dashed var(--border);
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
            font-family: monospace;
            color: var(--text-dark);
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            background: var(--primary);
            color: #FFFFFF;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #FFFFFF;
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: var(--primary-light);
        }
        .quick-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="brand-header">
        <div class="brand-icon">
            <i class="fa-solid fa-gamepad"></i>
        </div>
        <h1>Games Arena</h1>
        <p class="subtitle">Database Setup & System Health Check (PHP + MySQL / XAMPP)</p>
        <div style="margin-top: 14px;">
            <?php if ($isInstalled): ?>
                <span class="status-badge installed"><i class="fa-solid fa-circle-check"></i> Database Configured & Ready</span>
            <?php else: ?>
                <span class="status-badge pending"><i class="fa-solid fa-triangle-exclamation"></i> Database Installation Required</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>" style="font-size: 18px; margin-top: 2px;"></i>
            <div><?= htmlspecialchars($message) ?></div>
        </div>
    <?php endif; ?>

    <?php if (isset($dbError) && !$isInstalled): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-server" style="font-size: 18px;"></i>
            <div>
                <strong>MySQL Connection Notice:</strong><br>
                <?= htmlspecialchars($dbError) ?><br>
                <small>Make sure MySQL service is running in your XAMPP Control Panel on port <?= DB_PORT ?>.</small>
            </div>
        </div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-card">
            <h3><i class="fa-solid fa-user-shield"></i> Administrator</h3>
            <p>Access store analytics, CRUD for games, categories, order status, reviews moderation & GOTY.</p>
            <div class="credential-box">
                <strong>Email:</strong> admin@gamesarena.com<br>
                <strong>Pass:</strong> Admin@123
            </div>
        </div>

        <div class="info-card">
            <h3><i class="fa-solid fa-user"></i> Customer Demo</h3>
            <p>Browse games, live search, GOTY carousel, cart, instant checkout, reviews & favorites.</p>
            <div class="credential-box">
                <strong>Email:</strong> customer@gamesarena.com<br>
                <strong>Pass:</strong> Customer@123
            </div>
        </div>
    </div>

    <form method="POST" style="margin-bottom: 16px;">
        <input type="hidden" name="action" value="<?= $isInstalled ? 'reinstall' : 'install' ?>">
        <button type="submit" class="btn-primary">
            <i class="fa-solid <?= $isInstalled ? 'fa-rotate-right' : 'fa-database' ?>"></i>
            <?= $isInstalled ? 'Re-Initialize Database & Seed Data' : 'One-Click Database Setup' ?>
        </button>
    </form>

    <?php if ($isInstalled): ?>
        <div class="quick-links">
            <a href="index.php" class="btn-secondary">
                <i class="fa-solid fa-store"></i> Launch Storefront
            </a>
            <a href="login.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login Portal
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
