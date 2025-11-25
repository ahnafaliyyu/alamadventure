<?php
header('Content-Type: application/json');

$productId = $_GET['id'] ?? null;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID produk tidak valid.']);
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

$stmt = $conn->prepare("SELECT id, name, description, price_per_day, stock, image_url FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($product = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan.']);
}

$stmt->close();
$conn->close();
?>