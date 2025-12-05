<?php
require 'config/init.php';

// --- 1. CEK LOGIN (WAJIB) ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'keranjang.php';
    echo "<script>alert('Silakan login terlebih dahulu untuk melanjutkan pemesanan.'); window.location='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: keranjang.php");
    exit;
}

// Ambil Data User dari Session
$user_id = $_SESSION['user_id'];
// Data nama & hp diambil dari form checkout (agar bisa diubah jika perlu) atau session
$name = htmlspecialchars($_POST['customer_name']);
$phone = htmlspecialchars($_POST['customer_phone']);

$payment_method = $_POST['payment_method'] ?? 'online';
$duration = (int) $_POST['duration'];
if ($duration < 1)
    $duration = 1;

// --- 2. DATA PENGIRIMAN ---
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

// --- 3. SIAPKAN LIST BARANG ---
$list_barang = "";

foreach ($_SESSION['cart'] as $id => $item) {
    $item_total = $item['price'] * $item['qty'] * $duration;
    $total_barang += $item_total;

    // String untuk pesan WA
    $list_barang .= "- " . $item['name'] . " (" . $item['qty'] . " pcs)\n";

    // Array untuk Midtrans
    $item_details[] = [
        'id' => $id,
        'price' => $item['price'],
        'quantity' => $item['qty'] * $duration,
        'name' => substr($item['name'] . " ($duration Hari)", 0, 50)
    ];
}

// Tambahkan Ongkir sebagai Item Tambahan
if ($shipping_cost > 0) {
    $item_details[] = [
        'id' => 'ONGKIR',
        'price' => $shipping_cost,
        'quantity' => 1,
        'name' => 'Biaya Pengantaran'
    ];
}

$total_transaction = $total_barang + $shipping_cost;

// --- 4. SET WAKTU KADALUARSA (AUTO CANCEL) ---
if ($payment_method === 'cod') {
    // COD: Batas 24 Jam (Untuk proses antar/ambil)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $batas_pesan = "24 Jam";
} else {
    // ONLINE: Batas 2 Jam (Untuk segera transfer)
    $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
    $batas_pesan = "2 Jam";

    // // --- MODE TESTING (1 MENIT) ---
    // $expires_at = date('Y-m-d H:i:s', strtotime('+1 minute'));
    // $batas_pesan = "1 Menit";
}

$conn->begin_transaction();

try {
    // 5. SIMPAN ORDER (Dengan user_id & expires_at)
    $sql = "INSERT INTO orders (order_code, user_id, customer_name, customer_phone, total_amount, duration_days, status, payment_method, rental_status, delivery_method, delivery_address, delivery_lat, delivery_long, shipping_cost, expires_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 'pending_pickup', ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisdisssssdds", $order_code, $user_id, $name, $phone, $total_transaction, $duration, $payment_method, $delivery_method, $delivery_address, $lat, $long, $shipping_cost, $expires_at);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 6. SIMPAN ITEM
    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $pid => $item) {
        $stmtItem->bind_param("iiid", $order_id, $pid, $item['qty'], $item['price']);
        $stmtItem->execute();
    }

    // --- SETUP URL & PESAN WA ---
    $admin_phone = "082241559607"; // GANTI DENGAN NOMOR ADMIN
    $ngrok_url = "https://4695fb861470.ngrok-free.app"; // GANTI URL PUBLIK/NGROK BARU
    $link_faktur = $ngrok_url . "/invoice.php?order=" . $order_code;
    $formatted_amount = "Rp " . number_format($total_transaction, 0, ',', '.');

    $shop_map_link = "https://www.google.com/maps?q=-0.502183,117.153801";
    $buyer_map_link = "";
    if ($delivery_method === 'delivery' && $lat) {
        $buyer_map_link = "https://www.google.com/maps?q=$lat,$long";
    }

    // --- 7. PROSES PEMBAYARAN ---
    if ($payment_method === 'cod') {
        // --- COD FLOW ---

        // Buat Invoice Draft
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

        // A. Pesan WA Admin
        $msgAdmin = "*ORDER COD BARU!* 📦\n";
        $msgAdmin .= "Kode: $order_code\n";
        $msgAdmin .= "Nama: $name\n";
        $msgAdmin .= "Total: $formatted_amount\n";
        $msgAdmin .= "Durasi: $duration Hari\n\n";
        $msgAdmin .= "*Rincian Sewa:*\n$list_barang\n";
        $msgAdmin .= "Metode: " . strtoupper($delivery_method) . "\n";
        if ($delivery_method === 'delivery') {
            $msgAdmin .= "📍 Lokasi: $buyer_map_link\n";
            $msgAdmin .= "Alamat: $delivery_address\n";
        }
        $msgAdmin .= "👉 Cek Admin Panel.";
        sendWhatsApp($admin_phone, $msgAdmin);

        // B. Pesan WA User
        $msgUser = "*PESANAN DITERIMA* (COD) ✅\n";
        $msgUser .= "Halo $name,\n";
        $msgUser .= "Pesanan sewa alat camping Anda telah masuk.\n\n";
        $msgUser .= "🧾 Kode: $order_code\n";
        $msgUser .= "📅 Durasi: $duration Hari\n";
        $msgUser .= "⏳ Batas Waktu: $batas_pesan\n\n";
        $msgUser .= "*Barang:*\n$list_barang\n";
        $msgUser .= "Total Tagihan: $formatted_amount\n";
        $msgUser .= "Mohon siapkan uang pas saat terima barang.\n";

        if ($delivery_method === 'delivery') {
            $msgUser .= "🛵 Barang akan segera kami antar.\n";
        } else {
            $msgUser .= "🏪 Silakan ambil barang di toko kami:\n📍 $shop_map_link\n";
        }
        $msgUser .= "\n📄 Faktur: $link_faktur";
        sendWhatsApp($phone, $msgUser);

        // Redirect ke Invoice
        header("Location: invoice.php?order=$order_code");

    } else {
        // --- ONLINE (MIDTRANS) FLOW ---
        require 'config/midtrans.php';

        $params = [
            'transaction_details' => ['order_id' => $order_code, 'gross_amount' => $total_transaction],
            'customer_details' => ['first_name' => $name, 'phone' => $phone],
            'item_details' => $item_details,
            'expiry' => [
                'start_time' => date("Y-m-d H:i:s T"),

                // --- CODE ASLI (Disimpan sementara) ---
                'unit' => 'hours',
                'duration' => 2

                // // --- MODE TESTING (1 MENIT) ---
                // 'unit' => 'minutes',
                // 'duration' => 1
            ]
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $stmtUp = $conn->prepare("UPDATE orders SET snap_token = ? WHERE id = ?");
        $stmtUp->bind_param("si", $snapToken, $order_id);
        $stmtUp->execute();

        $conn->commit();
        unset($_SESSION['cart']);

        // Redirect ke Halaman Pembayaran
        header("Location: payment.php?order=$order_code");
    }

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

// Fungsi Kirim WA (Fonnte)
function sendWhatsApp($target, $message)
{
    $token = "pMEu6MFUdc2f9zQ3JzQk"; // GANTI TOKEN FONNTE

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