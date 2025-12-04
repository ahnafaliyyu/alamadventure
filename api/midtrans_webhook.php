<?php
// api/midtrans_webhook.php

// 1. Load Konfigurasi Database & Midtrans
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

// Pastikan Method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

// 2. Ambil Data JSON dari Midtrans
$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result) {
    die("Data tidak valid");
}

$order_id = $result['order_id'];
$transaction_status = $result['transaction_status'];
$gross_amount = $result['gross_amount'];
$payment_type = $result['payment_type'];
$status_code = $result['status_code'];

// 3. VALIDASI KEAMANAN (Signature Key)
$serverKey = \Midtrans\Config::$serverKey;
$my_signature_key = hash('sha512', $order_id . $status_code . $gross_amount . $serverKey);

if ($my_signature_key !== $result['signature_key']) {
    http_response_code(403);
    die("Signature Key Tidak Valid!");
}

// 4. Cek Status Transaksi
$new_status = 'pending';
if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
    $new_status = 'paid';
} else if (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
    $new_status = 'failed';
}

// 5. PROSES DATABASE
if ($new_status == 'paid') {

    // Cek dulu status sekarang agar tidak proses ganda
    $stmtCek = $conn->prepare("SELECT status, id, customer_name, customer_phone, duration_days, delivery_method, delivery_address, delivery_lat, delivery_long FROM orders WHERE order_code = ?");
    $stmtCek->bind_param("s", $order_id);
    $stmtCek->execute();
    $resCek = $stmtCek->get_result();
    $order = $resCek->fetch_assoc();

    if ($order && $order['status'] !== 'paid') {

        $conn->begin_transaction(); // Mulai Transaksi DB

        try {
            // A. UPDATE STATUS ORDER
            $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            // B. AMBIL DETAIL ITEM BARANG (Untuk Invoice & WA)
            $stmtItems = $conn->prepare("
                SELECT p.name, oi.qty 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmtItems->bind_param("i", $order['id']);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();

            $list_barang = "";
            $total_qty_calculated = 0;

            while ($item = $resItems->fetch_assoc()) {
                $list_barang .= "- " . $item['name'] . " (" . $item['qty'] . " pcs)\n";
                $total_qty_calculated += $item['qty'];
            }

            // C. BUAT REKORD INVOICE (Cek jika belum ada)
            $cekInv = $conn->prepare("SELECT id FROM invoices WHERE order_code = ?");
            $cekInv->bind_param("s", $order_id);
            $cekInv->execute();
            if ($cekInv->get_result()->num_rows == 0) {
                $inv_no = "INV-RENT/" . date('ymd') . "/" . rand(100, 999);
                $duration_text = $order['duration_days'] . " Hari";
                $order_type = 'Peminjaman Barang Camping';
                $sig_admin = 'Admin Rental';

                $stmtInv = $conn->prepare("INSERT INTO invoices 
                    (invoice_no, order_code, customer_name, order_type, duration, total_qty, payment_method, signature_customer, signature_admin) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmtInv->bind_param(
                    "sssssisss",
                    $inv_no,
                    $order_id,
                    $order['customer_name'],
                    $order_type,
                    $duration_text,
                    $total_qty_calculated,
                    $payment_type,
                    $order['customer_name'],
                    $sig_admin
                );
                $stmtInv->execute();
            } else {
                // Ambil nomor invoice yg sudah ada untuk WA
                $invData = $conn->query("SELECT invoice_no FROM invoices WHERE order_code = '$order_id'")->fetch_assoc();
                $inv_no = $invData['invoice_no'];
            }

            // --- PENTING: COMMIT DATABASE TERLEBIH DAHULU ---
            // Kita simpan perubahan status SEBELUM kirim WA.
            // Jadi kalau WA gagal, status pembayaran TETAP BERHASIL.
            $conn->commit();

            // ==========================================
            // D. KIRIM WHATSAPP (Di luar Transaksi DB)
            // ==========================================
            try {
                $formatted_amount = "Rp " . number_format($gross_amount, 0, ',', '.');

                // Link Maps
                $shop_map_link = "https://maps.google.com/?q=-0.502183,117.153801"; // Koordinat Toko (Contoh)
                $buyer_map_link = "";
                if ($order['delivery_method'] === 'delivery' && !empty($order['delivery_lat'])) {
                    $buyer_map_link = "https://maps.google.com/?q=" . $order['delivery_lat'] . "," . $order['delivery_long'];
                }

                // Link Faktur (Sesuaikan URL Domain Anda)
                // Ganti URL Ngrok ini dengan domain asli saat production
                $base_url = "https://015e3956031b.ngrok-free.app";
                $link_faktur = $base_url . "/invoice.php?order=" . $order_id;

                // 1. KIRIM KE ADMIN
                $pesan_admin = "*PEMBAYARAN MASUK!* 💰\n";
                $pesan_admin .= "Faktur: $inv_no\n";
                $pesan_admin .= "Penyewa: " . $order['customer_name'] . "\n";
                $pesan_admin .= "Total: " . $formatted_amount . "\n";
                $pesan_admin .= "Durasi: " . $order['duration_days'] . " Hari\n\n";
                $pesan_admin .= "*Rincian Sewa:*\n" . $list_barang . "\n";
                $pesan_admin .= "Metode: " . strtoupper($order['delivery_method']) . "\n";

                if ($order['delivery_method'] === 'delivery') {
                    $pesan_admin .= "📍 Lokasi Antar: $buyer_map_link\n";
                    $pesan_admin .= "Alamat: " . $order['delivery_address'] . "\n";
                }
                $pesan_admin .= "Cek Dashboard untuk memproses.";

                $nomor_admin = getSetting('shop_phone');
                if (empty($nomor_admin))
                    $nomor_admin = "082241559607";
                sendWhatsApp($nomor_admin, $pesan_admin);

                // 2. KIRIM KE CUSTOMER
                $nomor_penyewa = $order['customer_phone'];
                if ($nomor_penyewa) {
                    $pesan_user = "*PEMBAYARAN BERHASIL* ✅\n";
                    $pesan_user .= "Halo " . $order['customer_name'] . ",\n";
                    $pesan_user .= "Pembayaran sewa alat camping diterima.\n\n";
                    $pesan_user .= "🧾 *No Faktur:* $inv_no\n";
                    $pesan_user .= "*Barang:* \n" . $list_barang . "\n";

                    if ($order['delivery_method'] === 'pickup') {
                        $pesan_user .= "Silakan ambil barang di toko:\n📍 $shop_map_link\n\n";
                    } else {
                        $pesan_user .= "Barang segera kami antar 🛵.\n\n";
                    }
                    $pesan_user .= "🔗 Faktur: $link_faktur";
                    sendWhatsApp($nomor_penyewa, $pesan_user);
                }

            } catch (Exception $eWa) {
                // Jika WA gagal, biarkan saja (jangan rollback database)
                // Anda bisa menambahkan logging error di sini
                // file_put_contents('wa_error_log.txt', $eWa->getMessage(), FILE_APPEND);
            }

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            die("Database Error");
        }
    }
} elseif ($new_status == 'failed') {
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_code = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
}

// 6. FUNGSI KIRIM WA (Fonnte)
function sendWhatsApp($target, $message)
{
    $token = "pMEu6MFUdc2f9zQ3JzQk"; // Pastikan Token Valid

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 5, // Set timeout agar tidak blocking terlalu lama
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

http_response_code(200);
echo "Webhook Processed";
?>