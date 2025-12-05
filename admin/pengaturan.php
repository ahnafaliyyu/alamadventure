<?php
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

// --- HELPER FUNCTION (Safe from Null) ---
if (!function_exists('getSetting')) {
    function getSetting($key)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        // Mengembalikan string kosong '' jika null, agar htmlspecialchars tidak error
        $data = $res->fetch_assoc();
        return $data['setting_value'] ?? '';
    }
}

$notification = null; // Variabel untuk notifikasi

// --- PROSES SIMPAN PENGATURAN ---
if (isset($_POST['save_settings'])) {
    $settings = [
        'shop_name' => $_POST['shop_name'],
        'shop_phone' => $_POST['shop_phone'],
        'shop_address' => $_POST['shop_address'],
        'rental_fine_percent' => $_POST['rental_fine_percent'],
        'landing_title' => $_POST['landing_title'],
        'landing_desc' => $_POST['landing_desc'],
        'stats_title' => $_POST['stats_title'],
        'stats_desc' => $_POST['stats_desc'],
        'stat_1_num' => $_POST['stat_1_num'],
        'stat_1_label' => $_POST['stat_1_label'],
        'stat_2_num' => $_POST['stat_2_num'],
        'stat_2_label' => $_POST['stat_2_label'],
        'stat_3_num' => $_POST['stat_3_num'],
        'stat_3_label' => $_POST['stat_3_label'],
    ];

    // Handle Upload Gambar
    $bg_image_path = getSetting('landing_bg_image');
    if (isset($_FILES['landing_bg_upload']) && $_FILES['landing_bg_upload']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['landing_bg_upload']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $newFilename = 'hero-bg-' . time() . '.' . $ext;
            $targetDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($targetDir))
                mkdir($targetDir, 0777, true);
            if (move_uploaded_file($_FILES['landing_bg_upload']['tmp_name'], $targetDir . $newFilename)) {
                $bg_image_path = 'public/uploads/' . $newFilename;
            }
        }
    } elseif (!empty($_POST['landing_bg_url'])) {
        $bg_image_path = $_POST['landing_bg_url'];
    }
    $settings['landing_bg_image'] = $bg_image_path;

    // SIMPAN KE DATABASE (Metode Pintar: Insert jika belum ada, Update jika sudah ada)
    // Kita gunakan loop manual query karena prepared statement di loop agak tricky untuk ON DUPLICATE
    foreach ($settings as $key => $val) {
        // Hapus karakter berbahaya manual karena kita pakai query langsung untuk ON DUPLICATE
        $safeKey = $conn->real_escape_string($key);
        $safeVal = $conn->real_escape_string($val);

        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$safeKey', '$safeVal') 
                ON DUPLICATE KEY UPDATE setting_value = '$safeVal'";
        $conn->query($sql);
    }

    // Set Notifikasi Sukses
    $notification = [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => 'Pengaturan website telah diperbarui.'
    ];
}

