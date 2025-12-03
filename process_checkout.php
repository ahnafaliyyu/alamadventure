<?php
require 'config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: keranjang.php");
    exit;
}

$name = htmlspecialchars($_POST['customer_name']);
$phone = htmlspecialchars($_POST['customer_phone']);
$payment_method = $_POST['payment_method'] ?? 'online';
$duration = (int) $_POST['duration'];
if ($duration < 1)
    $duration = 1;

// --- DATA PENGIRIMAN ---
$delivery_method = $_POST['delivery_method'] ?? 'pickup';
$shipping_cost = 0;
$delivery_address = null;
$lat = null;
$long = null;

if ($delivery_method === 'delivery') {
    $shipping_cost = (int) $_POST['shipping_cost'];
    $delivery_address = htmlspecialchars($_POST['delivery_address']);
    $lat = $_POST['lat'];
    $long = $_POST['long'];
}

$order_code = 'RENT-' . time() . rand(100, 999);
$total_barang = 0;
$item_details = [];

// --- [MODIFIKASI 1] SIAPKAN VARIABEL LIST BARANG ---
$list_barang = "";

foreach ($_SESSION['cart'] as $id => $item) {
    $item_total = $item['price'] * $item['qty'] * $duration;
    $total_barang += $item_total;

    // Tambahkan ke string list barang untuk WA
    $list_barang .= "- " . $item['name'] . " (" . $item['qty'] . " pcs)\n";

    $item_details[] = [
        'id' => $id,
        'price' => $item['price'],
        'quantity' => $item['qty'] * $duration,
        'name' => substr($item['name'] . " ($duration Hari)", 0, 50)
    ];
}

// Tambahkan Ongkir sebagai Item Tambahan di Midtrans
if ($shipping_cost > 0) {
    $item_details[] = [
        'id' => 'ONGKIR',
        'price' => $shipping_cost,
        'quantity' => 1,
        'name' => 'Biaya Pengantaran'
    ];
}

// TOTAL FINAL
$total_transaction = $total_barang + $shipping_cost;

$conn->begin_transaction();

try {
    // 1. Simpan Order
    $sql = "INSERT INTO orders (order_code, customer_name, customer_phone, total_amount, duration_days, status, payment_method, rental_status, delivery_method, delivery_address, delivery_lat, delivery_long, shipping_cost) VALUES (?, ?, ?, ?, ?, 'pending', ?, 'pending_pickup', ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdisssssd", $order_code, $name, $phone, $total_transaction, $duration, $payment_method, $delivery_method, $delivery_address, $lat, $long, $shipping_cost);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 2. Simpan Item
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $item) {
        $stmtItem->bind_param("iiid", $order_id, $pid, $item['qty'], $item['price']);
        $stmtItem->execute();
    }

    // --- SETUP URL & LOKASI UNTUK WA ---
    $admin_phone = "082241559607"; // GANTI NOMOR ADMIN
    $ngrok_url = "https://d23f9303ec2b.ngrok-free.app"; // GANTI URL NGROK BARU
    $link_faktur = $ngrok_url . "/invoice.php?order=" . $order_code;
    $formatted_amount = "Rp " . number_format($total_transaction, 0, ',', '.');

    // Link Peta TOKO (Untuk Pembeli jika Pickup)
    $shop_map_link = "https://www.google.com/maps?q=-0.502183,117.153801";

    // Link Peta PEMBELI (Untuk Admin jika Delivery)
    $buyer_map_link = "";
    if ($delivery_method === 'delivery' && $lat) {
        $buyer_map_link = "https://www.google.com/maps?q=$lat,$long";
    }

    // 3. Proses Pembayaran
    if ($payment_method === 'cod') {
        // Buat Invoice Draft untuk COD
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

        // --- KIRIM WHATSAPP (COD) ---

        // --- [MODIFIKASI 2] UPDATE PESAN ADMIN ---
        $msgAdmin = "*ORDER COD BARU!* 📦\n";
        $msgAdmin .= "Kode: $order_code\n";
        $msgAdmin .= "Nama: $name\n";
        $msgAdmin .= "Total: $formatted_amount\n";
        $msgAdmin .= "Durasi: $duration Hari\n\n";

        $msgAdmin .= "*Rincian Sewa:*\n";
        $msgAdmin .= $list_barang . "\n"; // List barang dimasukkan disini

        $msgAdmin .= "Metode: " . strtoupper($delivery_method) . "\n";
        if ($delivery_method === 'delivery') {
            $msgAdmin .= "📍 Lokasi Antar: $buyer_map_link\n";
            $msgAdmin .= "Alamat: $delivery_address\n";
        }
        $msgAdmin .= "👉 Cek Admin Panel.";
        sendWhatsApp($admin_phone, $msgAdmin);

        // --- [MODIFIKASI 3] UPDATE PESAN USER ---
        $msgUser = "*PESANAN DITERIMA* (COD) ✅\n";
        $msgUser .= "Halo $name,\n";
        $msgUser .= "Pesanan sewa alat camping Anda telah masuk.\n\n";
        $msgUser .= "🧾 *Kode Order:* $order_code\n";
        $msgUser .= "📅 *Durasi:* $duration Hari\n\n";

        $msgUser .= "*Barang yang disewa:*\n";
        $msgUser .= $list_barang . "\n"; // List barang dimasukkan disini

        $msgUser .= "Total Tagihan: $formatted_amount\n";
        $msgUser .= "Mohon siapkan uang pas saat terima barang.\n";

        if ($delivery_method === 'delivery') {
            $msgUser .= "🛵 Barang akan segera kami antar ke lokasi Anda.\n";
        } else {
            $msgUser .= "🏪 Silakan ambil barang di toko kami:\n";
            $msgUser .= "📍 $shop_map_link\n";
        }
        $msgUser .= "\n📄 Faktur: $link_faktur";
        sendWhatsApp($phone, $msgUser);

        // Redirect
        header("Location: invoice.php?order=$order_code");

    } else {
        // ONLINE (Midtrans)
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
    }

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

// Fungsi Kirim WA (Fonnte)
function sendWhatsApp($target, $message)
{
    $token = "pMEu6MFUdc2f9zQ3JzQk"; // Token Fonnte

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $message,
        ),
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
}
?>