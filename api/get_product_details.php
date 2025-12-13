<?php
// api/get_product_details.php
header('Content-Type: application/json');

// Izinkan akses dari mana saja (opsional, untuk menghindari error CORS)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

// 1. Pastikan Metode adalah GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan. Gunakan GET.']);
    exit();
}

// 2. Ambil ID dari Parameter URL (?id=...)
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Produk tidak valid.']);
    exit();
}

// 3. Koneksi Database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $conn->connect_error]);
    exit();
}

// 4. Ambil Data Produk
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // Berhasil ditemukan
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        // ID tidak ditemukan
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Query Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>