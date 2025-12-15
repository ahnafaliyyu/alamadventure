<?php
// api/process_rental.php
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$order_id = $input['order_id'] ?? '';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

date_default_timezone_set('Asia/Makassar');

try {
    if (empty($action) || empty($order_id))
        throw new Exception("Data tidak lengkap.");

    if ($action === 'start_rent') {
        // --- MULAI SEWA (PICKUP) ---
        // Mencatat waktu pengambilan barang (actual_pickup_date) saat ini (NOW)

        $cek = $conn->prepare("SELECT payment_method FROM orders WHERE id = ?");
        $cek->bind_param("i", $order_id);
        $cek->execute();
        $res = $cek->get_result()->fetch_assoc();

        // Jika COD, set lunas saat diambil. Jika Online, biarkan status bayar.
        // PENTING: Set actual_pickup_date = NOW()
        if ($res && $res['payment_method'] === 'cod') {
            $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing', status = 'paid', actual_pickup_date = NOW() WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing', actual_pickup_date = NOW() WHERE id = ?");
        }

        $stmt->bind_param("i", $order_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Barang diserahkan. Waktu sewa dimulai sekarang.']);
        } else {
            throw new Exception("Gagal update database.");
        }

    } elseif ($action === 'finish_rent') {
        // --- SELESAI SEWA (RETURN) ---

        // 1. Ambil Setting Denda
        $resSet = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'rental_fine_percent'");
        $fine_percent = ($resSet && $rowSet = $resSet->fetch_assoc()) ? (int) $rowSet['setting_value'] : 50;

        // 2. Ambil Data Order & Waktu Pickup
        // Kita ambil actual_pickup_date. Jika kosong (order lama), fallback ke created_at
        $sqlCalc = "SELECT o.created_at, o.actual_pickup_date, o.duration_days, 
                    SUM(oi.price * oi.qty) as daily_total_price 
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.id = ?
                    GROUP BY o.id";

        $stmtCalc = $conn->prepare($sqlCalc);
        $stmtCalc->bind_param("i", $order_id);
        $stmtCalc->execute();
        $orderData = $stmtCalc->get_result()->fetch_assoc();

        if (!$orderData)
            throw new Exception('Data order tidak ditemukan.');

        // 3. Logika Perhitungan Waktu (1 Hari = 24 Jam)
        // Jika actual_pickup_date ada, gunakan itu. Jika tidak, pakai created_at.
        $start_time_str = !empty($orderData['actual_pickup_date']) ? $orderData['actual_pickup_date'] : $orderData['created_at'];

        $start_date = new DateTime($start_time_str);
        $duration = (int) $orderData['duration_days'];

        // Jatuh tempo = Waktu Ambil + (Durasi * 24 Jam)
        $due_date = clone $start_date;
        $due_date->modify("+{$duration} days");

        $now = new DateTime(); // Waktu sekarang (saat dikembalikan)

        $late_days = 0;
        $fine_amount = 0;

        // Cek keterlambatan (Hitungan detik)
        if ($now > $due_date) {
            // Hitung selisih detik
            $diff_seconds = $now->getTimestamp() - $due_date->getTimestamp();

            // Konversi ke hari (pembulatan ke atas). 
            // Telat 1 jam pun dihitung telat 1 hari (sesuai standar rental umumnya, atau bisa disesuaikan)
            // 86400 = detik dalam 24 jam
            $late_days = ceil($diff_seconds / 86400);
        }

        // Hitung Nominal Denda
        if ($late_days > 0) {
            $daily_total = (int) $orderData['daily_total_price'];
            // Rumus: Total Harga Harian * %Denda * Jumlah Hari Terlambat
            $fine_amount = $daily_total * ($fine_percent / 100) * $late_days;
        }

        // 4. Update Database
        $stmt = $conn->prepare("UPDATE orders SET rental_status = 'returned', actual_return_date = NOW(), fine_amount = ?, status = 'paid' WHERE id = ?");
        $stmt->bind_param("di", $fine_amount, $order_id);

        if ($stmt->execute()) {
            $msg = "Barang dikembalikan.";
            if ($late_days > 0) {
                $msg .= " Terlambat $late_days hari (Basis 24 Jam). Denda: Rp " . number_format($fine_amount, 0, ',', '.');
            }
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            throw new Exception("Gagal menyimpan data.");
        }

    } else {
        throw new Exception('Aksi tidak valid.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>