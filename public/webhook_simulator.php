<?php
require 'config.php';

// Pastikan Method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  die("Method Not Allowed");
}

// 1. Ambil Data JSON dari Midtrans
$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if (!$result)
  die("Data tidak valid");

$order_id = $result['order_id'];
$transaction_status = $result['transaction_status'];
$gross_amount = $result['gross_amount'];
$payment_type = $result['payment_type'];
$status_code = $result['status_code'];

// 2. VALIDASI KEAMANAN (Signature Key)
$my_signature_key = hash('sha512', $order_id . $status_code . $gross_amount . \Midtrans\Config::$serverKey);

if ($my_signature_key !== $result['signature_key']) {
  http_response_code(403);
  die("Signature Key Tidak Valid!");
}

// 3. Cek Status Transaksi
$new_status = 'pending';
if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
  $new_status = 'paid'; // Sukses
} else if (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
  $new_status = 'failed'; // Gagal
}

// 4. Update Database & BUAT FAKTUR
if ($new_status == 'paid') {
  try {
    $pdo->beginTransaction();

    // Cek status sekarang (biar gak double)
    $stmtCek = $pdo->prepare("SELECT status FROM orders WHERE order_code = ?");
    $stmtCek->execute([$order_id]);
    $currStatus = $stmtCek->fetchColumn();

    if ($currStatus !== 'paid') {
      // A. UPDATE STATUS ORDER
      $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
      $stmt->execute([$order_id]);

      // B. AMBIL DATA ORDER (Termasuk durasi)
      $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
      $stmtOrder->execute([$order_id]);
      $order = $stmtOrder->fetch();

      // C. HITUNG TOTAL BARANG
      $stmtQty = $pdo->prepare("SELECT SUM(qty) FROM order_items WHERE order_id = ?");
      $stmtQty->execute([$order['id']]);
      $total_qty = $stmtQty->fetchColumn() ?: 0;

      // D. BUAT FAKTUR PENYEWAAN
      $inv_no = "INV-RENT/" . date('ymd') . "/" . rand(100, 999);

      $stmtInv = $pdo->prepare("INSERT INTO invoices 
                (invoice_no, order_code, customer_name, order_type, duration, total_qty, payment_method, signature_customer, signature_admin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $stmtInv->execute([
        $inv_no,
        $order_id,
        $order['customer_name'],
        'Peminjaman Barang Camping', // <--- Jenis Pesanan
        $order['duration_days'] . " Hari", // <--- Berapa Hari
        $total_qty,
        $payment_type,
        $order['customer_name'], // TTD Pemesan
        'Admin Rental'           // TTD User
      ]);

      // E. KIRIM WHATSAPP
      // Ganti URL ini dengan alamat NGROK kamu yang aktif
      $link_faktur = "https://3d37e93201bd.ngrok-free.app/invoice.php?order=" . $order_id;

      $pesan = "*PEMBAYARAN SUKSES!* ✅\n";
      $pesan .= "No Faktur: $inv_no\n";
      $pesan .= "Order ID: $order_id\n";
      $pesan .= "Total: " . formatRupiah($gross_amount) . "\n";
      $pesan .= "Lihat Faktur: $link_faktur\n";

      $nomor_toko = "082241559607"; // Nomor tujuan WA
      sendWhatsApp($nomor_toko, $pesan);
    }

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error Webhook: " . $e->getMessage());
  }
}

// Fungsi Kirim WA
function sendWhatsApp($target, $message)
{
  $token = "oD6Vm6LKGZBwgfYAqx5suT9mTLe3"; // <-- ISI TOKEN DISINI

  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.fonnte.com/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => array(
      'target' => $target,
      'message' => $message,
    ),
    CURLOPT_HTTPHEADER => array("Authorization: $token"),
  ));
  curl_exec($curl);
  curl_close($curl);
}

http_response_code(200);
?>