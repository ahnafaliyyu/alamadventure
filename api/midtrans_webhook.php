<?php
// api/midtrans_webhook.php

// 1. Load Konfigurasi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

// --- FUNGSI LOGGING ---
function writeLog($msg) {
    $logFile = __DIR__ . '/wa_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// --- FUNGSI FORMAT NOMOR HP ---
function formatNomor($nomor) {
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

if (!$result) {
    writeLog("Error: Invalid JSON Input");
    die("Invalid JSON");
}

$order_id = $result['order_id'] ?? '';
$transaction_status = $result['transaction_status'] ?? '';
$payment_type = $result['payment_type'] ?? '';
$gross_amount = $result['gross_amount'] ?? 0;

if (empty($order_id)) {
    writeLog("Error: Order ID kosong");
    die("No Order ID");
}

writeLog("Webhook Masuk: $order_id | Status: $transaction_status");

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

    // Proses jika order ada dan belum paid
    if ($order && $order['status'] !== 'paid') {
        
        $conn->begin_transaction();

        try {
            // 1. UPDATE STATUS DI DB
            $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();

            // 2. AMBIL LIST ITEM
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

            // 3. BUAT INVOICE (Jika belum ada)
            $cekInv = $conn->query("SELECT invoice_no FROM invoices WHERE order_code = '$order_id'");
            if ($cekInv->num_rows == 0) {
                $inv_no = "INV/" . date('Ymd') . "/" . rand(1000, 9999);
                $stmtInv = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, customer_name, order_type, duration, total_qty, payment_method, signature_customer, signature_admin) VALUES (?, ?, ?, 'Peminjaman Barang', ?, ?, ?, ?, 'Admin')");
                $durText = $order['duration_days'] . " Hari";
                $stmtInv->bind_param("ssssiss", $inv_no, $order_id, $order['customer_name'], $durText, $total_qty, $payment_type, $order['customer_name']);
                $stmtInv->execute();
            } else {
                $inv_no = $cekInv->fetch_assoc()['invoice_no'];
            }

            $conn->commit();
            writeLog("Database Berhasil Commit: $order_id");

            // ==========================================
            // 4. PERSIAPAN PESAN WHATSAPP
            // ==========================================

            // Auto Detect Base URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $base_url = "$protocol://$host"; 
            
            // Link Faktur
            // Asumsi folder project di root, hapus '/api' dari path jika perlu
            $clean_path = str_replace("/api", "", dirname($_SERVER['PHP_SELF'])); 
            // Jika di localhost/ngrok tanpa subfolder, clean_path mungkin kosong
            $link_faktur = $base_url . $clean_path . "/invoice.php?order=" . $order_id;
            // Perbaiki jika ada double slash
            $link_faktur = str_replace("api//invoice", "invoice", $link_faktur); 

            $formatted_amount = "Rp " . number_format($gross_amount, 0, ',', '.');

            // --- KOORDINAT LOKASI ---
            // Koordinat Toko (Samarinda)
            $shop_lat = "-0.5454512191833396";
            $shop_long = "117.11993488175007";
            $shop_map_link = "https://maps.google.com/?q=$shop_lat,$shop_long"; 
            
            // Koordinat Pembeli (Jika Delivery)
            $buyer_map_link = "";
            if (!empty($order['delivery_lat']) && !empty($order['delivery_long'])) {
                $buyer_map_link = "https://maps.google.com/?q=" . $order['delivery_lat'] . "," . $order['delivery_long'];
            }

            // --- A. KIRIM KE ADMIN ---
            $nomor_admin = "082241559607"; 
            $pesan_admin = "*PEMBAYARAN MASUK!* 💰\n";
            $pesan_admin .= "Faktur: $inv_no\n";
            $pesan_admin .= "Penyewa: " . $order['customer_name'] . "\n";
            $pesan_admin .= "Total: " . $formatted_amount . "\n";
            $pesan_admin .= "Metode: " . strtoupper($payment_type) . "\n";
            $pesan_admin .= "*Barang:*\n" . $list_barang;
            
            if ($order['delivery_method'] === 'delivery') {
                $pesan_admin .= "\n📍 *Lokasi Antar:* $buyer_map_link\n";
                $pesan_admin .= "Alamat: " . $order['delivery_address'] . "\n";
            } else {
                $pesan_admin .= "\nInfo: Customer akan ambil barang di toko.\n";
            }

            $pesan_admin .= "Cek Dashboard untuk memproses: " . "\n" . "https://95816cc257c9.ngrok-free.app/transaksi-admin";
            
            $logAdmin = sendFonnte($nomor_admin, $pesan_admin);
            writeLog("WA Admin: $logAdmin");

            // --- B. KIRIM KE CUSTOMER ---
            $nomor_penyewa = $order['customer_phone'];
            if ($nomor_penyewa) {
                $pesan_user = "*PEMBAYARAN BERHASIL* ✅\n";
                $pesan_user .= "Halo " . $order['customer_name'] . ",\n";
                $pesan_user .= "Terima kasih, pembayaran sewa alat camping telah kami terima.\n\n";
                $pesan_user .= "🧾 *No Invoice:* $inv_no\n";
                $pesan_user .= "*Rincian:*\n" . $list_barang . "\n";
                
                if ($order['delivery_method'] === 'delivery') {
                    $pesan_user .= "🛵 *Status:* Menunggu pengantaran kurir.\n";
                } else {
                    // JIKA AMBIL SENDIRI (PICKUP) -> TAMPILKAN ALAMAT TOKO
                    $pesan_user .= "📍 *Status:* Silakan ambil barang di toko kami.\n";
                    $pesan_user .= "🗺️ *Google Maps:* $shop_map_link\n";
                }
                
                $pesan_user .= "\nLihat Invoice: $link_faktur";
                
                $logUser = sendFonnte($nomor_penyewa, $pesan_user);
                writeLog("WA User ($nomor_penyewa): $logUser");
            }

        } catch (Exception $e) {
            $conn->rollback();
            writeLog("CRITICAL ROLLBACK: " . $e->getMessage());
            http_response_code(500);
        }
    } else {
        writeLog("Order skip (sudah paid atau tidak ditemukan): $order_id");
    }

} elseif ($new_status == 'failed') {
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE order_code = '$order_id'");
    writeLog("Order Cancelled: $order_id");
}

echo json_encode(['status' => 'ok']);
?>