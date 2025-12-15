<?php
// alamadventure/invoice.php
require 'config/init.php';
require_once 'config/midtrans.php'; // Load config Midtrans untuk cek status

// --- [1] SET TIMEZONE DEFAULT ---
date_default_timezone_set('Asia/Makassar');

// 1. Ambil Order Code dari URL
$order_code = $_GET['order'] ?? '';
if (empty($order_code)) {
    die("Kode Order tidak ditemukan.");
}

// 2. Ambil Data Order & Invoice
$query = "SELECT o.*, i.invoice_no, i.payment_method as inv_method, i.signature_admin, i.created_at as invoice_date 
          FROM orders o
          LEFT JOIN invoices i ON o.order_code = i.order_code
          WHERE o.order_code = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $order_code);
$stmt->execute();
$result = $stmt->get_result();
$inv = $result->fetch_assoc();

// --- [LOGIKA BARU] AUTO-CHECK STATUS KE MIDTRANS ---
// Jika status di database masih PENDING dan bukan COD, kita tanya langsung ke Midtrans
if ($inv && $inv['status'] == 'pending' && $inv['payment_method'] == 'online') {
    try {
        // Cek status ke server Midtrans
        $status = \Midtrans\Transaction::status($order_code);
        $trx_status = $status->transaction_status;
        
        // Jika sudah sukses di sisi Midtrans (settlement/capture)
        if ($trx_status == 'settlement' || $trx_status == 'capture') {
            
            // 1. Update Status Order jadi PAID
            $upd = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
            $upd->bind_param("s", $order_code);
            $upd->execute();

            // 2. Buat Invoice jika belum ada
            if (empty($inv['invoice_no'])) {
                // Ambil item untuk hitung total qty (opsional, tp bagus untuk data)
                $resQty = $conn->query("SELECT SUM(qty) as tqty FROM order_items WHERE order_id = {$inv['id']}");
                $total_qty = $resQty->fetch_assoc()['tqty'] ?? 0;

                $invNo = 'INV/' . date('Ymd') . '/' . rand(1000, 9999);
                $durText = $inv['duration_days'] . " Hari";
                
                $ins = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, customer_name, order_type, duration, total_qty, payment_method, signature_customer, signature_admin) VALUES (?, ?, ?, 'Peminjaman Barang', ?, ?, 'online', ?, 'Admin')");
                $ins->bind_param("ssssis", $invNo, $order_code, $inv['customer_name'], $durText, $total_qty, $inv['customer_name']);
                $ins->execute();
            }

            // 3. Refresh Halaman agar tampilan berubah jadi LUNAS
            header("Refresh:0");
            exit;
        } elseif ($trx_status == 'expire' || $trx_status == 'cancel' || $trx_status == 'deny') {
            // Jika expired/gagal
            $conn->query("UPDATE orders SET status = 'cancelled' WHERE order_code = '$order_code'");
            header("Refresh:0");
            exit;
        }

    } catch (Exception $e) {
        // Jika koneksi ke Midtrans gagal (misal internet down), biarkan tetap pending
        // error_log($e->getMessage());
    }
}
// ---------------------------------------------------

