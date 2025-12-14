<?php
// api/midtrans_webhook.php

// 1. Load Konfigurasi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

// --- FUNGSI LOGGING (Penting untuk cek error) ---
function writeLog($msg)
{
    file_put_contents(__DIR__ . '/wa_log.txt', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// --- FUNGSI FORMAT NOMOR HP (08xx -> 628xx) ---
function formatNomor($nomor)
{
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 2) == '08') {
        $nomor = '62' . substr($nomor, 1);
    }
    return $nomor;
}

// Cek Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result)
    die("Invalid JSON");

$order_id = $result['order_id'];
$transaction_status = $result['transaction_status'];
$gross_amount = $result['gross_amount'];
$payment_type = $result['payment_type'];
$status_code = $result['status_code'];

// Validasi Signature Midtrans
$serverKey = \Midtrans\Config::$serverKey;
$my_signature_key = hash('sha512', $order_id . $status_code . $gross_amount . $serverKey);

if ($my_signature_key !== $result['signature_key']) {
    writeLog("Signature Key Salah: $order_id");
    http_response_code(403);
    die("Invalid Signature");
}

// Tentukan Status Baru
$new_status = 'pending';
if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
    $new_status = 'paid';
} else if (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
    $new_status = 'failed';
}

// --- LOGIKA UTAMA ---
if ($new_status == 'paid') {

    // Cek Data Order
    $stmtCek = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmtCek->bind_param("s", $order_id);
    $stmtCek->execute();
    $order = $stmtCek->get_result()->fetch_assoc();

    // Hanya proses jika status di database belum 'paid'
    if ($order && $order['status'] !== 'paid') {

        $conn->begin_transaction();

        try {
            // 1. UPDATE STATUS DI DB
            $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            // 2. AMBIL LIST ITEM (Untuk pesan WA)
            $stmtItems = $conn->prepare("SELECT p.name, oi.qty FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmtItems->bind_param("i", $order['id']);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();

            $list_barang = "";
            $total_qty = 0;
            while ($item = $resItems->fetch_assoc()) {
                $list_barang .= "- " . $item['name'] . " (" . $item['qty'] . " pcs)\n";
                $total_qty += $item['qty'];
            }

            // 3. BUAT/CEK INVOICE
            $inv_no = "";
            $cekInv = $conn->query("SELECT invoice_no FROM invoices WHERE order_code = '$order_id'");
            if ($cekInv->num_rows > 0) {
                $inv_no = $cekInv->fetch_assoc()['invoice_no'];
            } else {
                $inv_no = "INV-RENT/" . date('ymd') . "/" . rand(100, 999);
                $stmtInv = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, customer_name, order_type, duration, total_qty, payment_method, signature_customer, signature_admin) VALUES (?, ?, ?, 'Peminjaman Barang', ?, ?, ?, ?, 'Admin')");
                $durText = $order['duration_days'] . " Hari";
                $stmtInv->bind_param("ssssiss", $inv_no, $order_id, $order['customer_name'], $durText, $total_qty, $payment_type, $order['customer_name']);
                $stmtInv->execute();
            }

            // 4. COMMIT DATABASE (Simpan Data Dulu!)
            $conn->commit();
            writeLog("Database Berhasil Commit: $order_id");

            // ==========================================
            // 5. PERSIAPAN PESAN WHATSAPP (Format Baru)
            // ==========================================

            // Variabel Pendukung
            $formatted_amount = "Rp " . number_format($gross_amount, 0, ',', '.');
            $base_url = "https://9b5c644a336d.ngrok-free.app"; // GANTI URL NGROK/DOMAIN
            $link_faktur = $base_url . "/invoice.php?order=" . $order_id;

            // Lokasi Toko & Buyer
            $shop_map_link = "https://maps.google.com/?q=-0.502183,117.153801"; // Ganti Koordinat Toko Anda
            $buyer_map_link = "";
            if (!empty($order['delivery_lat']) && !empty($order['delivery_long'])) {
                $buyer_map_link = "https://www.google.com/maps?q=" . $order['delivery_lat'] . "," . $order['delivery_long'];
            }

            // --- A. FORMAT PESAN ADMIN ---
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
            $pesan_admin .= "🔗 Faktur: $link_faktur";
            $pesan_admin .= "Cek Dashboard untuk memproses: " . "\n" . "https://95816cc257c9.ngrok-free.app/transaksi-admin";

            // Kirim ke Admin
            $nomor_admin = "082241559607"; // Nomor Admin Utama
            sendWhatsApp($nomor_admin, $pesan_admin);


            // --- B. FORMAT PESAN CUSTOMER ---
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

        } catch (Exception $e) {
            $conn->rollback();
            writeLog("CRITICAL ROLLBACK: " . $e->getMessage());
            http_response_code(500);
        }
    } else {
        writeLog("Order skip (sudah paid/tidak ada): $order_id");
    }
} elseif ($new_status == 'failed') {
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE order_code = '$order_id'");
    writeLog("Order Cancelled: $order_id");
}

// --- FUNGSI KIRIM WA (ROBUST / ANTI GAGAL) ---
function sendWhatsApp($target, $message)
{
    // 1. Format Nomor (Auto ganti 08 ke 62)
    $target_formatted = formatNomor($target);

    // 2. Token Fonnte
    $token = "pMEu6MFUdc2f9zQ3JzQk";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        // PENTING: Bypass SSL agar jalan di localhost/ngrok
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POSTFIELDS => array(
            'target' => $target_formatted,
            'message' => $message,
        ),
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token"
        ),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        writeLog("WA Error ke $target_formatted: $error");
    } else {
        writeLog("WA Sukses ke $target_formatted. Resp: $response");
    }
}

http_response_code(200);
echo "Webhook OK";
?>