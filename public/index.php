<?php
require 'config.php'; // <-- INI KUNCI PERBAIKANNYA

// Logika Tambah ke Keranjang
if (isset($_POST['add_to_cart'])) {
    validate_csrf(); // Cek keamanan token
    $id = (int)$_POST['product_id'];
    
    // Validasi produk di DB
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        $_SESSION['cart'][$id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'qty' => ($_SESSION['cart'][$id]['qty'] ?? 0) + 1
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Toko Aman Jaya</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .product, .cart { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        .btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border: none; cursor: pointer;}
    </style>
</head>
<body>

<h2>Daftar Produk</h2>
<div class="products">
    <?php
    $stmt = $pdo->query("SELECT * FROM products");
    while ($row = $stmt->fetch()) {
        echo "<div class='product'>";
        echo "<b>" . e($row['name']) . "</b> - " . formatRupiah($row['price']); // <-- 'e' dan 'formatRupiah' sekarang ada
        echo "<form method='POST' style='margin-top:5px;'>";
        echo csrf_field(); // <-- 'csrf_field' sekarang ada
        echo "<input type='hidden' name='product_id' value='".$row['id']."'>";
        echo "<button type='submit' name='add_to_cart' class='btn'>+ Beli</button>";
        echo "</form>";
        echo "</div>";
    }
    ?>
</div>

<hr>

<h2>Keranjang Belanja</h2>
<?php if (!empty($_SESSION['cart'])): ?>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr><th>Produk</th><th>Qty</th><th>Subtotal</th></tr>
        <?php 
        $total = 0;
        foreach ($_SESSION['cart'] as $id => $item): 
            $subtotal = $item['price'] * $item['qty'];
            $total += $subtotal;
        ?>
            <tr>
                <td><?= e($item['name']) ?></td>
                <td><?= $item['qty'] ?></td>
                <td><?= formatRupiah($subtotal) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2"><b>Total Belanja</b></td>
            <td><b><?= formatRupiah($total) ?></b></td>
        </tr>
    </table>
    <br>
    <a href="checkout.php" class="btn" style="background: green;">Lanjut ke Pembayaranclear</a>
<?php else: ?>
    <p>Keranjang kosong.</p>
<?php endif; ?>

</body>
</html>