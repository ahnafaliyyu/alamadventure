<?php
require_once __DIR__ . '/../config/config.php';

if (auth_is_logged_in()) {
    redirect('index.php');
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Admin - Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css" />
    <style>
        .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f7f4; }
        .login-card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #d6e2d9; border-radius: 16px; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        .login-card h1 { margin: 0 0 18px; color: #2c4532; font-size: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c4532; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #d6e2d9; border-radius: 12px; font-size: 14px; }
        .btn-primary { display: inline-block; padding: 10px 16px; background: #2c4532; color: #fff; border: none; border-radius: 12px; cursor: pointer; text-decoration: none; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 12px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h1>Login Admin</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post" action="do_login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required />
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <button type="submit" class="btn-primary">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>