// --- PROSES GANTI PASSWORD ---
if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    if ($new_pass === $confirm_pass) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
        $stmt->bind_param("s", $hash);
        if ($stmt->execute()) {
            $notification = ['type' => 'success', 'title' => 'Sukses', 'message' => 'Password admin berhasil diubah.'];
        } else {
            $notification = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Terjadi kesalahan sistem.'];
        }
    } else {
        $notification = ['type' => 'error', 'title' => 'Error', 'message' => 'Konfirmasi password tidak cocok.'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label,
        .col label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--brand);
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            border-radius: 8px;
            font-family: "Inter", sans-serif;
            font-size: 14px;
            color: var(--text-main);
            transition: all 0.2s ease-in-out;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--brand);
            outline: none;
            box-shadow: 0 0 0 4px rgba(44, 69, 50, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        .row-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .row-group .col {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 600px) {
            .row-group {
                flex-direction: column;
                gap: 15px;
            }
        }

        input[type="file"].form-control {
            padding: 8px;
            background: #f8f9fa;
            border: 1px dashed #cbd5e1;
            cursor: pointer;
            height: auto;
        }

        input[type="file"]::file-selector-button {
            background-color: var(--brand);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            margin-right: 12px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.3s;
        }

        input[type="file"]::file-selector-button:hover {
            background-color: var(--brand-dark);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .card-box {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.03);
            margin-bottom: 15px;
        }

        .section-title {
            margin-top: 0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e2e8f0;
            color: var(--brand);
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--accent);
            background: rgba(249, 216, 74, 0.15);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 18px;
        }

        .preview-wrapper {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .preview-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }

        .preview-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 6px 12px;
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 992px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Floating Alert Animation */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        .floating-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 9999;
            border-left: 5px solid #ddd;
            min-width: 300px;
            animation: slideInRight 0.5s ease-out forwards;
        }

        .floating-alert.hide {
            animation: fadeOut 0.5s ease-in forwards;
        }

        .floating-alert.success {
            border-left-color: #2e7d32;
        }

        .floating-alert.error {
            border-left-color: #c62828;
        }

        .alert-icon {
            font-size: 24px;
        }

        .success .alert-icon {
            color: #2e7d32;
        }

        .error .alert-icon {
            color: #c62828;
        }

        .alert-content h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .alert-content p {
            margin: 2px 0 0;
            font-size: 13px;
            color: #666;
        }

        .btn-close-alert {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <?php if ($notification): ?>
        <div class="floating-alert <?= $notification['type'] ?>" id="floatAlert">
            <div class="alert-icon">
                <?php if ($notification['type'] == 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php endif; ?>
            </div>
            <div class="alert-content">
                <h4><?= $notification['title'] ?></h4>
                <p><?= $notification['message'] ?></p>
            </div>
            <button class="btn-close-alert" onclick="document.getElementById('floatAlert').classList.add('hide')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <script>
            setTimeout(() => {
                const alert = document.getElementById('floatAlert');
                if (alert) alert.classList.add('hide');
            }, 4000);
        </script>
    <?php endif; ?>

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
                <li class="Beranda">
                    <a href="../index.php">
                        <i class="fa-solid fa-house"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="Logout">
                    <a href="logout.php">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="btn-toggle-sidebar" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                    <h1>Pengaturan Website</h1>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="settings-grid">
                    <div class="left-col">

                        <div class="card-box">
                            <h3 class="section-title"><i class="fa-solid fa-desktop"></i> Landing Page (Hero)</h3>

                            <div class="form-group">
                                <label>Judul Utama (H1)</label>
                                <input type="text" name="landing_title" class="form-control"
                                    value="<?= htmlspecialchars(getSetting('landing_title') ?? '') ?>">
                                <small>Gunakan tag &lt;br&gt; untuk baris baru.</small>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi Singkat</label>
                                <textarea name="landing_desc" class="form-control"
                                    rows="2"><?= htmlspecialchars(getSetting('landing_desc') ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Gambar Background</label>
                                <?php
                                $bg = getSetting('landing_bg_image');
                                $src = (strpos($bg, 'http') === 0) ? $bg : '../' . $bg;
                                ?>
                                <div class="preview-wrapper">
                                    <img src="<?= $src ?>" class="preview-img">
                                    <div class="preview-label">Background Saat Ini</div>
                                </div>
                                <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                                    <div style="flex:1;">
                                        <label>Upload File</label>
                                        <input type="file" name="landing_bg_upload" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div style="flex:1;">
                                        <label>Atau URL</label>
                                        <input type="text" name="landing_bg_url" class="form-control"
                                            style="height: 51px;" placeholder="https://...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-box">
                            <h3 class="section-title"><i class="fa-solid fa-chart-simple"></i> Statistik (Kenapa Kami)
                            </h3>

                            <div class="form-group">
                                <label>Judul Section</label>
                                <input type="text" name="stats_title" class="form-control"
                                    value="<?= htmlspecialchars(getSetting('stats_title') ?? '') ?>">
                                <small>Gunakan tag &lt;strong&gt;text&lt;/strong&gt; untuk pertebal huruf.</small>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi Section</label>
                                <textarea name="stats_desc" class="form-control"
                                    rows="2"><?= htmlspecialchars(getSetting('stats_desc') ?? '') ?></textarea>
                            </div>

                            <hr style="border:0; border-top:1px dashed #e2e8f0; margin: 20px 0;">

                            <div class="row-group">
                                <div class="col">
                                    <label>Angka Stat 1</label>
                                    <input type="text" name="stat_1_num" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_1_num') ?? '') ?>">
                                </div>
                                <div class="col">
                                    <label>Label Stat 1</label>
                                    <input type="text" name="stat_1_label" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_1_label') ?? '') ?>">
                                </div>
                            </div>

                            <div class="row-group">
                                <div class="col">
                                    <label>Angka Stat 2</label>
                                    <input type="text" name="stat_2_num" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_2_num') ?? '') ?>">
                                </div>
                                <div class="col">
                                    <label>Label Stat 2</label>
                                    <input type="text" name="stat_2_label" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_2_label') ?? '') ?>">
                                </div>
                            </div>

                            <div class="row-group">
                                <div class="col">
                                    <label>Angka Stat 3</label>
                                    <input type="text" name="stat_3_num" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_3_num') ?? '') ?>">
                                </div>
                                <div class="col">
                                    <label>Label Stat 3</label>
                                    <input type="text" name="stat_3_label" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('stat_3_label') ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card-box">
                            <h3 class="section-title"><i class="fa-solid fa-store"></i> Info Toko</h3>
                            <div class="row-group">
                                <div class="col">
                                    <label>Nama Toko</label>
                                    <input type="text" name="shop_name" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('shop_name') ?? '') ?>" required>
                                </div>
                                <div class="col">
                                    <label>No. WA Admin</label>
                                    <input type="text" name="shop_phone" class="form-control"
                                        value="<?= htmlspecialchars(getSetting('shop_phone') ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="shop_address" class="form-control"
                                    rows="2"><?= htmlspecialchars(getSetting('shop_address') ?? '') ?></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Denda (%)</label>
                                <input type="number" name="rental_fine_percent" class="form-control"
                                    value="<?= htmlspecialchars(getSetting('rental_fine_percent') ?? '') ?>"
                                    style="width:100px;">
                                <small style="font-size: 12px">Denda dihitung perhari keterlambatan</small>
                            </div>
                        </div>

                        <button type="submit" name="save_settings" class="btn btn-primary"
                            style="padding:15px; font-size:16px;">
                            <i class="fa-solid fa-save"></i> SIMPAN SEMUA PERUBAHAN
                        </button>
                    </div>

                    <div class="right-col">
                        <div class="card-box">
                            <h3 class="section-title"><i class="fa-solid fa-lock"></i> Ganti Password</h3>
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-edit"
                                style="font-size: 14px;">Ubah
                                Password</button>
                        </div>
                    </div>
                </div>
            </form>
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