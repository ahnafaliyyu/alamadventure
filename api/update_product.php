<?php
require_once __DIR__ . '/../middleware/auth_api.php';

// Import koneksi database
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
$id = $input['id'] ?? null;
$name = $input['name'] ?? '';
$description = $input['description'] ?? '';
$price_per_day = $input['price_per_day'] ?? 0;
$stock = $input['stock'] ?? 0;
$image_url = $input['image_url'] ?? '';

if (!$id || empty($name) || !is_numeric($price_per_day) || !is_numeric($stock)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit();
}

$stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price_per_day = ?, stock = ?, image_url = ? WHERE id = ?");
$stmt->bind_param("ssdisi", $name, $description, $price_per_day, $stock, $image_url, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil diperbarui.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui produk: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>