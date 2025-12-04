<?php
// api/get_transactions.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    if ($conn->connect_error)
        throw new Exception("Connection failed");

    // --- [LOGIKA BARU] GLOBAL AUTO CANCEL ---
    date_default_timezone_set('Asia/Makassar'); // Set Timezone WITA untuk operasi PHP
    $current_time = date('Y-m-d H:i:s');

    // Update SEMUA pesanan kadaluarsa (tanpa filter user_id)
    $stmtAuto = $conn->prepare("UPDATE orders 
                                SET status = 'cancelled' 
                                WHERE status = 'pending' 
                                AND expires_at IS NOT NULL 
                                AND expires_at < ?");
    $stmtAuto->bind_param("s", $current_time);
    $stmtAuto->execute();
    $stmtAuto->close();
    // ----------------------------------------

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Query Pencarian & Pagination
    $whereSQL = "WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSQL .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR i.invoice_no LIKE ?)";
        $s = "%$search%";
        $params = [$s, $s, $s];
        $types = "sss";
    }

    $sqlCount = "SELECT COUNT(*) as total FROM orders o LEFT JOIN invoices i ON o.order_code = i.order_code $whereSQL";
    $stmtCount = $conn->prepare($sqlCount);
    if (!empty($params))
        $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalData = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalData / $limit);

    // Ambil Data Transaksi
    $sql = "SELECT o.*, i.invoice_no, o.payment_method, o.rental_status, o.created_at
            FROM orders o 
            LEFT JOIN invoices i ON o.order_code = i.order_code 
            $whereSQL 
            ORDER BY o.created_at DESC 
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_amount'] = (int) $row['total_amount'];

        // --- [PERBAIKAN WAKTU] KONVERSI UTC KE WITA ---
        // Server database biasanya menyimpan waktu dalam UTC.
        // Kode ini mengonversi waktu UTC dari database ke Asia/Makassar (WITA).
        try {
            $dt = new DateTime($row['created_at'], new DateTimeZone('UTC')); 
            $dt->setTimezone(new DateTimeZone('Asia/Makassar')); 
            $row['created_at'] = $dt->format('d M Y, H:i'); 
        } catch (Exception $e) {
            // Fallback manual tambah 8 jam jika gagal parsing
            $row['created_at'] = date('d M Y, H:i', strtotime($row['created_at']) + (8 * 3600));
        }
        // ----------------------------------------------

        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'total_pages' => $totalPages,
            'current_page' => $page
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>