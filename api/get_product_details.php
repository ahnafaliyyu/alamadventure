<?php
// api/add_product.php

// Matikan error display agar tidak merusak JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// 1. Cek Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit();
}

// 2. Ambil Data Form
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price_per_day = isset($_POST['price_per_day']) ? (float) $_POST['price_per_day'] : 0;
$stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;

// Validasi Data
if (empty($name) || $price_per_day <= 0 || $stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap (Nama/Harga/Stok).']);
    exit();
}

// Default Image
$image_url = '/public/logo.png';

// 3. Proses Upload Gambar
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Validasi Ekstensi
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['image']['name'];
    $fileTmp = $_FILES['image']['tmp_name'];
    $fileSize = $_FILES['image']['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format gambar harus JPG, PNG, atau WEBP.']);
        exit();
    }

    if ($fileSize > 5 * 1024 * 1024) { // 5MB
        echo json_encode(['success' => false, 'message' => 'Ukuran gambar terlalu besar (Max 5MB).']);
        exit();
    }

    // --- SETUP PATH FOLDER ---
    // Gunakan path absolute agar aman
    $baseDir = realpath(__DIR__ . '/../');
    $targetDir = $baseDir . '/public/image/';

    // --- GENERATE NAMA FILE SESUAI PRODUK ---
    // 1. Bersihkan nama produk: Huruf kecil, hanya huruf/angka, spasi jadi "-"
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    // 2. Tambahkan timestamp (time()) agar unik dan tidak menimpa file lain dengan nama sama
    // Contoh hasil: tenda-dome-pro-1709823412.jpg
    $newFilename = $slug . '-' . time() . '.' . $ext;

    $targetFile = $targetDir . $newFilename;

    // --- CEK FOLDER ---
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat folder public/image. Cek izin folder.']);
            exit();
        }
    }

    if (!is_writable($targetDir)) {
        echo json_encode(['success' => false, 'message' => 'Folder public/image tidak memiliki izin tulis (Not Writable).']);
        exit();
    }

    // Pindahkan File
    if (move_uploaded_file($fileTmp, $targetFile)) {
        // Simpan path web-nya ke variabel untuk database
        $image_url = "/public/image/" . $newFilename;
    } else {
        $error = error_get_last();
        $msg = isset($error['message']) ? $error['message'] : 'Unknown error';
        echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file. Server Error: ' . $msg]);
        exit();
    }

} elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'Error Upload Code: ' . $_FILES['image']['error']]);
    exit();
}

// 4. Simpan ke Database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $conn->connect_error]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO products (name, description, price_per_day, stock, image_url) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdis", $name, $description, $price_per_day, $stock, $image_url);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal simpan ke DB: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>