<?php
require 'config/init.php';

// Cek Login User
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Ambil Data User
$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();

// 2. Ambil Data Transaksi
$stmtTrx = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmtTrx->bind_param("i", $user_id);
$stmtTrx->execute();
$resultTrx = $stmtTrx->get_result();
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
    <style>
        body {
            background-color: #f4f7f5;
            padding-top: 100px;
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

        /* Sidebar Profil */
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

        /* Area Transaksi */
        .history-section h2 {
            font-size: 24px;
            color: #2c4532;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

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

        /* Status Border Colors */
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

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <nav class="nav">
        <div class="desktop-nav">
            <div class="logo">
                <img src="public/logo.png" width="30px" alt="Logo">
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Beranda</a></li>
                <li><a href="katalog.php" class="nav-link">Katalog</a></li>
            </ul>
        </div>
        <div class="btn-kanan">
            <span style="font-weight:600; color:#2c4532;">Hi,
                <?= htmlspecialchars(explode(' ', $userData['name'])[0]) ?></span>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-card">
            <div class="avatar-circle">
                <?= strtoupper(substr($userData['name'], 0, 1)) ?>
            </div>
            <div class="user-name"><?= htmlspecialchars($userData['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($userData['email']) ?></div>

            <div class="info-list">
                <div class="info-item">
                    <i class="fas fa-phone"></i> <?= htmlspecialchars($userData['phone']) ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar"></i> Member sejak <?= date('Y', strtotime($userData['created_at'])) ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-check-circle"></i>
                    <?= $userData['is_verified'] ? '<span style="color:green">Terverifikasi</span>' : '<span style="color:red">Belum Verifikasi</span>' ?>
                </div>
            </div>

            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>

        <div class="history-section">
            <h2>Riwayat Pesanan</h2>

            <?php if ($resultTrx->num_rows == 0): ?>
                <div style="text-align:center; padding:40px; color:#888;">
                    <i class="fas fa-shopping-basket" style="font-size:48px; margin-bottom:15px; color:#ddd;"></i>
                    <p>Belum ada pesanan.</p>
                    <a href="katalog.php" class="btn-action btn-pay">Mulai Sewa</a>
                </div>
            <?php else: ?>
                <?php while ($order = $resultTrx->fetch_assoc()): ?>
                    <?php
                    // Tentukan Class & Label Status
                    $status = $order['status'];
                    $statusClass = 'pending';
                    $statusLabel = 'Menunggu Pembayaran';
                    $badgeClass = 'bg-pending';

                    if ($status == 'paid') {
                        $statusClass = 'paid';
                        $statusLabel = 'Lunas / Sedang Disewa';
                        $badgeClass = 'bg-paid';
                        if ($order['rental_status'] == 'returned')
                            $statusLabel = 'Selesai (Dikembalikan)';
                    } elseif ($status == 'cancelled' || $status == 'failed') {
                        $statusClass = 'cancelled';
                        $statusLabel = 'Dibatalkan';
                        $badgeClass = 'bg-cancelled';
                    }
                    ?>

                    <div class="order-card <?= $statusClass ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-code">#<?= $order['order_code'] ?></div>
                                <div class="order-date"><i class="far fa-clock"></i>
                                    <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div>
                                <span class="status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </div>
                        </div>

                        <div class="order-details">
                            <div>
                                <strong>Metode Bayar:</strong> <?= strtoupper($order['payment_method']) ?><br>
                                <strong>Metode Ambil:</strong>
                                <?= $order['delivery_method'] == 'delivery' ? 'Diantar Kurir' : 'Ambil Sendiri' ?><br>
                                <strong>Durasi:</strong> <?= $order['duration_days'] ?> Hari
                            </div>
                            <div class="order-total">
                                Total: Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
                            </div>
                        </div>

                        <div style="margin-top:15px; text-align:right;">
                            <?php if ($order['status'] == 'pending'): ?>

                                <?php if ($order['payment_method'] == 'online'): ?>
                                    <a href="payment.php?order=<?= $order['order_code'] ?>" class="btn-action btn-pay">Bayar
                                        Sekarang</a>
                                    <small style="color:#c62828; display:block; margin-top:5px; font-weight:600;">
                                        <i class="fas fa-stopwatch"></i> Bayar sebelum:
                                        <?= $order['expires_at'] ? date('d M, H:i', strtotime($order['expires_at'])) : '-' ?>
                                    </small>

                                <?php else: ?>

                                    <a href="invoice.php?order=<?= $order['order_code'] ?>" class="btn-action btn-pay"
                                        style="background:#2980b9; color:white;">
                                        Lihat Invoice COD
                                    </a>

                                    <?php if ($order['delivery_method'] == 'pickup'): ?>
                                        <small style="color:#e67e22; display:block; margin-top:5px; font-weight:600;">
                                            <i class="fas fa-store"></i> Silakan ambil sebelum: <br>
                                            <?= date('d M, H:i', strtotime($order['expires_at'])) ?>
                                        </small>
                                    <?php else: ?>
                                        <small style="color:#27ae60; display:block; margin-top:5px; font-weight:600;">
                                            <i class="fas fa-motorcycle"></i> Sedang Proses Pengantaran
                                        </small>
                                    <?php endif; ?>

                                <?php endif; ?>

                            <?php elseif ($order['status'] == 'cancelled'): ?>
                                <div style="margin-top:10px; color:#999; font-size:13px; font-style:italic;">
                                    <i class="fas fa-times-circle"></i> Transaksi dibatalkan otomatis (Expired).
                                </div>

                            <?php elseif ($order['status'] == 'paid'): ?>
                                <a href="invoice.php?order=<?= $order['order_code'] ?>" target="_blank"
                                    class="btn-action btn-invoice">
                                    <i class="fas fa-print"></i> Cetak Invoice
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>