<?php
// api/process_rental.php
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$order_id = $input['order_id'] ?? '';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($action === 'start_rent') {
    // 1. Cek dulu jenis pembayarannya
    $cek = $conn->prepare("SELECT payment_method FROM orders WHERE id = ?");
    $cek->bind_param("i", $order_id);
    $cek->execute();
    $res = $cek->get_result()->fetch_assoc();

    if ($res && $res['payment_method'] === 'cod') {
        // KHUSUS COD:
        // Saat barang diambil, kita anggap uang sudah diterima (Lunas)
        // Sehingga pendapatan masuk ke Dashboard
        $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing', status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Pembayaran COD diterima & Barang telah diambil.']);
    } else {
        // ORDER ONLINE:
        // Status bayar sudah paid dari awal, cuma ubah status sewa
        $stmt = $conn->prepare("UPDATE orders SET rental_status = 'ongoing' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Barang telah diambil penyewa.']);
    }

} elseif ($action === 'finish_rent') {
    // --- LOGIKA HITUNG DENDA OTOMATIS (50%) ---

    // 1. Ambil Setting Persentase Denda
    $resSet = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'rental_fine_percent'");
    $rowSet = $resSet->fetch_assoc();
    $fine_percent = $rowSet ? (int) $rowSet['setting_value'] : 50; // Default 50%

    // 2. Ambil Data Order & Total Harga Sewa Harian Barang
    // Kita perlu tahu berapa total harga sewa per hari dari barang-barang di order ini
    $sqlCalc = "SELECT o.created_at, o.duration_days, 
                SUM(oi.price * oi.qty) as daily_total_price 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.id = ?";

    $stmtCalc = $conn->prepare($sqlCalc);
    $stmtCalc->bind_param("i", $order_id);
    $stmtCalc->execute();
    $orderData = $stmtCalc->get_result()->fetch_assoc();

    if (!$orderData) {
        echo json_encode(['success' => false, 'message' => 'Data order tidak ditemukan']);
        exit;
    }

    // 3. Hitung Tanggal Wajib Kembali & Keterlambatan
    // Set timezone agar akurat
    date_default_timezone_set('Asia/Makassar');

    $start_date = new DateTime($orderData['created_at']);
    $duration = (int) $orderData['duration_days'];

    // Tanggal jatuh tempo (created_at + durasi)
    $due_date = clone $start_date;
    $due_date->modify("+$duration days");

    $now = new DateTime(); // Waktu dikembalikan sekarang

    $late_days = 0;
    $fine_amount = 0;

    // Jika waktu sekarang > jatuh tempo, hitung selisih hari
    if ($now > $due_date) {
        // Hitung selisih hari (dibulatkan ke atas, misal telat 2 jam dianggap 1 hari atau gunakan logic diff->days)
        // Disini kita pakai diff->days yang menghitung selisih penuh
        $interval = $now->diff($due_date);
        $late_days = $interval->days;

        // Fix: jika diff days 0 tapi jamnya lewat, hitung 1 hari (opsional, tergantung kebijakan)
        if ($late_days == 0 && $now > $due_date) {
            $late_days = 1;
        }
    }

    // 4. Hitung Nominal Denda Akhir
    // Rumus: (Total Harga Harian) * (Persen / 100) * (Hari Telat)
    if ($late_days > 0) {
        $daily_total = (int) $orderData['daily_total_price'];
        $fine_amount = $daily_total * ($fine_percent / 100) * $late_days;
    }

    // 5. Update Database
    $stmt = $conn->prepare("UPDATE orders SET rental_status = 'returned', actual_return_date = NOW(), fine_amount = ?, status = 'paid' WHERE id = ?");
    $stmt->bind_param("di", $fine_amount, $order_id);
    $stmt->execute();

    // Format pesan sukses
    $msg = "Barang dikembalikan.";
    if ($late_days > 0) {
        $msg .= " Terlambat $late_days hari. Denda sistem: Rp " . number_format($fine_amount, 0, ',', '.');
    }

    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
}
?>