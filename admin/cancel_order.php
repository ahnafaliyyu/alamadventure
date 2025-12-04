<?php
// admin/cancel_order.php
require_once __DIR__ . '/../config/init.php';

// Set header JSON
header('Content-Type: application/json');

// Cek Login Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak (Unauthorized)']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int) $_POST['order_id'];

    // Lakukan Update
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan secara manual.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pesanan: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
}
?>