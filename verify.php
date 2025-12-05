<?php
require 'config/init.php';

$popupStatus = 'none'; // none, success, error, invalid
$popupMessage = '';
$redirectUrl = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Cek token di database
    $stmt = $conn->prepare("SELECT id, name, is_verified FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();

        if ($user['is_verified'] == 1) {
            // Sudah diverifikasi sebelumnya
            $popupStatus = 'info';
            $popupMessage = "Akun Anda sudah diverifikasi sebelumnya. Silakan login.";
            $redirectUrl = 'login.php';
        } else {
            // Lakukan Verifikasi
            $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);

            if ($update->execute()) {
                $popupStatus = 'success';
                $popupMessage = "Selamat, akun Anda berhasil diverifikasi! Anda sekarang dapat login dan mulai menyewa.";
                $redirectUrl = 'login.php';
            } else {
                $popupStatus = 'error';
                $popupMessage = "Terjadi kesalahan sistem saat memverifikasi akun.";
                $redirectUrl = 'index.php';
            }
        }
    } else {
        // Token tidak ditemukan
        $popupStatus = 'invalid';
        $popupMessage = "Link verifikasi tidak valid atau sudah kadaluarsa.";
        $redirectUrl = 'register.php';
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - Alam Adventure</title>
    <link rel="icon" href="public/logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #2c4532;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('public/main-background.jpg');
            background-size: cover;
            background-blend-mode: multiply;
        }

        /* Styling Modal Popup (Mengadaptasi gaya Admin) */
        .generic-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 99999;
            display: flex;
            /* Langsung tampil karena dikontrol PHP */
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .generic-box {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            margin: 0 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            transform: scale(0.9);
            animation: popUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .generic-icon {
            font-size: 60px;
            margin-bottom: 20px;
            display: inline-block;
        }

        .generic-icon.success {
            color: #2e7d32;
        }

        .generic-icon.error {
            color: #ef4444;
        }

        .generic-icon.info {
            color: #f9d84a;
        }

        .generic-icon.invalid {
            color: #666;
        }

        .generic-title {
            font-size: 24px;
            font-weight: 800;
            color: #2c4532;
            margin-bottom: 12px;
        }

        .generic-text {
            font-size: 15px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-generic {
            padding: 14px 30px;
            border-radius: 50px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-modal {
            background: #2c4532;
            color: white;
            box-shadow: 0 4px 15px rgba(44, 69, 50, 0.3);
        }

        .btn-primary-modal:hover {
            background: #1f3225;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 69, 50, 0.4);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes popUp {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <div class="generic-overlay">
        <div class="generic-box">
            <!-- Icon -->
            <?php if ($popupStatus == 'success'): ?>
                <div class="generic-icon success"><i class="fa-solid fa-circle-check"></i></div>
                <h3 class="generic-title">Verifikasi Berhasil!</h3>
            <?php elseif ($popupStatus == 'info'): ?>
                <div class="generic-icon info"><i class="fa-solid fa-circle-info"></i></div>
                <h3 class="generic-title">Info Akun</h3>
            <?php elseif ($popupStatus == 'invalid'): ?>
                <div class="generic-icon invalid"><i class="fa-solid fa-link-slash"></i></div>
                <h3 class="generic-title">Link Tidak Valid</h3>
            <?php else: ?>
                <div class="generic-icon error"><i class="fa-solid fa-circle-xmark"></i></div>
                <h3 class="generic-title">Gagal Verifikasi</h3>
            <?php endif; ?>

            <!-- Message -->
            <p class="generic-text"><?= htmlspecialchars($popupMessage) ?></p>

            <!-- Button -->
            <a href="<?= $redirectUrl ?>" class="btn-generic btn-primary-modal">
                <?php echo ($popupStatus == 'success' || $popupStatus == 'info') ? 'Login Sekarang' : 'Kembali'; ?>
            </a>
        </div>
    </div>

</body>

</html>