<?php
// api/create_payment.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php'; // Menggunakan koneksi mysqli yang sudah ada
require_once __DIR__ . '/../config/midtrans.php';

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Data dari frontend
$customerName = $input['customer_name'] ?? 'Guest';
$customerPhone = $input['customer_phone'] ?? '08123456789'; // Default atau wajib diisi user
$items = $input['items'] ?? [];
$amount = 0;
$orderCode = 'INV-' . time() . rand(100, 999);

// Validasi Item & Hitung Total
$itemDetails = [];
foreach ($items as $item) {
    // Di sini sebaiknya Anda query ulang harga dari database berdasarkan ID untuk keamanan
    // Contoh sederhana menggunakan data kiriman:
    $price = (int)$item['price'];
    $qty = (int)$item['qty'];
    $durasi = (int)$item['durasi'];
    
    $subtotal = $price * $qty * $durasi;
    $amount += $subtotal;

    $itemDetails[] = [
        'id' => $item['id'],
        'price' => $price, // Midtrans mengharuskan harga satuan
        'quantity' => $qty * $durasi, // Total unit dikali hari
        'name' => substr($item['name'] . " ($durasi Hari)", 0, 50)
    ];
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total transaksi nol']);
    exit;
}

// Transaction Payload untuk Midtrans
$transactionDetails = [
    'order_id' => $orderCode,
    'gross_amount' => $amount,
];

$customerDetails = [
    'first_name' => $customerName,
    'phone' => $customerPhone,
];

$params = [
    'transaction_details' => $transactionDetails,
    'item_details' => $itemDetails,
    'customer_details' => $customerDetails,
];

try {
    // 1. Dapatkan Snap Token
    $snapToken = \Midtrans\Snap::getSnapToken($params);

    // 2. Simpan Order ke Database (Menggunakan MySQLi sesuai config/database.php)
    $stmt = $conn->prepare("INSERT INTO orders (order_code, customer_name, customer_phone, total_amount, snap_token) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $orderCode, $customerName, $customerPhone, $amount, $snapToken);
    
    if ($stmt->execute()) {
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Simpan Item Order
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            // Mapping ID produk dari string 'p1' ke integer jika perlu. Asumsi DB id integer.
            // Jika ID di DB anda INT, pastikan kirim ID yang benar. 
            // Untuk contoh ini kita set 0 atau ambil angka dari string jika formatnya 'p1'.
            $prodId = (int)filter_var($item['id'], FILTER_SANITIZE_NUMBER_INT); 
            if($prodId == 0) $prodId = 1; // Fallback jika id string

            $pPrice = $item['price'];
            $pQty = $item['qty'];
            $pDurasi = $item['durasi'];
            $pSubtotal = $pPrice * $pQty * $pDurasi;

            $stmtItem->bind_param("iiidd", $orderId, $prodId, $pQty, $pPrice, $pSubtotal);
            $stmtItem->execute();
        }
        $stmtItem->close();

        echo json_encode(['success' => true, 'token' => $snapToken, 'order_code' => $orderCode]);
    } else {
        throw new Exception("Gagal menyimpan ke database: " . $conn->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>