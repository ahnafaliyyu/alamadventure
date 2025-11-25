<?php
require_once __DIR__ . '/../middleware/auth_api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$price_per_day = $input['price_per_day'] ?? null;
$stock = $input['stock'] ?? null;
$image_url = trim($input['image_url'] ?? '');

if ($name === '' || !is_numeric($price_per_day) || !is_numeric($stock)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alamadventure";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO products (name, description, price_per_day, stock, image_url) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdis", $name, $description, $price_per_day, $stock, $image_url);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menambah produk: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
