<?php
require_once __DIR__ . '/../middleware/auth_api.php';

// Database connection details (replace with your actual credentials)
$servername = "localhost";
$username = "root";
$password = ""; // Pastikan ini sesuai dengan password database Anda
$dbname = "alamadventure";

$conn = null; // Inisialisasi koneksi ke null

try {
    // Buat koneksi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Periksa koneksi
    if ($conn->connect_error) {
        throw new Exception('Koneksi database gagal: ' . $conn->connect_error);
    }

    $sql = "SELECT id, name, price_per_day, stock FROM products";
    $result = $conn->query($sql);

    $products = [];
    if ($result === false) { // Periksa jika query gagal
        throw new Exception('Query gagal: ' . $conn->error);
    }

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $products]);

} catch (Exception $e) {
    // Tangkap setiap exception dan kembalikan error dalam format JSON
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
} finally {
    // Tutup koneksi jika sudah dibuka
    if ($conn) {
        $conn->close();
    }
}
?>
