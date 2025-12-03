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
    // Finish Rent (Barang Kembali)
    $fine = $input['fine'] ?? 0;

    // Update status kembali, catat tanggal, dan masukkan denda
    $stmt = $conn->prepare("UPDATE orders SET rental_status = 'returned', actual_return_date = NOW(), fine_amount = ?, status = 'paid' WHERE id = ?");
    $stmt->bind_param("di", $fine, $order_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Barang dikembalikan. Stok pulih.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
}
?>