<?php
// Gunakan require_once agar aman
require_once 'config/init.php'; 

// Hapus Item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: keranjang.php");
    exit;
}

// Update Qty (Logic tambahan untuk membatasi input manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        if(isset($_SESSION['cart'][$pid])) {
            $q = (int)$q;
            if($q < 1) $q = 1;
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
    <link rel="stylesheet" href="./public/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .cart-table th { background: #333; color: #fff; padding: 12px; text-align: left; }
        .cart-table td { border-bottom: 1px solid #ddd; padding: 15px; }
        .cart-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 10px; }
        .checkout-box { background: #f8f9fa; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 15px; }
        .btn-checkout { background: #ffca28; color: #000; padding: 15px; width: 100%; border: none; font-weight: bold; cursor: pointer; border-radius: 5px; }
        .btn-checkout:hover { background: #ffc107; }
        .stock-warning { color: red; font-size: 11px; display: block; }
        .flex-row { display: flex; align-items: center; }
        
        /* Navbar specific fix for cart page */
        .nav { display: flex; justify-content: space-between; align-items: center; background-color: #222; padding: 15px 20px; }
    </style>
</head>
<body>

    <nav class="nav">
        <div class="desktop-nav">
            <div class="logo">
                <img src="/public/logo.png" width="30px" alt="Logo" />
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Beranda</a></li>
                <li><a href="tentang-kami.php" class="nav-link">Tentang Kami</a></li>
                <li><a href="katalog.php" class="nav-link">Katalog</a></li>
                <li><a href="kontak.php" class="nav-link">Kontak</a></li>
            </ul>
        </div>
        <div class="btn-kanan">
            <a href="keranjang.php" class="nav-link active">
                <i class="fas fa-shopping-cart"></i> 
                <span id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
            </a>
            <a href="/admin/login.php">Login</a>
        </div>
    </nav>
    
    <div class="cart-container">
        <h1>Keranjang Belanja</h1>
        <hr style="margin-bottom:20px; border:0; border-top:1px solid #eee;">

        <?php if (empty($_SESSION['cart'])): ?>
            <div style="text-align:center; padding:50px;">
                <h3>Keranjang Anda kosong.</h3>
                <a href="katalog.php" style="color:blue; text-decoration:underline;">Mulai Sewa Sekarang</a>
            </div>
        <?php else: ?>

            <form action="process_checkout.php" method="POST" id="checkoutForm">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga / Hari</th>
                            <th>Qty (Max Stok)</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        // Ambil ID produk di keranjang untuk dicek stok terbarunya
                        $ids = implode(',', array_keys($_SESSION['cart']));
                        
                        // Query Cek Stok Realtime
                        // Menggunakan LEFT JOIN agar jika produk dihapus tidak error
                        $sqlStock = "SELECT p.id, p.stock, 
                                    (p.stock - COALESCE((SELECT SUM(qty) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.product_id=p.id AND o.rental_status != 'returned' AND o.status != 'cancelled'), 0)) as avail 
                                    FROM products p WHERE p.id IN ($ids)";
                        
                        $resStock = $conn->query($sqlStock);
                        $stocks = [];
                        if($resStock) {
                            while($r = $resStock->fetch_assoc()) { $stocks[$r['id']] = $r['avail']; }
                        }

                        foreach ($_SESSION['cart'] as $id => $item):
                            $realStock = isset($stocks[$id]) ? $stocks[$id] : 0;
                            // Pastikan tidak minus
                            if($realStock < 0) $realStock = 0;

                            $subtotal = $item['price'] * $item['qty'];
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td style="display:flex; align-items:center;">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="cart-img" alt="Produk">
                                    <div>
                                        <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                        <?php if($realStock <= 0): ?>
                                            <span class="stock-warning"><i class="fas fa-exclamation-circle"></i> Stok Habis! Hapus item ini.</span>
                                        <?php elseif($item['qty'] > $realStock): ?>
                                            <span class="stock-warning">Stok sisa hanya <?= $realStock ?> unit</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>Rp <?= number_format($item['price'], 0,',','.') ?></td>
                                <td>
                                    <input type="number" 
                                           name="qty[<?= $id ?>]" 
                                           value="<?= $item['qty'] ?>" 
                                           min="1" 
                                           max="<?= $realStock ?>" 
                                           onchange="this.form.action='keranjang.php'; this.form.submit();"
                                           style="width: 60px; padding: 5px; text-align:center;"
                                           <?= $realStock <= 0 ? 'disabled' : '' ?> 
                                    >
                                    <input type="hidden" name="update_qty" value="1">
                                    <br>
                                    <small style="color:#666;">Tersedia: <?= $realStock ?></small>
                                </td>
                                <td>Rp <?= number_format($subtotal, 0,',','.') ?></td>
                                <td>
                                    <a href="?remove=<?= $id ?>" style="color:#e74c3c; text-decoration:none;" onclick="return confirm('Hapus item ini?')">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <tr style="background:#eee; font-weight:bold;">
                            <td colspan="3" style="text-align:right;">Total Per Hari:</td>
                            <td colspan="2">Rp <?= number_format($total, 0,',','.') ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="checkout-box">
                    <h3 style="margin-bottom:20px; border-bottom:1px solid #ddd; padding-bottom:10px;">Informasi Penyewa</h3>
                    
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Nama sesuai KTP">
                    </div>
                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" name="customer_phone" class="form-control" required placeholder="Contoh: 08123456789">
                    </div>
                    <div class="form-group">
                        <label>Lama Sewa (Hari)</label>
                        <input type="number" name="duration" class="form-control" value="1" min="1" required>
                        <small style="color:#666;">Total biaya akan dikalikan dengan durasi sewa.</small>
                    </div>
                    
                    <div class="form-group" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom:20px;">
                        <label style="font-weight:bold; font-size:16px; display:block; margin-bottom:10px;">Metode Pembayaran</label>
                        
                        <div style="display:flex; gap:20px; flex-wrap:wrap;">
                            <label style="cursor: pointer; display:flex; align-items:center; gap:8px;">
                                <input type="radio" name="payment_method" value="online" checked> 
                                <span>Transfer / QRIS (Otomatis)</span>
                            </label>
                            
                            <label style="cursor: pointer; display:flex; align-items:center; gap:8px;">
                                <input type="radio" name="payment_method" value="cod"> 
                                <span>Bayar di Tempat (COD)</span>
                            </label>
                        </div>
                        
                        <div style="margin-top:10px; font-size:13px; color:#555; background:#f9f9f9; padding:10px; border-radius:5px;">
                            <i class="fas fa-info-circle"></i> Jika memilih <strong>COD</strong>, faktur akan langsung dibuat. Silakan datang ke toko untuk pengambilan barang dan pembayaran tunai.
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout">
                        PROSES PESANAN SEKARANG <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>