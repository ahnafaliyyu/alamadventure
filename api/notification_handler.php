<?php
// api/notification_handler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

// Pastikan hanya merespon POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die("Error: Halaman ini hanya menerima metode POST dari Webhook Midtrans.");
}

try {
    // 1. Ambil input JSON mentah
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);

    // 2. Validasi apakah JSON valid dan memiliki transaction_id
    if (!$input_data || !isset($input_data['transaction_id'])) {
        http_response_code(400); // Bad Request
        die("Error: Input JSON tidak valid atau transaction_id tidak ditemukan.");
    }

    // 3. Inisialisasi Notifikasi Midtrans
    // Library akan otomatis memverifikasi status ke Server Midtrans
    $notif = new \Midtrans\Notification(); 

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    // --- LOGIKA UPDATE DATABASE (Sama seperti sebelumnya) ---
    $transaction_status = null;
    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $transaction_status = 'challenge';
            } else {
                $transaction_status = 'success';
            }
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

    if ($transaction_status == 'success') {
        // Update Order jadi PAID
        $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        
        // Buat Invoice jika belum ada
        $checkInv = $conn->prepare("SELECT id FROM invoices WHERE order_code = ?");
        $checkInv->bind_param("s", $order_id);
        $checkInv->execute();
        $checkInv->store_result();

        if ($checkInv->num_rows == 0) {
            $invNo = 'INV/'.date('Ymd').'/'.rand(1000,9999);
            $stmtInv = $conn->prepare("INSERT INTO invoices (invoice_no, order_code, payment_method) VALUES (?, ?, ?)");
            $stmtInv->bind_param("sss", $invNo, $order_id, $type);
            $stmtInv->execute();
        }
    } else if ($transaction_status == 'expired' || $transaction_status == 'cancelled') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_code = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
    }

    echo "Notification processed: $transaction_status";

} catch (Exception $e) {
    // Tangkap error jika Midtrans API gagal dihubungi (misal koneksi putus atau config salah)
    http_response_code(500);
    echo "Error processing notification: " . $e->getMessage();
}
?>