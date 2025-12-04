<?php
require './config/init.php'; // Sesuaikan path config Anda
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']))
    exit;

$user_id = $_SESSION['user_id'];
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$limit = 5;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// --- QUERY DATA ---
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($keyword)) {
    $sql .= " AND order_code LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $start;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- QUERY TOTAL (Untuk Pagination) ---
$sqlCount = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$paramsCount = [$user_id];
$typesCount = "i";

if (!empty($keyword)) {
    $sqlCount .= " AND order_code LIKE ?";
    $paramsCount[] = "%$keyword%";
    $typesCount .= "s";
}

$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param($typesCount, ...$paramsCount);
$stmtCount->execute();
$totalData = $stmtCount->get_result()->fetch_assoc()['total'];
$totalHalaman = ceil($totalData / $limit);

// =================================================================================
// OUTPUT HTML (Struktur HTML & Class DISAMAKAN dengan desain asli agar CSS jalan)
// =================================================================================

if ($result->num_rows == 0) {
    echo '<div style="text-align:center; padding:40px; color:#888;">
            <i class="fas fa-shopping-basket" style="font-size:48px; margin-bottom:15px; color:#ddd;"></i>
            <p>' . (!empty($keyword) ? 'Transaksi tidak ditemukan.' : 'Belum ada pesanan.') . '</p>
          </div>';
} else {
    while ($order = $result->fetch_assoc()) {
        // Logika Status (Sama seperti sebelumnya)
        $status = $order['status'];
        $statusClass = 'pending';
        $statusLabel = 'Menunggu Pembayaran';
        $badgeClass = 'bg-pending';

        if ($status == 'paid') {
            $statusClass = 'paid';
            $statusLabel = 'Lunas / Sedang Disewa';
            $badgeClass = 'bg-paid';
            if ($order['rental_status'] == 'returned')
                $statusLabel = 'Selesai (Dikembalikan)';
        } elseif ($status == 'cancelled' || $status == 'failed') {
            $statusClass = 'cancelled';
            $statusLabel = 'Dibatalkan';
            $badgeClass = 'bg-cancelled';
        }

        // Output Card
        ?>
        <div class="order-card <?= $statusClass ?>">
            <div class="order-header">
                <div>
                    <div class="order-code">#<?= $order['order_code'] ?></div>
                    <div class="order-date"><i class="far fa-clock"></i>
                        <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
                </div>
                <div><span class="status-badge <?= $badgeClass ?>"><?= $statusLabel ?></span></div>
            </div>

            <div class="order-details">
                <div>
                    <strong>Metode Bayar:</strong> <?= strtoupper($order['payment_method']) ?><br>
                    <strong>Metode Ambil:</strong>
                    <?= $order['delivery_method'] == 'delivery' ? 'Diantar Kurir' : 'Ambil Sendiri' ?><br>
                    <strong>Durasi:</strong> <?= $order['duration_days'] ?> Hari
                </div>
                <div class="order-total">
                    Total: Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
                </div>
            </div>

            <div style="margin-top:15px; text-align:right;">
                <?php if ($order['status'] == 'pending'): ?>
                    <?php if ($order['payment_method'] == 'online'): ?>
                        <a href="payment.php?order=<?= $order['order_code'] ?>" class="btn-action btn-pay">Bayar Sekarang</a>
                        <small style="color:#c62828; display:block; margin-top:5px; font-weight:600;">
                            <i class="fas fa-stopwatch"></i> Bayar sebelum:
                            <?= $order['expires_at'] ? date('d M, H:i', strtotime($order['expires_at'])) : '-' ?>
                        </small>
                    <?php else: ?>
                        <a href="invoice.php?order=<?= $order['order_code'] ?>" class="btn-action btn-pay"
                            style="background:#2980b9;">Lihat Invoice COD</a>
                    <?php endif; ?>
                <?php elseif ($order['status'] == 'cancelled'): ?>
                    <div style="margin-top:10px; color:#999; font-size:13px; font-style:italic;"><i class="fas fa-times-circle"></i>
                        Transaksi dibatalkan.</div>
                <?php elseif ($order['status'] == 'paid'): ?>
                    <a href="invoice.php?order=<?= $order['order_code'] ?>" target="_blank" class="btn-action btn-invoice"><i
                            class="fas fa-print"></i> Cetak Invoice</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// --- PAGINATION BUTTONS (AJAX STYLE) ---
if ($totalHalaman > 1) {
    echo '<div class="pagination">';

    // Tombol Previous
    if ($page > 1) {
        echo '<a href="javascript:void(0)" class="ajax-page" data-page="' . ($page - 1) . '">&laquo;</a>';
    }

    // Loop Angka
    for ($x = 1; $x <= $totalHalaman; $x++) {
        if ($x == $page) {
            echo '<span class="active">' . $x . '</span>';
        } else {
            echo '<a href="javascript:void(0)" class="ajax-page" data-page="' . $x . '">' . $x . '</a>';
        }
    }

    // Tombol Next
    if ($page < $totalHalaman) {
        echo '<a href="javascript:void(0)" class="ajax-page" data-page="' . ($page + 1) . '">&raquo;</a>';
    }

    echo '</div>';
    echo '<div style="text-align: center; margin-top: 10px; font-size: 12px; color: #888;">Halaman ' . $page . ' dari ' . $totalHalaman . '</div>';
}
?>