<?php
// api/process_rental.php
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan header JSON selalu dikirim agar frontend bisa membaca response
header('Content-Type: application/json');

// Tangkap input JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$order_id = $input['order_id'] ?? '';

// Buat koneksi baru
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi database
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

try {
    if (empty($action) || empty($order_id)) {
        throw new Exception("Data tidak lengkap.");
    }

    if ($action === 'start_rent') {
        // --- LOGIKA PENGAMBILAN BARANG ---

        // 1. Cek jenis pembayaran
        $cek = $conn->prepare("SELECT payment_method FROM orders WHERE id = ?");
        $cek->bind_param("i", $order_id);
        $cek->execute();
        $res = $cek->get_result()->fetch_assoc();

        if ($res && $res['payment_method'] === 'cod') {
            // Jika COD, saat diambil status bayar jadi 'paid' dan sewa 'ongoing'
            $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing', status = 'paid' WHERE id = ?");
        } else {
            // Jika Online, hanya update status sewa
            $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing' WHERE id = ?");
        }

        $stmt->bind_param("i", $order_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Barang berhasil diserahkan (Status: Ongoing).']);
        } else {
            throw new Exception("Gagal update database: " . $stmt->error);
        }

    } elseif ($action === 'finish_rent') {
        // --- LOGIKA PENGEMBALIAN BARANG ---

        // 1. Ambil Setting Persentase Denda
        $resSet = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'rental_fine_percent'");
        $rowSet = $resSet->fetch_assoc();
        $fine_percent = $rowSet ? (int) $rowSet['setting_value'] : 50; // Default 50%

        // 2. Ambil Data Order (PERBAIKAN: Ditambahkan GROUP BY)
        // Tanpa GROUP BY, query ini akan gagal di MySQL mode strict
        $sqlCalc = "SELECT o.created_at, o.duration_days, 
                    SUM(oi.price * oi.qty) as daily_total_price 
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.id = ?
                    GROUP BY o.id"; // <--- INI PERBAIKAN PENTINGNYA

        $stmtCalc = $conn->prepare($sqlCalc);
        if (!$stmtCalc) {
            throw new Exception("Query Error: " . $conn->error);
        }

        $stmtCalc->bind_param("i", $order_id);
        $stmtCalc->execute();
        $orderData = $stmtCalc->get_result()->fetch_assoc();

        if (!$orderData) {
            throw new Exception('Data order tidak ditemukan atau item kosong.');
        }

        // 3. Hitung Tanggal & Denda
        date_default_timezone_set('Asia/Makassar');

        $start_date = new DateTime($orderData['created_at']);
        $duration = (int) $orderData['duration_days'];

        // Jatuh tempo = tgl sewa + durasi
        $due_date = clone $start_date;
        $due_date->modify("+$duration days");

        $now = new DateTime(); // Waktu saat ini (pengembalian)

        $late_days = 0;
        $fine_amount = 0;

        // Cek apakah terlambat
        if ($now > $due_date) {
            $interval = $now->diff($due_date);
            $late_days = $interval->days;

            // Jika telat beberapa jam tapi di hari yang sama dengan jatuh tempo (jarang terjadi dgn diff),
            // Atau jika diff days 0 tapi jamnya lewat, hitung 1 hari.
            if ($late_days == 0 && $now > $due_date) {
                $late_days = 1;
            }
        }

        // Hitung nominal denda
        if ($late_days > 0) {
            $daily_total = (int) $orderData['daily_total_price'];
            $fine_amount = $daily_total * ($fine_percent / 100) * $late_days;
        }

        // 4. Update Database (Selesaikan Sewa)
        $stmt = $conn->prepare("UPDATE orders SET rental_status = 'returned', actual_return_date = NOW(), fine_amount = ?, status = 'paid' WHERE id = ?");
        $stmt->bind_param("di", $fine_amount, $order_id);

        if ($stmt->execute()) {
            // Format Pesan Sukses
            $msg = "Barang berhasil dikembalikan.";
            if ($late_days > 0) {
                $msg .= " Terlambat $late_days hari. Denda: Rp " . number_format($fine_amount, 0, ',', '.');
            }

            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            throw new Exception("Gagal menyimpan perubahan: " . $stmt->error);
        }

    } else {
        throw new Exception('Aksi tidak dikenali.');
    }

} catch (Exception $e) {
    // Tangkap error dan kirim sebagai JSON agar bisa dibaca alert javascript
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>