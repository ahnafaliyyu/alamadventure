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

        $link = "http://localhost/alamadventure/reset_password.php?token=" . $token;
        $body = "Klik link ini untuk reset password Anda (Berlaku 1 jam): <br> <a href='$link'>Reset Password</a>";

        if (sendEmail($email, "Reset Password", $body)) {
            $message = "<div class='alert alert-success'>Link reset password telah dikirim ke email Anda.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal mengirim email.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Email tidak ditemukan.</div>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Lupa Password</title>
    <link rel="stylesheet" href="public/css/main.css">
    <style>
        /* Inline style sederhana untuk layout tengah */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f9f5f0;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2c4532;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Lupa Password?</h2>
        <p>Masukkan email Anda untuk menerima instruksi reset password.</p>
        <?= $message ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Anda" required>
            <button type="submit" name="reset">Kirim Link Reset</button>
        </form>
        <br>
        <a href="login_user.php" style="color:#2c4532; text-decoration:none;">Kembali Login</a>
    </div>
</body>

</html>