// --- [2] PERBAIKAN WAKTU FAKTUR (UTC -> WITA) ---
if ($inv && !empty($inv['invoice_date'])) {
    try {
        $dt = new DateTime($inv['invoice_date'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Makassar'));
        $inv['invoice_date'] = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $inv['invoice_date'] = date('Y-m-d H:i:s', strtotime($inv['invoice_date']) + 28800);
    }
}

// --- LOGIKA TAMPILAN MENUNGGU ---
$is_cod = ($inv && $inv['payment_method'] === 'cod');

// Logika Auto-Refresh (Hanya jika Pending & Bukan COD)
if (!$inv || ($inv['status'] == 'pending' && !$is_cod)) {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
    echo "<h2>⏳ Menunggu Konfirmasi Pembayaran...</h2>";
    echo "<p>Sistem sedang memverifikasi pembayaran Anda secara otomatis.</p>";
    
    // Loader animasi sederhana
    echo "<div style='margin: 20px auto; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2c4532; border-radius: 50%; animation: spin 1s linear infinite;'></div>";
    echo "<style>@keyframes spin {0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); }}</style>";

    // Tombol Cek Manual (Backup jika auto refresh macet)
    echo "<a href='invoice.php?order=$order_code' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#2c4532; color:white; text-decoration:none; border-radius:5px;'>Cek Status Sekarang</a>";

    echo "<br><br>";
    echo "<p style='font-size:12px; color:#888;'>Jika sudah membayar tapi halaman tidak berubah,<br>silakan klik tombol di atas atau hubungi Admin.</p>";
    
    // Refresh otomatis setiap 5 detik
    echo "<meta http-equiv='refresh' content='5'>"; 
    echo "</div>";
    exit;
}

// 4. Ambil Item Produk
$queryItems = "SELECT p.name, oi.qty, oi.price 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$stmtItems = $conn->prepare($queryItems);
$stmtItems->bind_param("i", $inv['id']);
$stmtItems->execute();
$resItems = $stmtItems->get_result();
$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}

// Hitung baris kosong untuk layout
$min_rows = 6;
$current_rows = count($items);
$empty_rows = max(0, $min_rows - $current_rows);

// --- LABEL STATUS ---
if ($inv['status'] == 'paid') {
    $status_text = "LUNAS";
    $status_style = "color: #2c4532; border: 1px solid #2c4532; background: #e8f5e9;";
} elseif ($inv['status'] == 'cancelled') {
    $status_text = "DIBATALKAN";
    $status_style = "color: #c62828; border: 1px solid #c62828; background: #ffebee;";
} else {
    $status_text = "BELUM LUNAS";
    $status_style = "color: #d35400; border: 1px solid #d35400; background: #fff3e0;";
}

// Label Metode
$metode_text = strtoupper($inv['payment_method']);
if ($inv['payment_method'] === 'cod') $metode_text = "CASH ON DELIVERY";

// --- HITUNG ONGKIR & SUBTOTAL ---
$ongkir = $inv['shipping_cost'] ?? 0;
$subtotal_barang = $inv['total_amount'] - $ongkir;

// --- LOGIKA LOKASI MAPS ---
$shop_lat = "-0.5454512191833396";
$shop_long = "117.11993488175007"; // Koordinat Toko Anda (dari keranjang.php)

$maps_url = "";
$lokasi_label = "";

if ($inv['delivery_method'] == 'delivery') {
    $lokasi_label = "Lokasi Tujuan";
    $maps_url = "https://www.google.com/maps/search/?api=1&query=" . $inv['delivery_lat'] . "," . $inv['delivery_long'];
} else {
    $lokasi_label = "Lokasi Toko";
    $maps_url = "https://www.google.com/maps/search/?api=1&query=" . $shop_lat . "," . $shop_long;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Faktur - <?= htmlspecialchars($inv['invoice_no'] ?? 'RENTAL') ?></title>
    <style>
        /* Reset & Base */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #555; margin: 0; padding: 20px; font-size: 13px; color: #333; }
        .page-container { background: white; width: 210mm; min-height: 297mm; margin: auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); position: relative; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #2c4532; padding-bottom: 20px; }
        .logo-section h1 { margin: 0; font-size: 32px; color: #2c4532; font-weight: 800; letter-spacing: -0.5px; text-transform: uppercase; }
        .logo-section span { color: #f9d84a; }
        .company-info { margin-top: 5px; font-size: 12px; color: #555; line-height: 1.5; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #2c4532; text-align: right; margin-bottom: 5px; }
        .invoice-no { font-size: 14px; color: #666; text-align: right; font-family: monospace; }

        /* Customer Info */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px; }
        .info-box h3 { margin: 0 0 10px 0; font-size: 14px; color: #2c4532; border-bottom: 1px solid #eee; padding-bottom: 5px; text-transform: uppercase; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .label { color: #666; width: 100px; font-weight: 500; }

        /* Main Table */
        .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .main-table th { background-color: #2c4532; color: #ffffff; padding: 10px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; border: 1px solid #1f3225; }
        .main-table td { border: 1px solid #ddd; padding: 8px 10px; vertical-align: middle; }
        .main-table tr:nth-child(even) { background-color: #f9f9f9; }

        /* Footer & Totals */
        .footer-section { display: flex; justify-content: space-between; margin-top: 30px; }
        .notes { flex: 1; padding-right: 40px; font-size: 11px; color: #666; line-height: 1.6; }
        .notes strong { color: #2c4532; font-size: 12px; }
        .totals-box { width: 350px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .totals-table tr:last-child td { border-bottom: none; }
        .amount { text-align: right; font-weight: 600; }
        .grand-total { background-color: #2c4532; color: white; font-size: 16px; font-weight: bold; }

        /* Status Badge */
        .stamp-box { text-align: center; margin-top: 10px; border: 2px dashed #ccc; padding: 10px; border-radius: 8px; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 4px; font-weight: 800; font-size: 14px; letter-spacing: 1px; }

        .btn-download { position: fixed; bottom: 30px; right: 30px; background: #2c4532; color: white; padding: 15px 25px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); display: flex; align-items: center; gap: 10px; transition: 0.3s; text-decoration: none; }
        .btn-download:hover { transform: translateY(-3px); background: #1f3225; }
        
        .btn-home { position: fixed; bottom: 30px; left: 30px; background: #f9d84a; color: #333; padding: 15px 25px; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); text-decoration: none; }

        @media print {
            body { background: white; padding: 0; }
            .page-container { box-shadow: none; margin: 0; padding: 0; width: 100%; }
            .btn-download, .btn-home { display: none; }
        }
    </style>
</head>

<body>

    <a href="index.php" class="btn-home">🏠 Beranda</a>
    <button onclick="window.print()" class="btn-download">🖨️ Cetak Invoice</button>

    <div class="page-container">

        <div class="header">
            <div class="logo-section">
                <h1>Alam<span>Adventure</span></h1>
                <div class="company-info">
                    Sewa Alat Camping Terpercaya<br>
                    <?= htmlspecialchars(getSetting('shop_address')) ?><br>
                    WA: <?= htmlspecialchars(getSetting('shop_phone')) ?>
                </div>
            </div>
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-no">#<?= htmlspecialchars($inv['invoice_no'] ?? 'PENDING') ?></div>
                <div style="text-align:right; margin-top:5px; color:#666;">
                    <?= date('d F Y H:i', strtotime($inv['invoice_date'])) ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Info Penyewa</h3>
                <table class="info-table">
                    <tr>
                        <td class="label">Nama</td>
                        <td>: <strong><?= htmlspecialchars($inv['customer_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">No. HP</td>
                        <td>: <?= htmlspecialchars($inv['customer_phone']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Metode</td>
                        <td>: <?= htmlspecialchars($metode_text) ?></td>
                    </tr>
                </table>
            </div>
            <div class="info-box">
                <h3>Info Pengiriman</h3>
                <table class="info-table">
                    <tr>
                        <td class="label">Tipe</td>
                        <td>: 
                            <?php if ($inv['delivery_method'] == 'delivery'): ?>
                                <span style="color:#d35400; font-weight:bold;">Delivery (Diantar)</span>
                            <?php else: ?>
                                <span style="color:green; font-weight:bold;">Pickup (Ambil Sendiri)</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($inv['delivery_method'] == 'delivery' && !empty($inv['delivery_address'])): ?>
                        <tr>
                            <td class="label">Alamat</td>
                            <td>: <?= nl2br(htmlspecialchars($inv['delivery_address'])) ?></td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="label"><?= $lokasi_label ?></td>
                        <td>: 
                            <a href="<?= $maps_url ?>" target="_blank" style="color:#2980b9; text-decoration:none; font-weight:bold;">
                                📍 Lihat di Google Maps
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width:50%">Item & Deskripsi</th>
                    <th style="width:15%; text-align:right">Harga/Hari</th>
                    <th style="width:10%; text-align:center">Qty</th>
                    <th style="width:25%; text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $durasi = (int)$inv['duration_days'];
                    $subtotal = $item['price'] * $item['qty'] * $durasi;
                ?>
                <tr>
                    <td>
                        <b><?= htmlspecialchars($item['name']) ?></b>
                        <br><span style="font-size:11px; color:#666;">Sewa selama <?= $durasi ?> Hari</span>
                    </td>
                    <td style="text-align:right">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td style="text-align:center"><?= $item['qty'] ?></td>
                    <td style="text-align:right">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $empty_rows; $i++): ?>
                    <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="footer-section">
            <div class="notes">
                <strong>SYARAT & KETENTUAN:</strong>
                <ol style="margin-top:5px; padding-left:15px;">
                    <li>Wajib meninggalkan kartu identitas (KTP/SIM) asli sebagai jaminan.</li>
                    <li>Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa sepenuhnya.</li>
                    <li>Keterlambatan pengembalian dikenakan denda sesuai kebijakan toko.</li>
                    <li>Barang yang sudah diboking/dibayar tidak dapat dibatalkan (No Refund).</li>
                </ol>

                <div class="stamp-box">
                    <div>Status Pembayaran:</div>
                    <div class="status-badge" style="<?= $status_style ?>; margin-top:5px;">
                        <?= $status_text ?>
                    </div>
                </div>
            </div>

            <div class="totals-box">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal Barang</td>
                        <td class="amount">Rp <?= number_format($subtotal_barang, 0, ',', '.') ?></td>
                    </tr>
                    
                    <?php if($ongkir > 0): ?>
                    <tr>
                        <td>Biaya Pengantaran</td>
                        <td class="amount">Rp <?= number_format($ongkir, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr class="grand-total">
                        <td>TOTAL BAYAR</td>
                        <td class="amount" style="font-size:18px;">Rp <?= number_format($inv['total_amount'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</body>
</html>