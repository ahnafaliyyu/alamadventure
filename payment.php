<?php
require 'config/init.php';
// Masukkan Client Key Anda di sini
$clientKey = 'Mid-client-Z79It7Mc-vPxXiAz'; 

$order_code = $_GET['order'] ?? '';
if(!$order_code) die("Order tidak ditemukan");

// Ambil Token dari Database
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->bind_param("s", $order_code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Order tidak valid.");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Sewa</title>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?= $clientKey ?>"></script>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body style="text-align:center; padding-top: 50px;">

    <h2>Selesaikan Pembayaran</h2>
    <p>Kode Order: <b><?= $order['order_code'] ?></b></p>
    <p>Total: <b><?= formatRupiah($order['total_amount']) ?></b></p>

    <button id="pay-button" style="background:orange; padding:15px 30px; font-size:18px; border:none; cursor:pointer; color:white; font-weight:bold;">
        BAYAR SEKARANG
    </button>

    <script type="text/javascript">
      var payButton = document.getElementById('pay-button');
      payButton.addEventListener('click', function () {
        window.snap.pay('<?= $order['snap_token'] ?>', {
          onSuccess: function(result){
            window.location.href = "invoice.php?order=<?= $order_code ?>";
          },
          onPending: function(result){ alert("Menunggu pembayaran!"); },
          onError: function(result){ alert("Pembayaran gagal!"); },
          onClose: function(){ alert('Anda menutup popup sebelum membayar'); }
        });
      });
    </script>

</body>
</html>