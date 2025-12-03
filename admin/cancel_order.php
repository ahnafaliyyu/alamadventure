<?php
require '../config/init.php';
// Cek Login Admin (sesuaikan dengan logic auth admin Anda)
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int) $_POST['order_id'];

    // Ubah status jadi cancelled
    // Stok akan otomatis tersedia kembali karena katalog.php hanya menghitung stok dari order yang status != cancelled
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        header("Location: transaksi.php?msg=cancelled");
    } else {
        echo "Gagal membatalkan.";
    }
}
?>