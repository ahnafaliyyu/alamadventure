<?php
require 'config/init.php';
// Masukkan Client Key Anda (Pastikan sama dengan di config/midtrans.php atau dashboard)
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

// --- CEK KADALUARSA ---
if ($order['status'] == 'pending' && !empty($order['expires_at'])) {
  $expireTime = strtotime($order['expires_at']);
  $now = time();

  if ($now > $expireTime) {
    // Update status ke database jadi cancelled
    $upd = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $upd->bind_param("i", $order['id']);
    $upd->execute();
    $order['status'] = 'cancelled';
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran - Alam Adventure</title>
  <link rel="icon" href="public/logo.png" type="image/png" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="<?= $clientKey ?>"></script>

  <style>
    :root {
      --brand: #2c4532;
      --brand-hover: #1f3225;
      --accent: #f9d84a;
      --bg-body: #f4f7f5;
      --text-dark: #1f2937;
      --text-muted: #6b7280;
      --white: #ffffff;
      --danger: #ef4444;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
      background-color: var(--brand);
      /* Background Brand Utama */
      background-image: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(249, 216, 74, 0.1) 0%, transparent 20%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-dark);
    }

    .payment-container {
      width: 100%;
      max-width: 420px;
      margin: 20px;
    }

    .card {
      background: var(--white);
      border-radius: 20px;
      padding: 35px 30px;
      text-align: center;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    /* Hiasan atas */
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--accent), var(--brand));
    }

    .logo-area {
      margin-bottom: 25px;
    }

    .logo-area img {
      width: 60px;
      height: 60px;
      object-fit: contain;
      border-radius: 50%;
      background: #f9f5f0;
      padding: 5px;
    }

    h2 {
      margin: 0 0 10px;
      font-size: 22px;
      font-weight: 800;
      color: var(--brand);
    }

    p.subtitle {
      margin: 0 0 30px;
      color: var(--text-muted);
      font-size: 14px;
      line-height: 1.5;
    }

    /* Detail Box */
    .order-details {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      text-align: left;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      font-size: 14px;
      color: var(--text-muted);
    }

    .detail-row.total {
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px dashed #cbd5e1;
      font-weight: 700;
      color: var(--brand);
      font-size: 18px;
      margin-bottom: 0;
    }

    .countdown-box {
      background: #fff1f2;
      color: var(--danger);
      padding: 10px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    /* Buttons */
    .btn-pay {
      background: var(--brand);
      color: white;
      width: 100%;
      padding: 16px;
      border: none;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(44, 69, 50, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-pay:hover {
      background: var(--brand-hover);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(44, 69, 50, 0.4);
    }

    .btn-cancel {
      display: block;
      margin-top: 20px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 13px;
      font-weight: 500;
      transition: color 0.2s;
    }

    .btn-cancel:hover {
      color: var(--brand);
    }

    /* Status States */
    .state-cancelled {
      padding: 40px 20px;
    }

    .icon-cancelled {
      font-size: 50px;
      color: var(--danger);
      margin-bottom: 20px;
    }
  </style>
</head>

<body>

  <div class="payment-container">

    <?php if ($order['status'] == 'cancelled'): ?>
      <div class="card state-cancelled">
        <i class="fas fa-times-circle icon-cancelled"></i>
        <h2>Pesanan Dibatalkan</h2>
        <p class="subtitle">
          Maaf, batas waktu pembayaran untuk pesanan <strong>#<?= $order['order_code'] ?></strong> telah habis.
        </p>
        <a href="index.php" class="btn-pay" style="background:#666;">Kembali ke Beranda</a>
      </div>

    <?php else: ?>
      <div class="card">
        <div class="logo-area">
          <img src="public/logo.png" alt="Alam Adventure">
        </div>

        <h2>Selesaikan Pembayaran</h2>
        <p class="subtitle">Lakukan pembayaran agar pesanan Anda segera diproses.</p>

        <?php if ($order['expires_at']): ?>
          <div class="countdown-box">
            <i class="far fa-clock"></i>
            Batas Waktu: <?= date('d M Y, H:i', strtotime($order['expires_at'])) ?>
          </div>
        <?php endif; ?>

        <div class="order-details">
          <div class="detail-row">
            <span>Kode Order</span>
            <span style="font-weight:600; color:#333;"><?= $order['order_code'] ?></span>
          </div>
          <div class="detail-row">
            <span>Nama Penyewa</span>
            <span><?= htmlspecialchars($order['customer_name']) ?></span>
          </div>
          <div class="detail-row">
            <span>Durasi</span>
            <span><?= $order['duration_days'] ?> Hari</span>
          </div>
          <div class="detail-row total">
            <span>Total Tagihan</span>
            <span><?= formatRupiah($order['total_amount']) ?></span>
          </div>
        </div>

        <button id="pay-button" class="btn-pay">
          <i class="fas fa-lock"></i> BAYAR SEKARANG
        </button>

        <a href="index.php" class="btn-cancel">Batalkan & Kembali</a>
      </div>
    <?php endif; ?>

  </div>

  <script type="text/javascript">
    var payButton = document.getElementById('pay-button');
    if (payButton) {
      payButton.addEventListener('click', function () {
        window.snap.pay('<?= $order['snap_token'] ?>', {
          onSuccess: function (result) {
            // Ubah tombol jadi loading
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi Pembayaran...';
            payButton.disabled = true;

            /* Kirim data ke Webhook secara manual via Fetch API 
               untuk mempercepat update status tanpa menunggu notifikasi server-to-server 
            */
            fetch('api/midtrans_webhook.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(result)
            }).then(() => {
              // Redirect setelah request selesai
              window.location.href = "invoice.php?order=<?= $order_code ?>";
            }).catch(() => {
              // Jika fetch gagal (misal masalah jaringan), tetap redirect setelah 3 detik
              setTimeout(function () {
                window.location.href = "invoice.php?order=<?= $order_code ?>";
              }, 3000);
            });
          },
          onPending: function (result) {
            alert("Menunggu pembayaran! Silakan selesaikan pembayaran Anda.");
            // Opsional: Reload halaman untuk melihat status terbaru
            location.reload();
          },
          onError: function (result) {
            alert("Pembayaran gagal! Silakan coba lagi.");
            location.reload();
          },
          onClose: function () {
            // Tidak melakukan apa-apa jika ditutup
          }
        });
      });
    }
  </script>

</body>

</html>