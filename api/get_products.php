<?php
// api/get_products.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli($servername, $username, $password, $dbname);

try {
    if ($conn->connect_error)
        throw new Exception("Connection failed");

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    $whereSQL = "WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSQL .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // --- QUERY UPDATE: Hitung 'rented' (Sedang Dipinjam) ---
    // Total Stock = Kolom p.stock
    // Rented = Jumlah qty di order_items yang status sewanya 'pending_pickup' atau 'ongoing'

    $sql = "SELECT p.id, p.name, p.price_per_day, p.stock,
            COALESCE((
                SELECT SUM(oi.qty) 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.product_id = p.id 
                AND o.rental_status IN ('pending_pickup', 'ongoing')
                AND o.status != 'cancelled' 
                AND o.status != 'failed'
            ), 0) as rented
            FROM products p 
            $whereSQL 
            ORDER BY p.id DESC 
            LIMIT ? OFFSET ?";

    // Hitung Total Data (Pagination)
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM products $whereSQL");
    if (!empty($params))
        $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $totalData = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalData / $limit);

    // Ambil Data Produk
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['price_per_day'] = (int) $row['price_per_day'];
        $row['stock'] = (int) $row['stock'];
        $row['rented'] = (int) $row['rented']; // Data jumlah yang sedang dipinjam
        $products[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'total_data' => $totalData,
            'total_pages' => $totalPages,
            'current_page' => $page
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn)
        $conn->close();
}
?>