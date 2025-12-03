<?php
// api/midtrans_webhook.php

// 1. Load Konfigurasi Database & Midtrans
require_once __DIR__ . '/../config/database.php'; // Koneksi MySQLi ($conn)
require_once __DIR__ . '/../config/midtrans.php'; // Config Server Key

// LOGGING: Debugging
// file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Webhook Triggered\n", FILE_APPEND);

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
    $new_status = 'paid'; // Sukses
} else if (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
    $new_status = 'failed'; // Gagal
}

// 5. Update Database & BUAT FAKTUR RENTAL
if ($new_status == 'paid') {

    // Mulai Transaksi Database
    $conn->begin_transaction();

    try {
        // Cek status sekarang (biar gak double insert kalau notifikasi dikirim 2x)
        $stmtCek = $conn->prepare("SELECT status FROM orders WHERE order_code = ?");
        $stmtCek->bind_param("s", $order_id);
        $stmtCek->execute();
        $resCek = $stmtCek->get_result();
        $currRow = $resCek->fetch_assoc();
        $currStatus = $currRow ? $currRow['status'] : null;

        if ($currStatus !== 'paid') {
            // A. UPDATE STATUS ORDER
            $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            // B. AMBIL DATA ORDER (Termasuk durasi sewa)
            $stmtOrder = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
            $stmtOrder->bind_param("s", $order_id);
            $stmtOrder->execute();
            $order = $stmtOrder->get_result()->fetch_assoc();

            // --- [BARU] C. AMBIL DETAIL ITEM BARANG UNTUK WA ---
            $stmtItems = $conn->prepare("
                SELECT p.name, oi.qty 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmtItems->bind_param("i", $order['id']);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();

            $list_barang = ""; // String penampung list barang
            $total_qty_calculated = 0; // Hitung ulang total qty

            while ($item = $resItems->fetch_assoc()) {
                $list_barang .= "- " . $item['name'] . " (" . $item['qty'] . " pcs)\n";
                $total_qty_calculated += $item['qty'];
            }
            // ---------------------------------------------------

            // D. BUAT NOMOR FAKTUR RENTAL
            // Format: INV-RENT/TahunBulanTanggal/Random
            $inv_no = "INV-RENT/" . date('ymd') . "/" . rand(100, 999);
            $duration_text = $order['duration_days'] . " Hari";
            $order_type = 'Peminjaman Barang Camping';
            $sig_admin = 'Admin Rental';
            $sig_cust = $order['customer_name'];

            // INSERT KE TABEL INVOICES
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
                $total_qty_calculated, // Gunakan hasil hitung loop di atas
                $payment_type,
                $sig_cust,
                $sig_admin
            );
            $stmtInv->execute();

            // E. PERSIAPAN DATA WHATSAPP
            // Format Rupiah
            $formatted_amount = "Rp " . number_format($gross_amount, 0, ',', '.');

            // Link Maps
            $shop_map_link = "https://www.google.com/maps?q=-0.502183,117.153801"; // Ganti Koordinat Toko Anda
            $buyer_map_link = "";
            if ($order['delivery_method'] === 'delivery' && !empty($order['delivery_lat'])) {
                $buyer_map_link = "https://www.google.com/maps?q=" . $order['delivery_lat'] . "," . $order['delivery_long'];
            }

            // Link Faktur (Sesuaikan URL Ngrok/Domain)
            $ngrok_url = "hhttps://d23f9303ec2b.ngrok-free.app";
            $link_faktur = $ngrok_url . "/invoice.php?order=" . $order_id;


            // 1. KIRIM KE ADMIN (Dengan Detail Barang)
            $pesan_admin = "*PEMBAYARAN MASUK!* 💰\n";
            $pesan_admin .= "Faktur: $inv_no\n";
            $pesan_admin .= "Penyewa: " . $order['customer_name'] . "\n";
            $pesan_admin .= "Total: " . $formatted_amount . "\n";
            $pesan_admin .= "Durasi: " . $order['duration_days'] . " Hari\n\n";

            $pesan_admin .= "*Rincian Sewa:*\n";
            $pesan_admin .= $list_barang . "\n"; // Masukkan list barang disini

            $pesan_admin .= "Metode: " . strtoupper($order['delivery_method']) . "\n";

            if ($order['delivery_method'] === 'delivery') {
                $pesan_admin .= "📍 Lokasi Antar: $buyer_map_link\n";
                $pesan_admin .= "Alamat: " . $order['delivery_address'] . "\n";
            }
            $pesan_admin .= "Cek Dashboard untuk memproses.";

            $nomor_admin = "082241559607"; // Ganti nomor admin
            sendWhatsApp($nomor_admin, $pesan_admin);


            // 2. KIRIM KE PENYEWA (Dengan Detail Barang)
            $nomor_penyewa = $order['customer_phone'];
            if ($nomor_penyewa) {
                $pesan_user = "*PEMBAYARAN BERHASIL* ✅\n";
                $pesan_user .= "Halo " . $order['customer_name'] . ",\n";
                $pesan_user .= "Pembayaran untuk sewa alat camping telah kami terima.\n\n";

                $pesan_user .= "🧾 *No Faktur:* $inv_no\n";
                $pesan_user .= "📅 *Durasi:* " . $order['duration_days'] . " Hari\n\n";

                $pesan_user .= "*Barang yang disewa:*\n";
                $pesan_user .= $list_barang . "\n"; // Masukkan list barang disini

                if ($order['delivery_method'] === 'pickup') {
                    $pesan_user .= "Silakan ambil barang di lokasi kami:\n";
                    $pesan_user .= "📍 Lokasi Toko: $shop_map_link\n\n";
                } else {
                    $pesan_user .= "Barang akan segera kami proses untuk pengantaran.\n\n";
                }

                $pesan_user .= "🔗 Unduh Bukti Faktur: $link_faktur";
                sendWhatsApp($nomor_penyewa, $pesan_user);
            }

            $conn->commit();
            // file_put_contents('webhook_log.txt', " - SUKSES: Faktur $inv_no dibuat.\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        $conn->rollback();
        // file_put_contents('webhook_log.txt', " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
    }
}

// 6. FUNGSI KIRIM WA (Fonnte)
function sendWhatsApp($target, $message)
{
    $token = "pMEu6MFUdc2f9zQ3JzQk"; // Pastikan Token Benar

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

http_response_code(200);
echo "Webhook Processed";
?>