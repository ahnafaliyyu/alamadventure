<?php
require 'config/init.php';

// Ambil dari ENV (Gunakan Null Coalescing ?? untuk fallback jika gagal)
$cf_site_key = $_ENV['CF_SITE_KEY'] ?? '';
$cf_secret_key = $_ENV['CF_SECRET_KEY'] ?? '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $cf_token = $_POST['cf-turnstile-response'] ?? '';

    // 1. Verifikasi Cloudflare Turnstile
    $ip = $_SERVER['REMOTE_ADDR'];
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $cf_secret_key,
        'response' => $cf_token,
        'remoteip' => $ip
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $verifyUrl);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $responseKeys = json_decode($response, true);

    if ($responseKeys["success"]) {
        // 2. Jika Captcha Valid, Cek User
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // [TAMBAHAN] Cek Verifikasi
            if ($user['is_verified'] == 0) {
                $error = "Akun belum diverifikasi. Cek email Anda.";
            } else {
                // Login Sukses
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_phone'] = $user['phone'];

                // Cek redirect url (misal dari keranjang)
                if (isset($_SESSION['redirect_after_login'])) {
                    $url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $url");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        } else {
            $error = "Email atau Password salah!";
        }
    } else {
        $error = "Verifikasi keamanan gagal. Silakan coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Alam Adventure</title>
    <link rel="icon" href="public/logo.png" type="image/png" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        :root {
            /* Palette Warna Alam Adventure */
            --primary: #2c4532;
            /* Hijau Gelap */
            --primary-hover: #1f3225;
            --accent: #f9d84a;
            /* Kuning Emas */
            --bg-color: #f8fafc;
            /* Background Halaman */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--primary);
            min-height: 100vh;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }

        .login-container {
            width: 100%;
            max-width: 520px;
            padding: 20px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            /* Sedikit transparan */
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px 32px;
            text-align: center;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .auth-header {
            margin-bottom: 32px;
        }

        .auth-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .auth-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            padding-right: 40px;
            /* Space untuk icon */
            font-size: 14px;
            line-height: 1.5;
            color: var(--text-main);
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 69, 50, 0.15);
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            /* Icon tidak bisa diklik kecuali toggle password */
            font-size: 14px;
        }

        .toggle-password {
            pointer-events: auto;
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--text-main);
        }

        .forgot-password {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .forgot-password a {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .cf-container {
            margin: 24px 0;
            display: flex;
            justify-content: center;
        }

        .btn-auth {
            width: 100%;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-auth:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .auth-footer {
            margin-top: 32px;
            font-size: 14px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-icon {
            flex-shrink: 0;
        }

        @media (max-width: 510px) {
            body {
                background: rgba(255, 255, 255, 0.98);
            }

            .auth-card {
                padding: 30px 10px;
                border-radius: 12px;
                box-shadow: none;
            }

            .login-container {
                padding: 16px;
            }
        }

        @media (max-width: 380px) {
            .auth-card {
                padding: 30px 0;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="auth-card">
            <img src="public/logo.png" alt="Alam Adventure Logo" class="brand-logo">

            <div class="auth-header">
                <h2>Selamat Datang</h2>
                <p>Masuk ke akun Anda untuk melanjutkan petualangan.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com"
                            required autocomplete="email">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label for="password" class="form-label" style="margin-bottom:0;">Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                            required>
                        <i class="fas fa-eye input-icon toggle-password" id="togglePass" title="Tampilkan Password"></i>
                    </div>
                    <div class="forgot-password">
                        <a href="lupa-password">Lupa Password?</a>
                    </div>
                </div>

                <div class="cf-container">
                    <div class="cf-turnstile" data-sitekey="<?= $cf_site_key ?>" data-theme="light"></div>
                </div>

                <button type="submit" name="login" class="btn-auth">
                    Masuk Sekarang
                </button>
            </form>

            <div class="auth-footer">
                <p>Belum memiliki akun? <a href="registrasi">Daftar disini</a></p>
                <p style="margin-top: 12px;">
                    <a href="index.php" style="color: var(--text-muted); font-weight: normal; font-size: 13px;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Script Toggle Password Visibility
        const togglePass = document.getElementById('togglePass');
        const passInput = document.getElementById('password');

        if (togglePass && passInput) {
            togglePass.addEventListener('click', function () {
                // Toggle tipe input
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);

                // Toggle icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>

</body>

</html>