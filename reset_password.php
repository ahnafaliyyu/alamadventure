<?php
require 'config/init.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$token_valid = false;

// 1. Validasi Token di Database
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $token_valid = true;
}

// 2. Proses Reset Password
if ($token_valid && isset($_POST['change_pass'])) {
    $pass = $_POST['password'];
    $conf = $_POST['confirm_password'];

    if (strlen($pass) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($pass !== $conf) {
        $error = "Password konfirmasi tidak cocok.";
    } else {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);

        // Update password & hapus token agar tidak bisa dipakai lagi
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $upd->bind_param("ss", $newHash, $token);

        if ($upd->execute()) {
            $success = "Password berhasil diubah! Silakan login dengan password baru Anda.";
            $token_valid = false; // Sembunyikan form
        } else {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Alam Adventure</title>
    <link rel="icon" href="public/logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #2c4532;
            --primary-hover: #1f3225;
            --accent: #f9d84a;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px 30px;
            text-align: center;
            backdrop-filter: blur(8px);
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: #f0fdf4;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .icon-circle.error {
            background: #fef2f2;
            color: #dc2626;
        }

        .icon-circle.success {
            background: #ecfccb;
            color: #65a30d;
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 10px;
        }

        p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0 0 25px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            padding-left: 40px;
            padding-right: 40px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 69, 50, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        .back-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
        }

        .toggle-password:hover {
            color: var(--text-main);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <?php if ($success): ?>
                <!-- TAMPILAN SUKSES -->
                <div class="icon-circle success">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Password Diubah!</h2>
                <p><?= $success ?></p>
                <a href="login.php" class="btn-submit"
                    style="text-decoration:none; display:inline-block; width:100%; box-sizing:border-box;">Masuk
                    Sekarang</a>

            <?php elseif (!$token_valid): ?>
                <!-- TAMPILAN TOKEN INVALID -->
                <div class="icon-circle error">
                    <i class="fas fa-link-slash"></i>
                </div>
                <h2>Link Tidak Valid</h2>
                <p>Link reset password ini sudah kadaluarsa atau tidak valid. Silakan minta link baru.</p>
                <a href="forgot_password.php" class="btn-submit"
                    style="text-decoration:none; display:inline-block; width:100%; box-sizing:border-box;">Minta Link
                    Baru</a>
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Kembali ke Login
                </a>

            <?php else: ?>
                <!-- TAMPILAN FORM -->
                <div class="icon-circle">
                    <i class="fas fa-key"></i>
                </div>

                <h2>Reset Password</h2>
                <p>Silakan buat password baru untuk akun Anda.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-control"
                                placeholder="Password Baru" required minlength="6">
                            <i class="fas fa-eye toggle-password" onclick="togglePass('password')"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                                placeholder="Konfirmasi Password" required minlength="6">
                            <i class="fas fa-eye toggle-password" onclick="togglePass('confirm_password')"></i>
                        </div>
                    </div>
                    <button type="submit" name="change_pass" class="btn-submit">Simpan Password</button>
                </form>

                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Batal
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePass(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>

</html>