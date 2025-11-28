<?php
require 'config/init.php'; // Load session & DB

// Hapus Item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: keranjang.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Alam Adventure</title>
    <link rel="stylesheet" href="./public/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            font-family: sans-serif;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .cart-table th {
            background: #333;
            color: #fff;
            padding: 12px;
            text-align: left;
        }

        .cart-table td {
            border-bottom: 1px solid #ddd;
            padding: 15px;
            vertical-align: middle;
        }

        .cart-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 10px;
        }

        .btn-del {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
        }

        .checkout-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .btn-checkout {
            background: #ffca28;
            color: #000;
            padding: 15px;
            width: 100%;
            border: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .btn-checkout:hover {
            background: #ffc107;
        }

        .flex-row {
            display: flex;
            align-items: center;
        }
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
                <li>
                    <a href="tentang-kami.php" class="nav-link">Tentang Kami</a>
                </li>
                <li><a href="katalog.php" class="nav-link">Katalog</a></li>
          <li><a href="kontak.php" class="nav-link">Kontak</a></li>
            </ul>
        </div>
        <div class="btn-kanan">
            <a href="keranjang.php" class="nav-link"><i
                    class="fas fa-shopping-cart"></i><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></a>
            <a href="/admin/login.php">Login</a>
        </div>
    </nav>

    <div class="cart-container">
        <h1>Keranjang Belanja</h1>
        <hr style="margin-bottom:20px; border:0; border-top:1px solid #eee;">

        <?php if (empty($_SESSION['cart'])): ?>
            <div style="text-align:center; padding:50px;">
                <h3>Keranjang Anda kosong.</h3>
                <a href="katalog.php" style="color:blue;">Mulai Sewa Sekarang</a>
            </div>
        <?php else: ?>

            <form action="process_checkout.php" method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga / Hari</th>
                            <th>Qty</th>
                            <th>Subtotal (1 Hari)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $id => $item):
                            $subtotal = $item['price'] * $item['qty'];
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td>
                                    <div class="flex-row">
                                        <img src="<?= e($item['image']) ?>" class="cart-img" alt="img">
                                        <strong><?= e($item['name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= formatRupiah($item['price']) ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td><?= formatRupiah($subtotal) ?></td>
                                <td>
                                    <a href="?remove=<?= $id ?>" class="btn-del"
                                        onclick="return confirm('Hapus item ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background:#eee; font-weight:bold;">
                            <td colspan="3" style="text-align:right;">Total Per Hari:</td>
                            <td colspan="2"><?= formatRupiah($total) ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="checkout-box">
                    <h3>Data Penyewa</h3>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Nama Anda" required>
                    </div>

                    <div class="form-group">
                        <label>Nomor WhatsApp</label>
                        <input type="text" name="customer_phone" class="form-control" placeholder="Contoh: 08123456789"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Lama Sewa (Hari)</label>
                        <input type="number" name="duration" class="form-control" value="1" min="1" required>
                        <small style="color:#666;">Total biaya akan dikalikan dengan durasi hari.</small>
                    </div>

                    <button type="submit" class="btn-checkout">
                        LANJUT KE PEMBAYARAN <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>

</body>

</html>