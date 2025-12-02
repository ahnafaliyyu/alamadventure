<?php
// alamadventure/invoice.php
require 'config/init.php';

// 1. Ambil Order Code dari URL
$order_code = $_GET['order'] ?? '';
if (empty($order_code)) {
    die("Kode Order tidak ditemukan.");
}

// 2. Ambil Data Order & Invoice dari Database
// Kita gunakan LEFT JOIN ke tabel invoices karena mungkin data invoice belum terbuat (jika delay webhook)
$query = "SELECT o.*, i.invoice_no, i.payment_method, i.signature_admin, i.created_at as invoice_date 
          FROM orders o
          LEFT JOIN invoices i ON o.order_code = i.order_code
          WHERE o.order_code = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $order_code);
$stmt->execute();
$result = $stmt->get_result();
$inv = $result->fetch_assoc();

// --- LOGIKA BARU (Pengecualian untuk COD) ---
$is_cod = ($inv && $inv['payment_method'] === 'cod');

// 3. Logika Auto-Refresh (Menunggu Webhook Masuk)
// LOGIKA: Jika data tidak ditemukan ATAU (status masih pending DAN BUKAN COD), suruh user tunggu.
// Jadi jika COD, meskipun status 'pending', dia akan lolos (tidak masuk if ini).
if (!$inv || ($inv['status'] == 'pending' && !$is_cod)) {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
    echo "<h2>⏳ Sedang Memverifikasi Pembayaran...</h2>";
    echo "<p>Sistem sedang menunggu konfirmasi otomatis dari Midtrans/Bank.</p>";
    echo "<p>Halaman ini akan refresh otomatis dalam 3 detik.</p>";
    // Script Auto Refresh
    echo "<meta http-equiv='refresh' content='3'>";
    echo "<a href='invoice.php?order=$order_code'>Klik di sini jika tidak refresh otomatis</a>";
    echo "</div>";
    exit;
}

// 4. Ambil Item Produk
$queryItems = "SELECT p.name, oi.qty, oi.price 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$stmtItems = $conn->prepare($queryItems);
$stmtItems->bind_param("i", $inv['id']); // Menggunakan ID Order
$stmtItems->execute();
$resItems = $stmtItems->get_result();
$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}

// Hitung baris kosong agar tabel terlihat panjang (estetika struk)
$min_rows = 6;
$current_rows = count($items);
$empty_rows = max(0, $min_rows - $current_rows);

// --- PERSIAPAN VARIABEL TAMPILAN DINAMIS ---
// Agar tidak mengubah struktur HTML di bawah secara drastis

// 1. Label Status
if ($inv['status'] == 'paid') {
    $status_text = "LUNAS";
    $status_style = "color: green;"; // Hijau
} else {
    // Jika COD dan belum lunas (pending)
    $status_text = "BELUM LUNAS (COD)";
    $status_style = "color: #d35400;"; // Merah Bata / Oranye Tua
}

// 2. Label Metode Pembayaran
$metode_text = strtoupper($inv['payment_method']);
if ($inv['payment_method'] === 'cod') {
    $metode_text = "CASH ON DELIVERY";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Faktur - <?= e($inv['invoice_no'] ?? 'RENTAL') ?></title>
</head>
<style>
    /* Reset & Base */
    body {
        font-family: Arial, sans-serif;
        background: #555;
        margin: 0;
        padding: 20px;
        font-size: 12px;
    }

    .page-container {
        background: white;
        width: 210mm;
        min-height: 297mm;
        /* A4 Size */
        margin: auto;
        padding: 15mm;
        box-sizing: border-box;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    /* Header Section */
    .header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .logo-section h1 {
        margin: 0;
        font-size: 36px;
        color: #333;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .logo-section span {
        color: #f39c12;
    }

    /* Warna Oranye logo */
    .company-info {
        margin-top: 5px;
        font-size: 11px;
        line-height: 1.4;
        color: #333;
    }

    .customer-info {
        text-align: right;
        font-size: 12px;
    }

    .customer-info table {
        float: right;
    }

    .customer-info td {
        padding: 2px 5px;
        text-align: left;
    }

    /* Main Table Style (Excel Look) */
    .main-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        border: 2px solid #000;
    }

    .main-table th {
        background-color: #f1c40f;
        /* Warna Kuning/Oranye Header */
        border: 1px solid #000;
        padding: 8px;
        text-align: center;
        font-weight: bold;
    }

    .main-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: middle;
        height: 25px;
    }

    /* Column Widths */
    .col-desc {
        width: 55%;
    }

    .col-price {
        width: 15%;
        text-align: right;
    }

    .col-qty {
        width: 10%;
        text-align: center;
    }

    .col-total {
        width: 20%;
        text-align: right;
    }

    /* Footer Section */
    .footer {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
    }

    /* Disclaimer (Kiri) */
    .disclaimer {
        width: 40%;
        font-size: 10px;
        color: #333;
        margin-top: 10px;
    }

    .warning-text {
        color: red;
        font-weight: bold;
    }

    /* Info Tengah (Kasir/Status) */
    .status-info {
        width: 25%;
        font-size: 12px;
        padding-top: 10px;
    }

    .status-info table td {
        padding: 3px;
    }

    .lunas-badge {
        background: #eee;
        padding: 2px 5px;
        font-weight: bold;
        /* Warna text dihapus dari sini, dipindah inline via PHP */
    }

    /* Total Box (Kanan) */
    .total-box {
        width: 30%;
    }

    .total-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #000;
    }

    .total-table td {
        border: 1px solid #000;
        padding: 5px 8px;
    }

    .total-header {
        background-color: #f1c40f;
        font-weight: bold;
        width: 40%;
    }

    .total-value {
        text-align: right;
    }

    /* Utility */
    .text-right {
        text-align: right;
    }

    .btn-download {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #e74c3c;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        transition: all 0.3s;
    }

    .btn-download:hover {
        transform: scale(1.05);
        background: #c0392b;
    }

    /* Print Settings */
    @media print {
        body {
            background: white;
            padding: 0;
        }

        .page-container {
            box-shadow: none;
            width: 100%;
            margin: 0;
            padding: 10mm;
        }

        .btn-download {
            display: none;
        }
    }
