<?php
require 'config.php';

$snapToken = $_GET['token'] ?? '';
$order_code = $_GET['order'] ?? '';

if (!$snapToken) {
    die("Token pembayaran tidak ditemukan.");
}

// Client Key dari Config (untuk JS)
// Kita ambil manual dari config karena property-nya static
// Pastikan kamu sudah isi Client Key di dashboard Midtrans
$clientKey = 'Mid-client-Z79It7Mc-vPxXiAz'; // <-- GANTI DENGAN CLIENT KEY KAMU
?>

<!DOCTYPE html>
<html>

<head>
    <title>Pembayaran</title>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="<?= $clientKey ?>"></script>
</head>

<body style="text-align:center; padding: 50px;">

    <h2>Selesaikan Pembayaran Anda</h2>
    <p>Order ID: <b><?= e($order_code) ?></b></p>

    <button id="pay-button"
        style="background-color: blue; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;">
        BAYAR SEKARANG
    </button>

    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        payButton.addEventListener('click', function () {

            // Trigger Snap Popup
            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function (result) {
                    alert("Pembayaran Berhasil! Mengalihkan ke Faktur...");
                    // UBAH KE SINI: Redirect ke invoice.php membawa order_code
                    window.location.href = "invoice.php?order=<?= $order_code ?>";
                },
                onPending: function (result) {
                    alert("Menunggu pembayaran!");
                },
                onError: function (result) {
                    alert("Pembayaran gagal!");
                },
                onClose: function () {
                    alert('Anda menutup popup sebelum menyelesaikan pembayaran');
                }
            });
        });
    </script>

</body>

</html>