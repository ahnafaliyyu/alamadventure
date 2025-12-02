<?php
// process_checkout.php
require 'config/init.php';

// JANGAN LOAD MIDTRANS DI SINI (Supaya COD tidak error jika library belum ada)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    die("Keranjang kosong.");
}

$name = htmlspecialchars($_POST['customer_name']);
$phone = htmlspecialchars($_POST['customer_phone']);
$payment_method = $_POST['payment_method'] ?? 'online';
$duration = (int) $_POST['duration'];
if ($duration < 1)
    $duration = 1;

$order_code = 'RENT-' . time() . rand(100, 999);
$total_transaction = 0;
$item_details = [];

foreach ($_SESSION['cart'] as $id => $item) {
    $item_total = $item['price'] * $item['qty'] * $duration;
    $total_transaction += $item_total;
    $item_details[] = [
        'id' => $id,
        'price' => $item['price'],
        'quantity' => $item['qty'] * $duration,
        'name' => substr($item['name'] . " ($duration Hari)", 0, 50)
    ];
}

$conn->begin_transaction();

try {
    // Simpan Order (Type string: sssdis)
    $stmt = $conn->prepare("INSERT INTO orders (order_code, customer_name, customer_phone, total_amount, duration_days, status, payment_method, rental_status) VALUES (?, ?, ?, ?, ?, 'pending', ?, 'pending_pickup')");
    $stmt->bind_param("sssdis", $order_code, $name, $phone, $total_transaction, $duration, $payment_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Simpan Item
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $item) {
        $stmtItem->bind_param("iiid", $order_id, $pid, $item['qty'], $item['price']);
        $stmtItem->execute();
    }

    // --- LOGIKA PERCABANGAN PEMBAYARAN ---
    if ($payment_method === 'cod') {
        // JIKA COD: Buat Invoice Draft (Type: ssssi)
        $invNo = 'INV/COD/' . date('Ymd') . '/' . rand(1000, 9999);
        $stmtInv = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, payment_method, customer_name, duration, total_qty, signature_admin) VALUES (?, ?, 'cod', ?, ?, ?, 'Belum Lunas')");

        $durStr = $duration . " Hari";
        $totQty = 0;
        foreach ($_SESSION['cart'] as $c)
            $totQty += $c['qty'];

        $stmtInv->bind_param("ssssi", $invNo, $order_code, $name, $durStr, $totQty);
        $stmtInv->execute();

        $conn->commit();
        unset($_SESSION['cart']);

        // LANGSUNG KE FAKTUR (Tanpa Midtrans)
        header("Location: invoice.php?order=$order_code");
        exit;

    } else {
        // JIKA ONLINE: Load Midtrans Disini
        if (!file_exists(__DIR__ . '/config/midtrans.php')) {
            throw new Exception("Library Midtrans belum siap.");
        }
        require 'config/midtrans.php';

        $params = [
            'transaction_details' => ['order_id' => $order_code, 'gross_amount' => $total_transaction],
            'customer_details' => ['first_name' => $name, 'phone' => $phone],
            'item_details' => $item_details,
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $stmtUp = $conn->prepare("UPDATE orders SET snap_token = ? WHERE id = ?");
        $stmtUp->bind_param("si", $snapToken, $order_id);
        $stmtUp->execute();

        $conn->commit();
        unset($_SESSION['cart']);

        header("Location: payment.php?order=$order_code");
        exit;
    }

} catch (Exception $e) {
    $conn->rollback();
    die("Terjadi kesalahan: " . $e->getMessage());
}
?>