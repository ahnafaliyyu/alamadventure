<?php
require 'config/init.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validasi Token
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("<h3 style='text-align:center; margin-top:50px;'>Token tidak valid atau sudah kadaluarsa. <a href='forgot_password.php'>Minta ulang</a></h3>");
}

if (isset($_POST['change_pass'])) {
    $pass = $_POST['password'];
    $conf = $_POST['confirm_password'];

    if ($pass !== $conf) {
        $error = "Password konfirmasi tidak cocok.";
    } else {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        // Update password & hapus token
        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $upd->bind_param("ss", $newHash, $token);

        if ($upd->execute()) {
            echo "<script>alert('Password berhasil diubah! Silakan login.'); window.location='login_user.php';</script>";
        } else {
            $error = "Terjadi kesalahan sistem.";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f9f5f0;
            font-family: sans-serif;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
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

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2 style="text-align:center; color:#2c4532;">Buat Password Baru</h2>
        <?php if ($error)
            echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Password Baru" required>
            <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required>
            <button type="submit" name="change_pass">Simpan Password</button>
        </form>
    </div>
</body>

</html>