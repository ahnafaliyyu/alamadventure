<?php
// api/delete_product.php

// 1. Matikan error display agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

// 2. Validasi Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

// 3. Ambil Input JSON
$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['id'] ?? null;

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID produk tidak ditemukan.']);
    exit();
}

// 4. Koneksi Database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit();
}

// 5. Ambil Info Gambar (Untuk dihapus fisiknya nanti)
$imgQuery = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
$imgQuery->bind_param("i", $productId);
$imgQuery->execute();
$result = $imgQuery->get_result();
$product = $result->fetch_assoc();
$imageUrl = $product['image_url'] ?? '';

// 6. Hapus Data dari Database
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);

if ($stmt->execute()) {
    // === FITUR BARU: Hapus File Gambar Fisik ===
    // Cek apakah ada gambar dan bukan gambar default
    if (!empty($imageUrl) && $imageUrl !== '/public/logo.png') {
        // Konversi URL path (/public/image/..) ke System Path (../public/image/..)
        $filePath = __DIR__ . '/..' . $imageUrl;

        // Hapus file jika ada
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Produk dan gambarnya berhasil dihapus.']);
} else {
    // Cek jika gagal karena Foreign Key (sedang dipinjam/ada di riwayat order)
    if ($conn->errno == 1451) {
        echo json_encode(['success' => false, 'message' => 'Gagal: Produk ini ada dalam riwayat transaksi dan tidak bisa dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>