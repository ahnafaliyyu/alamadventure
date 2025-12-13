<?php
// api/notification_handler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php'; // Memuat token & fungsi sendFonnte

// Pastikan hanya merespon POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Error: Halaman ini hanya menerima metode POST dari Webhook Midtrans.");
}

try {
    // 1. Ambil input JSON mentah
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);

    // 2. Validasi
    if (!$input_data || !isset($input_data['transaction_id'])) {
        http_response_code(400);
        die("Error: Input JSON tidak valid atau transaction_id tidak ditemukan.");
    }

    // 3. Inisialisasi Notifikasi Midtrans
    $notif = new \Midtrans\Notification();

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    // --- LOGIKA STATUS TRANSAKSI ---
    $transaction_status = null;
    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            $transaction_status = ($fraud == 'challenge') ? 'challenge' : 'success';
        }
    } else if ($transaction == 'settlement') {
        $transaction_status = 'success';
    } else if ($transaction == 'pending') {
        $transaction_status = 'pending';
    } else if ($transaction == 'deny') {
        $transaction_status = 'failed';
    } else if ($transaction == 'expire') {
        $transaction_status = 'expired';
    } else if ($transaction == 'cancel') {
        $transaction_status = 'cancelled';
    }

    // --- UPDATE DATABASE & KIRIM WA ---
    if ($transaction_status == 'success') {
        // Ambil data customer untuk kirim WA
        $stmt = $conn->prepare("SELECT customer_name, customer_phone FROM orders WHERE order_code = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        // Update Status jadi PAID
        $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
        $stmtUpdate->bind_param("s", $order_id);
        $stmtUpdate->execute();

        // Buat Invoice jika belum ada
        $checkInv = $conn->prepare("SELECT id FROM invoices WHERE order_code = ?");
        $checkInv->bind_param("s", $order_id);
        $checkInv->execute();
        $checkInv->store_result();

        if ($checkInv->num_rows == 0) {
            $invNo = 'INV/' . date('Ymd') . '/' . rand(1000, 9999);
            $stmtInv = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, payment_method) VALUES (?, ?, ?)");
            $stmtInv->bind_param("sss", $invNo, $order_id, $type);
            $stmtInv->execute();

            // KIRIM WA NOTIFIKASI
            if ($res) {
                $msg = "Halo *{$res['customer_name']}*,\n\nPembayaran untuk pesanan *#$order_id* telah kami terima. Terima kasih sudah mempercayakan kebutuhan camping Anda pada Alam Adventure.\n\nSimpan bukti ini sebagai referensi.";
                sendFonnte($res['customer_phone'], $msg);
            }
        }

    } else if ($transaction_status == 'expired' || $transaction_status == 'cancelled') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_code = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
    }

    echo "Notification processed: $transaction_status";

} catch (Exception $e) {
    http_response_code(500);
    echo "Error processing notification: " . $e->getMessage();
}
?>