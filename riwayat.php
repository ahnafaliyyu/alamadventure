<?php
require 'config/init.php';
date_default_timezone_set('Asia/Makassar');

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Ambil Data User (Hanya untuk Sidebar Profil)
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya - Alam Adventure</title>
    <link rel="icon" href="public/logo.png" type="image/png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/main.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* === STYLING ASLI (TIDAK DIUBAH) === */
        body {
            background-color: #f4f7f5;
            padding-top: 100px;
            font-family: sans-serif;
        }

        .profile-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        /* Sidebar Styles */
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: #2c4532;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }

        .user-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .user-email {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info-list {
            text-align: left;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .info-item {
            margin-bottom: 15px;
            font-size: 14px;
        }

        .info-item i {
            width: 25px;
            color: #2c4532;
        }

        .btn-logout {
            display: block;
            padding: 12px;
            background: #ffebee;
            color: #c62828;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 20px;
            transition: 0.3s;
        }

        .btn-logout:hover {
            background: #ffcdd2;
        }

        /* History & Search Styles */
        .history-section h2 {
            font-size: 24px;
            color: #2c4532;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #2c4532;
        }

        /* Order Card Styles (Ini yang dipakai AJAX nanti) */
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #ccc;
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-3px);
        }

        .order-card.pending {
            border-left-color: #f9d84a;
        }

        .order-card.paid {
            border-left-color: #2e7d32;
        }

        .order-card.cancelled {
            border-left-color: #c62828;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }

        .order-code {
            font-weight: 700;
            color: #333;
        }

        .order-date {
            font-size: 12px;
            color: #888;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        .bg-pending {
            background: #fff8e1;
            color: #f57f17;
        }

        .bg-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .bg-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 14px;
        }

        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: #2c4532;
            margin-top: 10px;
            text-align: right;
        }

        .btn-action {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 10px;
        }

        .btn-pay {
            background: #2c4532;
            color: white;
        }

        .btn-invoice {
            background: #f9f9f9;
            color: #333;
            border: 1px solid #ddd;
        }

        /* Pagination Styles (Compatible with AJAX) */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 14px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: #2c4532;
            border-radius: 6px;
            background: white;
        }

        .pagination .active {
            background-color: #2c4532;
            color: white;
            border-color: #2c4532;
        }

        .pagination a:hover:not(.active) {
            background-color: #eee;
        }

        /* Loader */
        #loader {
            display: none;
            text-align: center;
            padding: 20px;
            color: #666;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <nav class="nav">
        <div class="desktop-nav">
            <div class="desktop-nav">
                <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="logo">
                    <img src="public/logo.png" width="30px" alt="Logo" />
                </div>

                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link">Beranda</a></li>
                    <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
                    <li><a href="katalog.php" class="nav-link">Katalog</a></li>
                    <li><a href="kontak.php" class="nav-link">Kontak</a></li>
                </ul>
            </div>
        </div>
        <div class="btn-kanan">
            <span style="font-weight:600; color:#fff;">Hi
                <?= htmlspecialchars(explode(' ', $userData['name'])[0]) ?></span>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-card">
            <div class="avatar-circle"><?= strtoupper(substr($userData['name'], 0, 1)) ?></div>
            <div class="user-name"><?= htmlspecialchars($userData['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($userData['email']) ?></div>
            <div class="info-list">
                <div class="info-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($userData['phone']) ?></div>
                <div class="info-item"><i class="fas fa-calendar"></i> Member sejak
                    <?= date('Y', strtotime($userData['created_at'])) ?>
                </div>
                <div class="info-item"><i class="fas fa-check-circle"></i>
                    <?= $userData['is_verified'] ? '<span style="color:green">Terverifikasi</span>' : '<span style="color:red">Belum Verifikasi</span>' ?>
                </div>
            </div>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>

        <div class="history-section">
            <h2>Riwayat Pesanan</h2>

            <div class="search-box">
                <input type="text" id="inputCari" placeholder="Cari Kode Transaksi..." autocomplete="off">
            </div>

            <div id="loader"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>

            <div id="dataContainer">
            </div>
        </div>
    </div>

    <div id="loginChoiceModal" class="login-modal-overlay">
        <div class="login-modal-content">
            <button class="btn-close-modal" onclick="closeLoginModal()">&times;</button>

            <div class="login-modal-header">
                <h3>Selamat Datang!</h3>
                <p>Silakan pilih cara masuk Anda</p>
            </div>

            <a href="login.php" class="option-user">
                <i class="fas fa-user-circle"></i> Masuk sebagai Pelanggan
            </a>

            <div class="modal-divider"><span>ATAU</span></div>

            <a href="admin/login.php" class="option-admin">
                <i class="fas fa-lock"></i> Masuk sebagai Admin
            </a>
        </div>
    </div>

    <script>
        $(document).ready(function () {

            // Fungsi panggil data
            function loadRiwayat(page, keyword) {
                $('#loader').show();
                $('#dataContainer').css('opacity', '0.3');

                $.ajax({
                    url: 'load_riwayat.php',
                    type: 'POST',
                    data: {
                        page: page,
                        keyword: keyword
                    },
                    success: function (response) {
                        $('#dataContainer').html(response);
                        $('#loader').hide();
                        $('#dataContainer').css('opacity', '1');
                    },
                    error: function () {
                        alert('Gagal memuat data');
                        $('#loader').hide();
                    }
                });
            }

            // 1. Load Pertama kali (Halaman 1, keyword kosong)
            loadRiwayat(1, '');

            // 2. Event Ketik (Live Search)
            $('#inputCari').on('keyup', function () {
                var keyword = $(this).val();
                loadRiwayat(1, keyword); // Reset ke hal 1 setiap mencari
            });

            // 3. Event Klik Pagination (Delegation)
            // Kita pakai $(document).on karena tombol pagination dibuat dinamis oleh AJAX
            $(document).on('click', '.ajax-page', function (e) {
                e.preventDefault();
                var page = $(this).data('page');
                var keyword = $('#inputCari').val();

                loadRiwayat(page, keyword);

                // Scroll halus ke atas list
                $('html, body').animate({
                    scrollTop: $(".history-section").offset().top - 120
                }, 500);
            });

        });
    </script>

    <script src="public/js/nav.js"></script>

</body>

</html>