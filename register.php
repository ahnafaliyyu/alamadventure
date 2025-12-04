<?php
// --- BAGIAN 1: KONFIGURASI & INISIALISASI ---
// Sesuaikan path jika letak folder config berbeda
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// --- BAGIAN 2: FUNGSI KIRIM EMAIL ---
function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        // Konfigurasi Server SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // GANTI DENGAN EMAIL & APP PASSWORD ANDA
        $mail->Username = 'ridho.setiawan24406@gmail.com';
        $mail->Password = 'yoeovvavzpuzycua';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Penerima
        $mail->setFrom('no-reply@alamadventure.com', 'Alam Adventure');
        $mail->addAddress($to);

        // Konten
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// --- BAGIAN 3: PROSES REGISTER ---
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
                // Kirim Email Verifikasi
                // Pastikan domain sesuai dengan setup lokal/live Anda
                $link = "http://d23f9303ec2b.ngrok-free.app/verify.php?token=" . $token;

                $emailBody = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #2c4532;'>Halo, $name!</h2>
                        <p>Terima kasih telah bergabung di <strong>Alam Adventure</strong>.</p>
                        <p>Tinggal satu langkah lagi untuk memulai petualangan Anda. Silakan klik tombol di bawah untuk mengaktifkan akun:</p>
                        <p style='text-align: center;'>
                            <a href='$link' style='background-color: #2c4532; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Verifikasi Akun Saya</a>
                        </p>
                        <p style='margin-top: 20px; font-size: 12px; color: #777;'>Jika tombol tidak berfungsi, salin link ini: <br> $link</p>
                    </div>
                ";

                if (sendEmail($email, "Verifikasi Akun Alam Adventure", $emailBody)) {
                    $success = "Pendaftaran berhasil! Cek email Anda untuk verifikasi.";
                } else {
                    $error = "Pendaftaran berhasil, namun gagal mengirim email verifikasi.";
                }
            } else {
                $error = "Gagal mendaftar ke database. Coba lagi.";
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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette Warna Alam Adventure */
            --primary: #2c4532;
            --primary-hover: #1f3225;
            --accent: #f9d84a;
            --bg-color: #f8fafc;
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
            max-width: 460px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px 32px;
            text-align: center;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 25px 0;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            margin-bottom: 24px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .auth-header {
            margin-bottom: 32px;
        }

        .auth-header h2 {
            font-size: 26px;
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

        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
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
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
            margin-top: 10px;
        }

        .btn-auth:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
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

        /* Alerts */
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

        @media (max-width: 510px) {
            body {
                background: rgba(255, 255, 255, 0.98);
            }

            .auth-card {
                padding: 30px 20px;
                border-radius: 12px;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="auth-card">
            <img src="public/logo.png" alt="Alam Adventure Logo" class="brand-logo" onerror="this.style.display='none'">

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
                    <span><?= $success ?> <br><a href="login.php" style="color:inherit; text-decoration:underline;">Masuk
                            sekarang</a></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Email</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
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
                        <i class="fas fa-eye input-icon toggle-password" id="togglePass" title="Tampilkan Password"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-auth">Daftar Akun</button>
            </form>

            <div class="auth-footer">
                <p>Sudah memiliki akun? <a href="login.php">Masuk disini</a></p>
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
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);

                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>

</body>

</html>