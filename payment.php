<?php
require 'config/init.php';
// Masukkan Client Key Anda
$clientKey = 'Mid-client-Z79It7Mc-vPxXiAz';

$order_code = $_GET['order'] ?? '';
if (!$order_code)
  die("Order tidak ditemukan");

// Ambil Data Order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->bind_param("s", $order_code);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order)
  die("Order tidak valid.");

// --- [LOGIKA BARU] CEK KADALUARSA SAAT MEMBUKA HALAMAN BAYAR ---
if ($order['status'] == 'pending' && !empty($order['expires_at'])) {
  $expireTime = strtotime($order['expires_at']);
  $now = time();

  if ($now > $expireTime) {
    // Update status ke database jadi cancelled
    $upd = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $upd->bind_param("i", $order['id']);
    $upd->execute();

    // Update variabel lokal agar UI berubah
    $order['status'] = 'cancelled';
  }
}

// Jika status sudah cancelled (baik baru saja atau sebelumnya), tolak akses
if ($order['status'] == 'cancelled') {
  echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>";
  echo "<h2 style='color:red;'>Pesanan Dibatalkan</h2>";
  echo "<p>Maaf, batas waktu pembayaran untuk pesanan ini telah habis.</p>";
  echo "<a href='index.php'>Kembali ke Beranda</a>";
  echo "</div>";
  exit; // Stop script
}
// ----------------------------------------------------------------

?>

<!DOCTYPE html>
<html>

<head>
  <title>Pembayaran Sewa</title>
  <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="<?= $clientKey ?>"></script>
  <link rel="stylesheet" href="public/css/style.css">
</head>

<body style="text-align:center; padding-top: 50px;">

  <h2>Selesaikan Pembayaran</h2>
  <?php if ($order['expires_at']): ?>
    <p style="color:red; font-size:14px;">Batas Waktu: <?= $order['expires_at'] ?></p>
  <?php endif; ?>

  <p>Kode Order: <b><?= $order['order_code'] ?></b></p>
  <p>Total: <b><?= formatRupiah($order['total_amount']) ?></b></p>

  <button id="pay-button"
    style="background:orange; padding:15px 30px; font-size:18px; border:none; cursor:pointer; color:white; font-weight:bold;">
    BAYAR SEKARANG
  </button>

  <script type="text/javascript">
    var payButton = document.getElementById('pay-button');
    payButton.addEventListener('click', function () {
      window.snap.pay('<?= $order['snap_token'] ?>', {
        onSuccess: function (result) {
          window.location.href = "invoice.php?order=<?= $order_code ?>";
        },
        onPending: function (result) { alert("Menunggu pembayaran!"); },
        onError: function (result) { alert("Pembayaran gagal!"); },
        onClose: function () { alert('Anda menutup popup sebelum membayar'); }
      });
    });
  </script>

</body>

</html>