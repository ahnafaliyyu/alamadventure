<?php
// --- BAGIAN 1: KONFIGURASI & INISIALISASI ---
require_once 'config/init.php';
require_once 'config/mail.php'; // Memanggil fungsi kirim email

$error = '';
$success = '';

// --- BAGIAN 2: PROSES REGISTER ---
if (isset($_POST['register'])) {
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(htmlspecialchars($_POST['email']));
    $phone = trim(htmlspecialchars($_POST['phone']));
    $password = $_POST['password'];

    // Validasi input sederhana
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Cek Email Duplicate
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $error = "Email sudah terdaftar! Silakan login.";
        } else {
            // Hash Password & Buat Token
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            // Simpan ke Database
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sssss", $name, $email, $phone, $passwordHash, $token);

            if ($stmt->execute()) {
                // --- BAGIAN 3: KIRIM EMAIL VERIFIKASI ---

                // Deteksi URL dasar secara otomatis (Localhost / Ngrok / Hosting)
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                // Sesuaikan path folder jika perlu (misal: /alamadventure/verify.php)
                $path = dirname($_SERVER['PHP_SELF']);
                $base_url = $protocol . "://" . $host . $path;

                $link = $base_url . "/verify.php?token=" . $token;

                $emailSubject = "Verifikasi Akun Alam Adventure";
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                        <div style='background-color: #2c4532; padding: 20px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Selamat Datang!</h2>
                        </div>
                        <div style='padding: 20px; color: #333;'>
                            <p>Halo <strong>$name</strong>,</p>
                            <p>Terima kasih telah bergabung di Alam Adventure. Untuk mulai menyewa peralatan, silakan verifikasi email Anda dengan mengklik tombol di bawah ini:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$link' style='background-color: #f9d84a; color: #2c4532; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Verifikasi Akun Saya</a>
                            </div>
                            <p style='font-size: 12px; color: #777;'>Atau salin link berikut ke browser Anda:<br> $link</p>
                        </div>
                        <div style='background-color: #f4f7f5; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                            &copy; " . date('Y') . " Alam Adventure
                        </div>
                    </div>
                ";

                // Panggil fungsi dari config/mail.php
                if (sendEmail($email, $emailSubject, $emailBody)) {
                    $success = "Pendaftaran berhasil! Link verifikasi telah dikirim ke <strong>$email</strong>. Silakan cek Inbox atau Spam.";
                } else {
                    // Jika email gagal, user tetap terdaftar tapi belum verifikasi
                    $error = "Pendaftaran berhasil, namun sistem gagal mengirim email verifikasi. Hubungi Admin.";
                }
            } else {
                $error = "Gagal mendaftar ke database. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Alam Adventure</title>
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
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
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
            background-image: url('public/main-background.jpg');
            background-size: cover;
            background-blend-mode: multiply;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            padding: 20px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px 32px;
            text-align: center;
            backdrop-filter: blur(8px);
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            margin-bottom: 24px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .auth-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 8px 0;
        }

        .auth-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .form-group {
            margin: 30px 0 20px 0;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
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
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .toggle-password {
            pointer-events: auto;
            cursor: pointer;
        }

        .btn-auth {
            width: 100%;
            padding: 14px 24px;
            background-color: var(--primary);
            color: white;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }

        .btn-auth:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
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
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

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

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="auth-card">
            <img src="public/logo.png" alt="Logo" class="brand-logo">

            <div class="auth-header">
                <h2>Bergabung Bersama Kami</h2>
                <p>Buat akun baru untuk mulai menyewa perlengkapan.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="Nama Lengkap" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="email@contoh.com" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <div class="input-group">
                        <input type="tel" name="phone" class="form-control" placeholder="0812..." required>
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Buat password aman" required>
                        <i class="fas fa-eye input-icon toggle-password" id="togglePass"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-auth">Daftar Akun</button>
            </form>

            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Masuk disini</a></p>
                <p style="margin-top: 10px;"><a href="index.php" style="color: #64748b; font-weight: normal;">&larr;
                        Kembali ke Beranda</a></p>
            </div>
        </div>
    </div>

    <script>
        const togglePass = document.getElementById('togglePass');
        const passInput = document.getElementById('password');
        if (togglePass && passInput) {
            togglePass.addEventListener('click', function () {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>

</html>