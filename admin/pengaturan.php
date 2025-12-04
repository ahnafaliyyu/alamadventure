<?php
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

// --- PROSES SIMPAN PENGATURAN TOKO ---
if (isset($_POST['save_settings'])) {
    // 1. Ambil data dari Form
    $shop_name = $_POST['shop_name'];
    $shop_phone = $_POST['shop_phone'];
    $shop_address = $_POST['shop_address'];

    // Ambil input persentase denda (Pastikan name di form adalah 'rental_fine_percent')
    $rental_fine_percent = $_POST['rental_fine_percent'];

    // 2. Siapkan Array Data
    // Key array sebelah kiri ('shop_name', dll) HARUS SAMA PERSIS dengan kolom 'setting_key' di database
    $settings = [
        'shop_name' => $shop_name,
        'shop_phone' => $shop_phone,
        'shop_address' => $shop_address,
        'rental_fine_percent' => $rental_fine_percent // Simpan nilai persen
    ];

    // 3. Loop untuk Update ke Database
    foreach ($settings as $key => $val) {
        // Query UPDATE ini akan mencari baris berdasarkan setting_key dan mengubah isinya
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
    }

    $success_msg = "Pengaturan toko berhasil disimpan!";
}

// --- PROSES GANTI PASSWORD ADMIN ---
if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        // Update password admin (Asumsi single admin dengan username 'admin' atau session ID)
        $admin_username = 'admin';

        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hash, $admin_username);

        if ($stmt->execute()) {
            $pass_msg = "Password berhasil diubah!";
            $pass_type = "success";
        } else {
            $pass_msg = "Gagal mengubah password.";
            $pass_type = "error";
        }
    } else {
        $pass_msg = "Konfirmasi password tidak cocok.";
        $pass_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin Alam Adventure</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }

        .card-box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            color: #2c4532;
            font-size: 18px;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>ALAM<span style="color:#fff">ADVENTURE</span></h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
                <li><a href="produk.php"><i class="fa-solid fa-box-open"></i> <span>Produk</span></a></li>
                <li><a href="transaksi.php"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Transaksi</span></a>
                </li>
                <li><a href="pengaturan.php" class="active"><i class="fa-solid fa-gear"></i> <span>Pengaturan</span></a>
                </li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Keluar</span></a></li>
                <li class="beranda">
                    <a href="../index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Beranda</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Pengaturan</h1>
                </div>
            </div>

            <div class="settings-grid">
                <div class="card-box">
                    <h3 class="section-title"><i class="fa-solid fa-store"></i> Konfigurasi Toko</h3>

                    <?php if (isset($success_msg)): ?>
                        <div class="alert alert-success"><?= $success_msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Toko</label>
                            <input type="text" name="shop_name" value="<?= htmlspecialchars(getSetting('shop_name')) ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label>Nomor WhatsApp Admin (Format: 628...)</label>
                            <input type="text" name="shop_phone"
                                value="<?= htmlspecialchars(getSetting('shop_phone')) ?>" required>
                            <small style="color:#888;">Digunakan untuk notifikasi pesanan masuk.</small>
                        </div>

                        <div class="form-group">
                            <label>Alamat Toko</label>
                            <textarea name="shop_address" rows="3"
                                required><?= htmlspecialchars(getSetting('shop_address')) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Denda Keterlambatan (%) per Hari</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="number" name="rental_fine_percent"
                                    value="<?= htmlspecialchars(getSetting('rental_fine_percent')) ?>" required min="0"
                                    max="100" style="width: 80px;">
                                <span>% dari total harga sewa harian</span>
                            </div>
                            <small style="color:#888;">Contoh: Jika total sewa Rp 100.000/hari dan denda 50%, maka denda
                                = Rp 50.000/hari.</small>
                        </div>

                        <button type="submit" name="save_settings" class="btn btn-primary">Simpan Pengaturan</button>
                    </form>
                </div>

                <div class="card-box" style="height: fit-content;">
                    <h3 class="section-title"><i class="fa-solid fa-lock"></i> Keamanan Admin</h3>

                    <?php if (isset($pass_msg)): ?>
                        <div class="alert alert-<?= $pass_type ?>"><?= $pass_msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" required placeholder="Minimal 6 karakter">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" required placeholder="Ulangi password">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-edit">Ubah
                            Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
        if (toggleBtn) { toggleBtn.addEventListener('click', toggleSidebar); overlay.addEventListener('click', toggleSidebar); }
    </script>
</body>

</html>