<?php
// process_checkout.php
require 'config/init.php';
require 'config/midtrans.php'; // Pastikan file config midtrans yang dibuat sebelumnya ada

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    die("Keranjang kosong.");
}

$name = htmlspecialchars($_POST['customer_name']);
$phone = htmlspecialchars($_POST['customer_phone']);
$duration = (int)$_POST['duration'];
if ($duration < 1) $duration = 1;

$order_code = 'RENT-' . time() . rand(100, 999);
$total_transaction = 0;
$item_details = [];

// 1. Hitung Total & Siapkan Data Midtrans
foreach ($_SESSION['cart'] as $id => $item) {
    $item_total = $item['price'] * $item['qty'] * $duration;
    $total_transaction += $item_total;

    $item_details[] = [
        'id' => $id,
        'price' => $item['price'], // Midtrans minta harga satuan
        'quantity' => $item['qty'] * $duration, // Kita kalikan quantity dengan hari
        'name' => substr($item['name'] . " ($duration Hari)", 0, 50)
    ];
}

// 2. Database Transaction
$conn->begin_transaction();

try {
    // Simpan Order
    $stmt = $conn->prepare("INSERT INTO orders (order_code, customer_name, customer_phone, total_amount, duration_days, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssii", $order_code, $name, $phone, $total_transaction, $duration);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Simpan Order Items
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $item) {
        $stmtItem->bind_param("iiid", $order_id, $pid, $item['qty'], $item['price']);
        $stmtItem->execute();
    }

    // 3. Minta Snap Token Midtrans
    $params = [
        'transaction_details' => [
            'order_id' => $order_code,
            'gross_amount' => $total_transaction,
        ],
        'customer_details' => [
            'first_name' => $name,
            'phone' => $phone,
        ],
        'item_details' => $item_details,
    ];

    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Update Token di Database
    $stmtUp = $conn->prepare("UPDATE orders SET snap_token = ? WHERE id = ?");
    $stmtUp->bind_param("si", $snapToken, $order_id);
    $stmtUp->execute();

    $conn->commit();

    // Hapus keranjang
    unset($_SESSION['cart']);

    // Redirect ke halaman pembayaran
    header("Location: payment.php?order=$order_code");

} catch (Exception $e) {
    $conn->rollback();
    die("Terjadi kesalahan: " . $e->getMessage());
}
?>