<?php
// api/get_dashboard_data.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$response = [];
$conn = new mysqli($servername, $username, $password, $dbname);

try {
    if ($conn->connect_error) throw new Exception("Koneksi database gagal");

    // --- 1. DATA KARTU RINGKASAN (SUMMARY) ---
    
    // Status 'paid' dianggap sebagai transaksi berhasil
    
    // A. Total Pendapatan
    $resRev = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'");
    $totalRevenue = $resRev->fetch_assoc()['total'] ?? 0;

    // B. Jumlah Produk
    $resProd = $conn->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $resProd->fetch_assoc()['total'] ?? 0;

    // C. Transaksi Bulan Ini
    $currentMonth = date('m');
    $currentYear = date('Y');
    $resTrxMonth = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'paid' AND MONTH(created_at) = '$currentMonth' AND YEAR(created_at) = '$currentYear'");
    $trxMonth = $resTrxMonth->fetch_assoc()['total'] ?? 0;

    // D. Transaksi Tahun Ini (BARU)
    $resTrxYear = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'paid' AND YEAR(created_at) = '$currentYear'");
    $trxYear = $resTrxYear->fetch_assoc()['total'] ?? 0;

    // E. Total Transaksi Seluruhnya (BARU)
    $resTrxTotal = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'paid'");
    $trxTotal = $resTrxTotal->fetch_assoc()['total'] ?? 0;

    // F. Total Pelanggan Unik
    $resCust = $conn->query("SELECT COUNT(DISTINCT customer_phone) as total FROM orders WHERE status = 'paid'");
    $totalCustomers = $resCust->fetch_assoc()['total'] ?? 0;


    // --- 2. DATA CHART: PENDAPATAN BULANAN (6 Bulan Terakhir) ---
    $revenueLabels = [];
    $revenueData = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthNum = date('m', strtotime("-$i months"));
        $yearNum = date('Y', strtotime("-$i months"));
        $label = date('M', strtotime("-$i months"));
        
        $sqlRev = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid' AND MONTH(created_at) = '$monthNum' AND YEAR(created_at) = '$yearNum'";
        $res = $conn->query($sqlRev);
        $val = $res->fetch_assoc()['total'] ?? 0;
        
        $revenueLabels[] = $label;
        $revenueData[] = (int)$val;
    }

    // --- 3. DATA CHART: TRANSAKSI HARIAN (7 Hari Terakhir) ---
    $dailyLabels = [];
    $dailyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D', strtotime("-$i days"));
        
        $sqlDaily = "SELECT COUNT(*) as total FROM orders WHERE status = 'paid' AND DATE(created_at) = '$date'";
        $res = $conn->query($sqlDaily);
        $val = $res->fetch_assoc()['total'] ?? 0;
        
        $dailyLabels[] = $label;
        $dailyData[] = (int)$val;
    }

    // --- 4. DATA CHART: KATEGORI ---
    $sqlCat = "SELECT p.name, SUM(oi.qty) as total_qty 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               JOIN orders o ON oi.order_id = o.id
               WHERE o.status = 'paid'
               GROUP BY p.id";
    $resCat = $conn->query($sqlCat);
    
    $categories = ['Tenda' => 0, 'Alat Masak' => 0, 'Penerangan' => 0, 'Lainnya' => 0];
    
    while($row = $resCat->fetch_assoc()) {
        $name = strtolower($row['name']);
        $qty = (int)$row['total_qty'];
        
        if (strpos($name, 'tenda') !== false || strpos($name, 'flysheet') !== false) {
            $categories['Tenda'] += $qty;
        } elseif (strpos($name, 'kompor') !== false || strpos($name, 'nesting') !== false || strpos($name, 'gas') !== false) {
            $categories['Alat Masak'] += $qty;
        } elseif (strpos($name, 'lampu') !== false || strpos($name, 'headlamp') !== false || strpos($name, 'lentera') !== false) {
            $categories['Penerangan'] += $qty;
        } else {
            $categories['Lainnya'] += $qty;
        }
    }

    // --- 5. TRANSAKSI TERBARU ---
    $sqlRecent = "SELECT order_code, customer_name, total_amount, created_at, status FROM orders ORDER BY created_at DESC LIMIT 5";
    $resRecent = $conn->query($sqlRecent);
    $recentTrx = [];
    while($row = $resRecent->fetch_assoc()) {
        $row['total_amount'] = (int)$row['total_amount'];
        $recentTrx[] = $row;
    }

    // KIRIM RESPONSE JSON
    echo json_encode([
        'success' => true,
        'summary' => [
            'revenue' => $totalRevenue,
            'products' => $totalProducts,
            'trx_month' => $trxMonth,
            'trx_year' => $trxYear,   // Data baru
            'trx_total' => $trxTotal, // Data baru
            'customers' => $totalCustomers
        ],
        'charts' => [
            'revenue' => ['labels' => $revenueLabels, 'data' => $revenueData],
            'daily' => ['labels' => $dailyLabels, 'data' => $dailyData],
            'category' => ['labels' => array_keys($categories), 'data' => array_values($categories)]
        ],
        'recent' => $recentTrx
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn) $conn->close();
}
?>