</style>

<body>

    <button onclick="window.print()" class="btn-download">🖨️ Cetak Faktur</button>

    <div class="page-container">

        <div class="header">
            <div class="logo-section">
                <h1>Alam<span>Adventure</span></h1>
                <div class="company-info">
                    Desain dan Penyewaan Alat Camping<br>
                    Wa: 0812-3456-7890<br>
                    Email: admin@toko.com<br>
                    Alamat: Samarinda
                </div>
                <div style="margin-top: 10px; font-size: 12px;">
                    No Kode : <strong><?= e($inv['invoice_no']) ?></strong>
                </div>
            </div>

            <div class="customer-info">
                <table>
                    <tr>
                        <td>Samarinda, </td>
                        <td><?= date('d F Y', strtotime($inv['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td>Kepada Yth :</td>
                        <td><strong><?= e($inv['customer_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>No HP :</td>
                        <td><?= e($inv['customer_phone']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th class="col-desc">KETERANGAN PRODUK</th>
                    <th class="col-price">HARGA</th>
                    <th class="col-qty">QTY</th>
                    <th class="col-total">JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item):
                    // Hitung subtotal (Harga x Qty x Durasi di Database Order)
                    // Asumsi: price di DB adalah harga per hari
                    $durasi = (int) $inv['duration_days'];
                    $subtotal = $item['price'] * $item['qty'] * $durasi;
                    ?>
                    <tr>
                        <td>
                            <?= e($item['name']) ?>
                            <span style="font-size:10px; color:#666;">(Sewa: <?= $durasi ?> Hari)</span>
                        </td>
                        <td class="text-right"><?= formatRupiah($item['price']) ?></td>
                        <td class="col-qty"><?= $item['qty'] ?></td>
                        <td class="text-right"><?= formatRupiah($subtotal) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $empty_rows; $i++): ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="disclaimer">
                <strong>KETENTUAN SEWA & DENDA:</strong><br>
                1. Mohon periksa kembali kondisi barang sebelum meninggalkan tempat.<br>
                2. Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa sepenuhnya.<br>
                <span style="color: #c0392b; font-weight: bold; background: #fff3cd; padding: 2px 5px;">
                    3. Keterlambatan pengembalian dikenakan DENDA Rp 50.000 / Hari.
                </span><br>
                4. Barang yang sudah disewa tidak dapat dikembalikan uang sewanya.
            </div>

            <div class="status-info">
                <table>
                    <tr>
                        <td>KETERANGAN</td>
                        <td>: <span class="lunas-badge" style="<?= $status_style ?>"><?= $status_text ?></span></td>
                    </tr>
                    <tr>
                        <td>METODE</td>
                        <td>: <?= e($metode_text) ?></td>
                    </tr>
                </table>
            </div>

            <div class="total-box">
                <table class="total-table">
                    <tr>
                        <td class="total-header">ONGKIR</td>
                        <td class="total-value"><?= formatRupiah(0) ?></td>
                    </tr>
                    <tr>
                        <td class="total-header">JUMLAH</td>
                        <td class="total-value" style="font-weight:bold; font-size:14px;">
                            <?= formatRupiah($inv['total_amount']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

</body>

</html>