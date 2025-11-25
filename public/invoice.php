<?php
require 'config.php'; // <-- BARIS WAJIB: Agar fungsi e() dan database bisa dipakai

// Ambil Order Code dari URL
$order_code = $_GET['order'] ?? '';

if (empty($order_code)) {
    die("Order ID tidak ditemukan.");
}

// Ambil Data Invoice & Order dari Database
$stmt = $pdo->prepare("SELECT i.*, o.total_amount, o.duration_days 
                       FROM invoices i 
                       JOIN orders o ON i.order_code = o.order_code 
                       WHERE i.order_code = ?");
$stmt->execute([$order_code]);
$inv = $stmt->fetch();

// FITUR AUTO-REFRESH: Jika data belum masuk (karena Webhook delay), refresh halaman
if (!$inv) {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
    echo "<h2>Sedang menerbitkan Faktur...</h2>";
    echo "<p>Mohon tunggu, halaman akan refresh otomatis dalam 3 detik.</p>";
    echo "<meta http-equiv='refresh' content='3'>"; // Refresh tiap 3 detik
    echo "</div>";
    exit;
}

// Ambil Item Barang yang Disewa
$stmtItems = $pdo->prepare("SELECT p.name, oi.qty, oi.price 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            JOIN orders o ON oi.order_id = o.id 
                            WHERE o.order_code = ?");
$stmtItems->execute([$order_code]);
$items = $stmtItems->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Faktur Sewa - <?= e($inv['invoice_no']) ?></title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; padding: 40px; background-color: #f4f4f4; }
        .invoice-box {
            max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee;
            background: white; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        h1 { text-align: center; color: #333; }
        .header-info { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        
        /* Tabel Rincian */
        .details-table { width: 100%; margin-bottom: 20px; }
        .details-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; width: 30%; }
        
        /* Tabel Barang */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .items-table th { background-color: #f8f9fa; }
        
        .total-area { text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold; }
        
        /* Area Tanda Tangan */
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; text-align: center; }
        .sig-box { width: 200px; }
        .sig-line { margin-top: 70px; border-top: 1px solid #333; font-weight: bold; padding-top: 5px; }
        
        /* Tombol Cetak (Hilang saat diprint) */
        @media print { 
            .no-print { display: none; } 
            body { background: white; padding: 0; }
            .invoice-box { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header-info">
        <h1>FAKTUR PENYEWAAN</h1>
        <p><strong>No. Faktur:</strong> <?= e($inv['invoice_no']) ?></p>
        <p><strong>Tanggal:</strong> <?= date('d F Y H:i', strtotime($inv['created_at'])) ?></p>
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Nama Penyewa</td>
            <td>: <?= e($inv['customer_name']) ?></td>
        </tr>
        <tr>
            <td class="label">Jenis Pesanan</td>
            <td>: <?= e($inv['order_type']) ?></td>
        </tr>
        <tr>
            <td class="label">Lama Sewa</td>
            <td>: <?= e($inv['duration']) ?></td> 
        </tr>
        <tr>
            <td class="label">Jumlah Barang</td>
            <td>: <?= e($inv['total_qty']) ?> Unit</td>
        </tr>
        <tr>
            <td class="label">Pembayaran</td>
            <td>: <?= strtoupper(e($inv['payment_method'])) ?> (LUNAS)</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Barang</th>
                <th>Harga / Hari</th>
                <th>Durasi</th>
                <th>Total Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['name']) ?></td>
                <td><?= formatRupiah($item['price']) ?></td>
                <td><?= e($inv['duration']) ?></td> <td><?= $item['qty'] ?></td>
                <td><?= formatRupiah($item['price'] * $item['qty'] * (int)$inv['duration']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-area">
        Total Bayar: <?= formatRupiah($inv['total_amount']) ?>
    </div>

    <div class="signatures">
        <div class="sig-box">
            <p>TTD Pemesan</p>
            <div class="sig-line"><?= e($inv['signature_customer']) ?></div>
        </div>

        <div class="sig-box">
            <p>TTD User (Admin)</p>
            <div class="sig-line"><?= e($inv['signature_admin']) ?></div>
        </div>
    </div>
    
    <center class="no-print" style="margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none;">Cetak Faktur</button>
        <a href="index.php" style="margin-left: 15px; text-decoration: none; color: #333;">Kembali ke Toko</a>
    </center>
</div>

</body>
</html>