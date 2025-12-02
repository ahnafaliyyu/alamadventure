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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Reset & Variable mirip Main Site */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .login-wrapper { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            
            /* Background Gambar Alam + Overlay Gelap */
            background-image: linear-gradient(rgba(0, 20, 5, 0.7), rgba(0, 0, 0, 0.6)),
                              url('../public/main-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-card { 
            width: 100%; 
            max-width: 450px; /* Diperbesar sedikit */
            background: rgba(255, 255, 255, 0.95); /* Putih transparan */
            border: 2px solid #f2ead3;
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); 
            backdrop-filter: blur(5px);
            transition: transform 0.3s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header img {
            width: 60px;
            margin-bottom: 10px;
        }

        .login-card h1 { 
            margin: 0; 
            color: #2c4532; /* Hijau Tua Brand */
            font-size: 28px; 
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .login-card p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group { margin-bottom: 20px; }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #2c4532; 
            font-size: 14px;
        }
        
        .form-group input { 
            width: 100%; 
            padding: 14px; 
            border: 2px solid #ddd; 
            border-radius: 12px; 
            font-size: 14px; 
            box-sizing: border-box;
            transition: border-color 0.3s;
            background: #fdfdfd;
        }

        .form-group input:focus {
            outline: none;
            border-color: #344f1f;
            background: #fff;
        }

        .btn-primary { 
            display: block; 
            width: 100%;
            padding: 14px; 
            background: #344f1f; /* Hijau Tua */
            color: #fff; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #233515;
            box-shadow: 0 5px 15px rgba(52, 79, 31, 0.3);
            transform: translateY(-2px);
        }

        .error { 
            background: #ffe6e6; 
            color: #d63031; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 13px;
            border-left: 5px solid #d63031;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link a:hover {
            color: #344f1f;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <img src="../public/logo.png" alt="Logo" onerror="this.style.display='none'">
                <h1>ADMIN PANEL</h1>
                <p>Silakan masuk untuk mengelola website</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" action="do_login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required />
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required />
                </div>
                <button type="submit" class="btn-primary">Masuk Dashboard</button>
            </form>
            
            <div class="back-link">
                <a href="../index.html">← Kembali ke Halaman Utama</a>
            </div>
        </div>
    </div>
</body>
</html>