<?php
require_once __DIR__ . '/../middleware/auth_api.php';

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['id'] ?? null;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID produk tidak ditemukan.']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alamadventure";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>