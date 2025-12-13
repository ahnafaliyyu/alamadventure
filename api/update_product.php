<?php
// api/update_product.php

// 1. Atur Header agar browser tidak bingung (CORS & JSON)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 2. Tangani Preflight Request (OPTIONS)
// Jika browser mengecek koneksi dulu, kita jawab "OK" agar lanjut ke POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Masukkan dependensi
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan koneksi database tersedia
if (!isset($conn)) {
    $conn = new mysqli($servername, $username, $password, $dbname);
}

// 4. Cek Metode Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Tampilkan metode apa yang sebenarnya diterima server untuk debugging
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Server received: ' . $_SERVER['REQUEST_METHOD'] . '. Pastikan tidak ada redirect (misal HTTP ke HTTPS).'
    ]);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = isset($_POST['price']) ? (int) $_POST['price'] : 0;
$stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;

// Validasi sederhana
if (empty($name) || empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Nama produk dan ID wajib diisi.']);
    exit;
}

try {
    // 5. Cek Upload Gambar
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            throw new Exception("Format file tidak valid. Gunakan JPG, PNG, atau WEBP.");
        }

        $newFilename = uniqid() . '.' . $ext;
        $targetDir = __DIR__ . "/../public/uploads/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFile = $targetDir . $newFilename;
        $dbPath = "public/uploads/" . $newFilename; // Simpan path relative untuk frontend

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Update dengan gambar
            $sql = "UPDATE products SET name=?, description=?, price_per_day=?, stock=?, image_url=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiisi", $name, $description, $price, $stock, $dbPath, $id);
        } else {
            throw new Exception("Gagal mengupload gambar ke server.");
        }
    } else {
        // Update TANPA gambar
        $sql = "UPDATE products SET name=?, description=?, price_per_day=?, stock=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $name, $description, $price, $stock, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil diperbarui']);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt))
    $stmt->close();
// Jangan close $conn jika digunakan script lain, tapi di API biasanya aman.
$conn->close();
?>