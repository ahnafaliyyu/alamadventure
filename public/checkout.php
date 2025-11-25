<?php
require 'config.php';

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['pay'])) {
    validate_csrf();
    
    $name = htmlspecialchars($_POST['name']);
    // Perbaikan: Pastikan input form menggunakan name="phone" agar terbaca di sini
    $phone = htmlspecialchars($_POST['phone']); 
    $duration = (int)$_POST['duration']; 
    
    if ($duration < 1) $duration = 1; 

    $order_code = 'RENT-' . time() . rand(100,999); 
    
    // 1. Hitung Total (Harga x Qty x Hari)
    $total = 0;
    $item_details = [];

    foreach ($_SESSION['cart'] as $id => $item) {
        $subtotal = $item['price'] * $item['qty'] * $duration; 
        $total += $subtotal;

        $item_details[] = [
            'id' => $id,
            'price' => $item['price'], 
            'quantity' => $item['qty'] * $duration, 
            'name' => substr($item['name'] . " ($duration Hari)", 0, 50)
        ];
    }

    try {
        $pdo->beginTransaction();

        // 2. Simpan ke Database
        $stmt = $pdo->prepare("INSERT INTO orders (order_code, total_amount, customer_name, customer_phone, status, duration_days) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$order_code, $total, $name, $phone, $duration]);
        $order_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $pid => $item) {
            $stmtItem->execute([$order_id, $pid, $item['qty'], $item['price']]);
        }

        $pdo->commit();

        // 3. Minta Snap Token
        $params = [
            'transaction_details' => [
                'order_id' => $order_code,
                'gross_amount' => $total,
            ],
            'customer_details' => [
                'first_name' => $name,
                'phone' => $phone,
            ],
            'item_details' => $item_details,
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        unset($_SESSION['cart']);
        header("Location: payment_page.php?token=$snapToken&order=$order_code");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>

<h2>Form Sewa Alat Camping</h2>

<div style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">
    <h4>Barang yang disewa:</h4>
    <ul>
    <?php foreach ($_SESSION['cart'] as $item): ?>
        <li><?= e($item['name']) ?> x <?= $item['qty'] ?> (@ <?= formatRupiah($item['price']) ?>/hari)</li>
    <?php endforeach; ?>
    </ul>
</div>

<form method="POST">
    <?= csrf_field() ?>
    
    <label>Nama Penyewa:</label><br>
    <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 10px;"><br>
    
    <label>No WhatsApp:</label><br>
    <input type="text" name="phone" required style="width: 100%; padding: 8px; margin-bottom: 10px;" placeholder="Contoh: 08123456789"><br>

    <label>Lama Sewa (Hari):</label><br>
    <input type="number" name="duration" value="1" min="1" required style="width: 100%; padding: 8px; margin-bottom: 20px;">
    
    <br>
    <button type="submit" name="pay" class="btn" style="background: orange; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        Hitung Total & Bayar
    </button>
</form>