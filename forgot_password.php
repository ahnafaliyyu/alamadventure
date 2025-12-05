<?php
require 'config/init.php';
require 'config/mail.php';

$message = '';

if (isset($_POST['reset'])) {
    $email = $_POST['email'];

    // Cek apakah email ada
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        $token = bin2hex(random_bytes(32));
        // Token berlaku 1 jam
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $upd->bind_param("sss", $token, $expires, $email);
        $upd->execute();

        // Deteksi URL dasar
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $base_url = $protocol . "://" . $host . $path;

        $link = $base_url . "/reset_password.php?token=" . $token;

        $emailSubject = "Reset Password Alam Adventure";
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background-color: #2c4532; padding: 20px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0;'>Reset Password</h2>
                </div>
                <div style='padding: 20px; color: #333;'>
                    <p>Halo <strong>{$user['name']}</strong>,</p>
                    <p>Kami menerima permintaan untuk mereset password akun Alam Adventure Anda. Klik tombol di bawah ini untuk membuat password baru:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='background-color: #f9d84a; color: #2c4532; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Reset Password Saya</a>
                    </div>
                    <p style='font-size: 12px; color: #777;'>Link ini hanya berlaku selama 1 jam. Jika Anda tidak merasa meminta reset password, abaikan email ini.</p>
                </div>
            </div>
        ";

        if (sendEmail($email, $emailSubject, $emailBody)) {
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Link reset telah dikirim ke email Anda.</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Gagal mengirim email. Coba lagi nanti.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Email tidak ditemukan.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Alam Adventure</title>
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
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="icon-circle">
                <i class="fas fa-lock"></i>
            </div>

            <h2>Lupa Password?</h2>
            <p>Masukkan alamat email yang terdaftar. Kami akan mengirimkan instruksi untuk mereset password Anda.</p>

            <?= $message ?>

            <form method="POST">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda"
                            required>
                    </div>
                </div>
                <button type="submit" name="reset" class="btn-submit">Kirim Link Reset</button>
            </form>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </div>
</body>

</html>