<?php
// api/update_product.php
header('Content-Type: application/json');
require_once __DIR__ . '/../middleware/auth_api.php';
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = (int) $_POST['id'];
$name = $_POST['name'];
$description = $_POST['description'];
$price = (int) $_POST['price'];
$stock = (int) $_POST['stock'];

// Validasi sederhana
if (empty($name) || empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Nama produk wajib diisi']);
    exit;
}

try {
    // 1. Cek apakah ada upload gambar baru
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            throw new Exception("Format file tidak valid. Gunakan JPG, PNG, atau WEBP.");
        }

        // Generate nama file unik agar tidak cache
        $newFilename = uniqid() . '.' . $ext;
        $targetDir = "../public/uploads/";

        // Buat folder jika belum ada
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);

        $targetFile = $targetDir . $newFilename;

        // Path untuk disimpan di DB (relatif terhadap root project jika diakses dari browser)
        // Sesuaikan dengan struktur folder Anda. Biasanya ../public/uploads/file.jpg
        // Tapi di database kita simpan path absolut web-nya, misal: /public/uploads/file.jpg
        $dbPath = "/public/uploads/" . $newFilename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Update dengan gambar baru
            $sql = "UPDATE products SET name=?, description=?, price_per_day=?, stock=?, image_url=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiisi", $name, $description, $price, $stock, $dbPath, $id);
        } else {
            throw new Exception("Gagal mengupload gambar.");
        }
    } else {
        // Update TANPA mengubah gambar
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
$conn->close();
?>