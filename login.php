<?php
require 'config/init.php';

// --- KONFIGURASI CLOUDFLARE ---
$cf_site_key = '1x00000000000000000000AA';
$cf_secret_key = '1x0000000000000000000000000000000AA';
// -----------------------------

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
    <title>Login Pelanggan - Alam Adventure</title>
    <link rel="icon" href="public/logo.png" type="image/png" />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Inter:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        /* Styling Konsisten dengan Register */
        :root {
            --brand: #2c4532;
            --accent: #f9d84a;
            --bg-color: #f9f5f0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Background yang sama */
            background-image: linear-gradient(rgba(44, 69, 50, 0.8), rgba(44, 69, 50, 0.8)), url('public/main-background.jpg');
            background-size: cover;
            background-position: center;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
        }

        .brand-logo {
            width: 70px;
            margin-bottom: 15px;
        }

        h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--brand);
            margin: 0 0 5px 0;
            font-weight: 700;
        }

        p.subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--brand);
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 69, 50, 0.1);
        }

        .btn-auth {
            width: 100%;
            padding: 14px;
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-auth:hover {
            background: #1f3225;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 69, 50, 0.3);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .footer-link {
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .footer-link a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        /* Style khusus Cloudflare Container */
        .cf-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <img src="public/logo.png" alt="Logo" class="brand-logo">
        <h2>Selamat Datang Kembali</h2>
        <p class="subtitle">Masuk untuk melanjutkan petualanganmu</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <div style="position:relative;">
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                    <i class="fas fa-envelope" style="position:absolute; right:15px; top:14px; color:#aaa;"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="passInput" class="form-control"
                        placeholder="Masukkan password" required>
                    <i class="fas fa-eye" id="togglePass"
                        style="position:absolute; right:15px; top:14px; color:#aaa; cursor:pointer;"></i>
                </div>
            </div>

            <div class="cf-container">
                <div class="cf-turnstile" data-sitekey="<?= $cf_site_key ?>" data-theme="light"></div>
            </div>

            <button type="submit" name="login" class="btn-auth">Masuk Sekarang</button>
        </form>

        <div class="footer-link">
            Belum punya akun? <a href="register_user.php">Daftar disini</a>
            <br>
            <a href="index.php" style="font-size:12px; display:block; margin-top:10px; color:#888;">&larr; Kembali ke
                Beranda</a>
        </div>
    </div>

    <script>
        // Script Toggle Password Visibility
        const togglePass = document.getElementById('togglePass');
        const passInput = document.getElementById('passInput');

        togglePass.addEventListener('click', function () {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>