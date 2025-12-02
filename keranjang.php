<?php
require_once 'config/init.php';

// Hapus Item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: keranjang.php");
    exit;
}

// Update Qty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        if (isset($_SESSION['cart'][$pid])) {
            $q = (int) $q;
            if ($q < 1)
                $q = 1;
            $_SESSION['cart'][$pid]['qty'] = $q;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Alam Adventure</title>
    <link rel="icon" href="/public/logo.png" type="image/png" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="./public/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand: #2c4532;
            --brand-dark: #1f3225;
            --accent: #f9d84a;
            --bg-color: #f9f5f0;
            --white: #ffffff;
            --text-main: #222;
            --text-muted: #666;
            --radius: 12px;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .cart-wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 60vh;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--brand);
            margin-bottom: 20px;
            border-bottom: 3px solid var(--accent);
            display: inline-block;
            padding-bottom: 5px;
        }

        /* Empty State */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .empty-cart i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .btn-link {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 2px solid var(--accent);
        }

        /* Table Styling */
        .table-responsive {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .cart-table th {
            background: var(--brand);
            color: var(--white);
            padding: 15px 20px;
            text-align: left;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .cart-table td {
            padding: 20px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .product-name {
            font-weight: 600;
            color: var(--brand-dark);
            display: block;
            margin-bottom: 4px;
        }

        .qty-input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-weight: 600;
        }

        .stock-status {
            font-size: 11px;
            color: var(--text-muted);
            display: block;
            margin-top: 5px;
        }

        .stock-warning {
            color: #d63031;
            font-weight: bold;
            font-size: 11px;
        }

        .btn-remove {
            color: #d63031;
            background: #fff0f0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #d63031;
            color: white;
        }

        /* Checkout Section */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
        }

        .card-box {
            background: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .section-heading {
            font-size: 18px;
            font-weight: 700;
            color: var(--brand);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
        }

        .form-control:focus {
            border-color: var(--brand);
            outline: none;
        }

        /* Payment Options */
        .payment-option {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-option:hover {
            background: #f9f9f9;
        }

        .payment-option input {
            accent-color: var(--brand);
            transform: scale(1.2);
        }

        /* Warning Box */
        .warning-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
            color: #856404;
            line-height: 1.5;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .btn-checkout {
            background: var(--accent);
            color: var(--brand-dark);
            padding: 15px;
            width: 100%;
            border: none;
            font-weight: 700;
            cursor: pointer;
            border-radius: 50px;
            font-size: 16px;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 10px rgba(249, 216, 74, 0.4);
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            background: #f0c33c;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .cart-table th,
            .cart-table td {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <nav class="nav">
        <div class="desktop-nav">
            <div class="logo"><img src="/public/logo.png" width="30px" alt="Logo" /></div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Beranda</a></li>
                <li><a href="katalog.php" class="nav-link">Katalog</a></li>
            </ul>
        </div>
        <div class="btn-kanan">
            <a href="keranjang.php" class="nav-link active"><i class="fas fa-shopping-cart"></i>
                <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></a>
        </div>
    </nav>

    <div class="cart-wrapper">
        <h1 class="page-title">Keranjang Belanja</h1>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <h3>Keranjang Anda kosong.</h3>
                <p>Belum ada barang yang disewa. Yuk pilih perlengkapanmu sekarang!</p>
                <br>
                <a href="katalog.php" class="btn-link">Mulai Sewa Sekarang <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php else: ?>

            <form action="process_checkout.php" method="POST" id="checkoutForm">

                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga / Hari</th>
                                <th style="text-align:center;">Qty</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = 0;
                            $ids = implode(',', array_keys($_SESSION['cart']));

                            $sqlStock = "SELECT p.id, p.stock, 
                                        (p.stock - COALESCE((SELECT SUM(qty) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id AND o.rental_status != 'returned' AND o.status != 'cancelled'), 0)) as avail 
                                        FROM products p WHERE p.id IN ($ids)";
                            $resStock = $conn->query($sqlStock);
                            $stocks = [];
                            if ($resStock)
                                while ($r = $resStock->fetch_assoc())
                                    $stocks[$r['id']] = $r['avail'];

                            foreach ($_SESSION['cart'] as $id => $item):
                                $realStock = isset($stocks[$id]) ? $stocks[$id] : 0;
                                if ($realStock < 0)
                                    $realStock = 0;

                                $subtotal = $item['price'] * $item['qty'];
                                $total += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <img src="<?= htmlspecialchars($item['image']) ?>" class="cart-img" alt="Produk">
                                            <div>
                                                <span class="product-name"><?= htmlspecialchars($item['name']) ?></span>
                                                <?php if ($realStock <= 0): ?>
                                                    <span class="stock-warning"><i class="fas fa-exclamation-circle"></i> Stok
                                                        Habis!</span>
                                                <?php elseif ($item['qty'] > $realStock): ?>
                                                    <span class="stock-warning">Stok hanya <?= $realStock ?> unit</span>
                                                <?php else: ?>
                                                    <span class="stock-status" style="color:green;"><i
                                                            class="fas fa-check-circle"></i> Tersedia: <?= $realStock ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                    <td style="text-align:center;">
                                        <input type="number" name="qty[<?= $id ?>]" value="<?= $item['qty'] ?>" min="1"
                                            max="<?= $realStock ?>"
                                            onchange="this.form.action='keranjang.php'; this.form.submit();" class="qty-input"
                                            <?= $realStock <= 0 ? 'disabled' : '' ?>>
                                        <input type="hidden" name="update_qty" value="1">
                                    </td>
                                    <td style="font-weight:bold;">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td>
                                        <a href="?remove=<?= $id ?>" class="btn-remove"
                                            onclick="return confirm('Hapus item ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="checkout-grid">
                    <div class="card-box">
                        <div class="section-heading"><i class="fas fa-user-edit"></i> Data Penyewa</div>
                        <div class="form-group">
                            <label>Nama Lengkap (Sesuai KTP)</label>
                            <input type="text" name="customer_name" class="form-control" required
                                placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp Aktif</label>
                            <input type="text" name="customer_phone" class="form-control" required
                                placeholder="Contoh: 08123456789">
                        </div>
                        <div class="form-group">
                            <label>Lama Sewa (Hari)</label>
                            <input type="number" name="duration" class="form-control" value="1" min="1" required>
                            <small style="color:var(--text-muted)">*Harga total akan dikalikan dengan durasi sewa.</small>
                        </div>
                    </div>

                    <div class="card-box" style="height: fit-content;">
                        <div class="section-heading"><i class="fas fa-wallet"></i> Pembayaran</div>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="online" checked>
                            <div>
                                <strong>Transfer / QRIS</strong>
                                <div style="font-size:12px; color:#666;">Otomatis via Midtrans</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod">
                            <div>
                                <strong>Bayar di Tempat (COD)</strong>
                                <div style="font-size:12px; color:#666;">Bayar tunai saat ambil barang</div>
                            </div>
                        </label>

                        <div class="warning-box">
                            <strong><i class="fas fa-exclamation-triangle"></i> PENTING!</strong>
                            Harap kembalikan barang tepat waktu sesuai durasi sewa.
                            Keterlambatan pengembalian akan dikenakan <u>denda harian</u> sebesar Rp 50.000 (atau sesuai
                            kebijakan toko) per hari keterlambatan.
                        </div>

                        <div
                            style="margin-top:20px; border-top:1px dashed #ddd; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                            <span>Total per Hari:</span>
                            <span style="font-size:20px; font-weight:800; color:var(--brand);">Rp
                                <?= number_format($total, 0, ',', '.') ?></span>
                        </div>

                        <button type="submit" class="btn-checkout">PROSES PESANAN <i
                                class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

            </form>
        <?php endif; ?>
    </div>
</body>

</html>