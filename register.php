<?php
// --- BAGIAN 1: KONFIGURASI & INISIALISASI ---
// Sesuaikan path jika letak folder config berbeda
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Pastikan path ke vendor benar (biasanya di root project)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// [PENTING] Inisialisasi variabel agar tidak error "Undefined variable"
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
        // Uncomment baris bawah untuk debugging jika email gagal
        // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
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

            // Simpan ke Database (Status is_verified = 0)
            // Pastikan tabel 'users' sudah punya kolom 'verification_token' dan 'is_verified'
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("sssss", $name, $email, $phone, $passwordHash, $token);

            if ($stmt->execute()) {
                // Kirim Email Verifikasi
                // Ganti 'localhost/public' sesuai alamat website Anda
                $link = "http://d23f9303ec2b.ngrok-free.app/verify.php?token=" . $token;

                $emailBody = "
                    <h3>Halo, $name!</h3>
                    <p>Terima kasih telah mendaftar di Alam Adventure.</p>
                    <p>Silakan klik tombol di bawah untuk mengaktifkan akun Anda:</p>
                    <a href='$link' style='background:#2c4532; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Verifikasi Akun</a>
                    <br><br>
                    <p>Atau klik link ini: <a href='$link'>$link</a></p>
                ";

                if (sendEmail($email, "Verifikasi Akun Alam Adventure", $emailBody)) {
                    $success = "Pendaftaran berhasil! Cek email Anda untuk verifikasi.";
                } else {
                    $error = "Pendaftaran berhasil, namun gagal mengirim email verifikasi. Hubungi Admin.";
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
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Inter:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand: #2c4532;
            --accent: #f9d84a;
            --bg-color: #f9f5f0;
        }

        body {
            margin: 20px 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(44, 69, 50, 0.8), rgba(44, 69, 50, 0.8)), url('public/main-background.jpg');
            background-size: cover;
            background-position: center;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 450px;
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

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .footer-link {
            margin-top: 20px;
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
    </style>
</head>

<body>

    <div class="auth-card">
        <img src="public/logo.png" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
        <h2>Bergabung Bersama Kami</h2>
        <p class="subtitle">Buat akun untuk mulai menyewa perlengkapan</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?> <a
                    href="login.php">Login sekarang</a></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
            </div>

            <div class="form-group">
                <label>Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
            </div>

            <div class="form-group">
                <label>Nomor WhatsApp</label>
                <input type="text" name="phone" class="form-control" placeholder="0812..." required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Buat password aman" required>
            </div>

            <button type="submit" name="register" class="btn-auth">Daftar Akun</button>
        </form>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Masuk disini</a>
            <br>
            <a href="index.php" style="font-size:12px; display:block; margin-top:10px; color:#888;">&larr; Kembali ke
                Beranda</a>
        </div>
    </div>

</body>

</html>