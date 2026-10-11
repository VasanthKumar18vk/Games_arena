<?php
/**
 * Games Arena - Customer Registration
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/customer/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters in length.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = get_db();
            if (!$pdo) {
                $error = 'Database connection error. Please run the <a href="install.php">Installer</a>.';
            } else {
                // Check duplicate email
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email address already exists. Please login instead.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, phone, role, status, language, theme)
                        VALUES (?, ?, ?, ?, 'customer', 'active', 'English', 'light')
                    ");
                    $stmt->execute([$name, $email, $hash, $phone]);
                    $newUserId = $pdo->lastInsertId();

                    // Auto login
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['user_theme'] = 'light';
                    $_SESSION['user_language'] = 'English';

                    // Sync any items added to cart or favorites as a guest
                    sync_guest_data_to_user($pdo, (int)$newUserId);

                    set_flash('success', 'Welcome to Games Arena! Your account has been created.');
                    header("Location: " . BASE_URL . "/customer/dashboard.php");
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account — Games Arena</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #EFF6FF 0%, #F8FAFC 40%, #DBEAFE 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-container {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 45px -10px rgba(37, 99, 235, 0.15), 0 0 0 1px var(--border-color);
            max-width: 520px;
            width: 100%;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #2563EB, #38BDF8, #1E40AF);
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div style="text-align:center; margin-bottom:28px;">
        <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex; align-items:center; gap:10px; text-decoration:none; margin-bottom:12px;">
            <div style="width:48px; height:48px; background:linear-gradient(135deg, #2563EB, #38BDF8); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#FFF; font-size:24px;">
                <i class="fa-solid fa-gamepad"></i>
            </div>
            <span style="font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; color:var(--text-primary); letter-spacing:-0.5px;">GAMES <span style="color:var(--primary);">ARENA</span></span>
        </a>
        <h1 style="font-size:24px; font-weight:800; margin-bottom:6px;">Create Account</h1>
        <p style="color:var(--text-muted); font-size:14.5px;">Join thousands of gamers on Games Arena</p>
    </div>

    <?php if ($error): ?>
        <div style="background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; padding:12px 16px; border-radius:12px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <div style="position:relative;">
                <i class="fa-regular fa-user" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="text" name="name" class="form-control" style="padding-left:44px;" placeholder="John Doe" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email Address *</label>
            <div style="position:relative;">
                <i class="fa-regular fa-envelope" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="email" name="email" class="form-control" style="padding-left:44px;" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Phone Number (Optional)</label>
            <div style="position:relative;">
                <i class="fa-solid fa-phone" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="tel" name="phone" class="form-control" style="padding-left:44px;" placeholder="+91 9876543210" value="<?= e($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:15px; margin-top:10px;">
            <i class="fa-solid fa-user-plus"></i> Create Account
        </button>
    </form>

    <div style="text-align:center; margin-top:24px; padding-top:20px; border-top:1px solid var(--border-color); font-size:14px; color:var(--text-secondary);">
        Already have an account? <a href="login.php" style="font-weight:700; color:var(--primary);">Sign In</a>
        <br><br>
        <a href="index.php" style="color:var(--text-muted); font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Back to Storefront</a>
    </div>
</div>

</body>
</html>
