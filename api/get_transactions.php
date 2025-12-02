<?php
// api/get_transactions.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    if ($conn->connect_error) throw new Exception("Connection failed");

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Logic Pencarian
    $whereSQL = "WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSQL .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR i.invoice_no LIKE ?)";
        $s = "%$search%";
        $params = [$s, $s, $s];
        $types = "sss";
    }

    // Hitung Total
    $sqlCount = "SELECT COUNT(*) as total FROM orders o LEFT JOIN invoices i ON o.order_code = i.order_code $whereSQL";
    $stmtCount = $conn->prepare($sqlCount);
    if (!empty($params)) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalData = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalData / $limit);

    // Ambil Data
    $sql = "SELECT o.*, i.invoice_no 
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
        $row['total_amount'] = (int)$row['total_amount'];
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
}
?>