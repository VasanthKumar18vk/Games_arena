<?php
/**
 * Games Arena - Unified Customer & Admin Login
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/customer/dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            $pdo = get_db();
            if (!$pdo) {
                $error = 'Database connection error. Please run the <a href="install.php" style="text-decoration:underline;">Installer</a>.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] === 'blocked') {
                        $error = 'Your account has been deactivated. Please contact customer support.';
                    } else {
                        // Successful login
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_theme'] = $user['theme'] ?? 'light';
                        $_SESSION['user_language'] = $user['language'] ?? 'English';

                        // Sync any items added to cart or favorites as a guest
                        sync_guest_data_to_user($pdo, (int)$user['user_id']);

                        $redirect = $_SESSION['redirect_after_login'] ?? '';
                        unset($_SESSION['redirect_after_login']);

                        if ($redirect && !str_contains($redirect, 'login.php')) {
                            header("Location: " . $redirect);
                        } elseif ($user['role'] === 'admin') {
                            header("Location: " . BASE_URL . "/admin/dashboard.php");
                        } else {
                            header("Location: " . BASE_URL . "/customer/dashboard.php");
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password combination.';
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
    <title>Login — Games Arena</title>
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
            max-width: 480px;
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
        .demo-quick-box {
            background: #F8FAFC;
            border: 1px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .quick-badge {
            cursor: pointer;
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            margin-top: 6px;
            transition: var(--transition);
        }
        .quick-badge:hover {
            background: var(--primary);
            color: #FFFFFF;
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
        <h1 style="font-size:24px; font-weight:800; margin-bottom:6px;">Welcome Back</h1>
        <p style="color:var(--text-muted); font-size:14.5px;">Sign in to your account to continue</p>
    </div>

    <?php if ($error): ?>
        <div style="background:#FEF2F2; color:#991B1B; border:1px solid #FECACA; padding:12px 16px; border-radius:12px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <div class="demo-quick-box">
        <div style="font-weight:700; color:var(--text-primary); margin-bottom:4px;"><i class="fa-solid fa-bolt text-amber-500"></i> Quick Demo Accounts:</div>
        <div style="display:flex; gap:10px;">
            <span class="quick-badge" onclick="fillDemo('admin@gamesarena.com', 'Admin@123')"><i class="fa-solid fa-shield"></i> Admin Login</span>
            <span class="quick-badge" onclick="fillDemo('customer@gamesarena.com', 'Customer@123')"><i class="fa-solid fa-user"></i> Customer Demo</span>
        </div>
    </div>

    <form method="POST" action="login.php">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <div style="position:relative;">
                <i class="fa-regular fa-envelope" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="email" name="email" id="login-email" class="form-control" style="padding-left:44px;" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" style="display:flex; justify-content:space-between;">
                <span>Password</span>
            </label>
            <div style="position:relative;">
                <i class="fa-solid fa-lock" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                <input type="password" name="password" id="login-pass" class="form-control" style="padding-left:44px;" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:15px; margin-top:8px;">
            <i class="fa-solid fa-right-to-bracket"></i> Sign In to Games Arena
        </button>
    </form>

    <div style="text-align:center; margin-top:24px; padding-top:20px; border-top:1px solid var(--border-color); font-size:14px; color:var(--text-secondary);">
        Don't have an account? <a href="register.php" style="font-weight:700; color:var(--primary);">Create an Account</a>
        <br><br>
        <a href="index.php" style="color:var(--text-muted); font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Back to Storefront</a>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-pass').value = pass;
}
</script>

</body>
</html>
