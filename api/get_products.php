<?php
// 1. Matikan tampilan error PHP agar tidak merusak format JSON
// Error tetap bisa dilihat di log server (error_log), tapi tidak muncul di browser
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Set Header agar browser tahu ini PASTI JSON
header('Content-Type: application/json; charset=utf-8');

// 3. Mulai Output Buffering
// Ini akan menahan semua output (termasuk spasi dari file include) agar tidak langsung dicetak
ob_start();

$response = [];
$conn = null;

try {
    // Import file dependensi
    require_once __DIR__ . '/../middleware/auth_api.php';
    require_once __DIR__ . '/../config/database.php';

    // Buat koneksi (Pastikan variabel $servername dll ada di database.php)
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Periksa koneksi
    if ($conn->connect_error) {
        throw new Exception('Koneksi database gagal: ' . $conn->connect_error);
    }

    $sql = "SELECT id, name, price_per_day, stock FROM products ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Query gagal: ' . $conn->error);
    }

    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Pastikan tipe data benar (misal harga jadi integer/number, bukan string)
            $row['price_per_day'] = (int)$row['price_per_day'];
            $row['stock'] = (int)$row['stock'];
            $products[] = $row;
        }
    }

    $response = ['success' => true, 'data' => $products];

} catch (Exception $e) {
    // Set HTTP response code ke 500 jika error server, atau 200 dengan status false
    // http_response_code(500); 
    $response = ['success' => false, 'message' => $e->getMessage()];
} finally {
    if ($conn) {
        $conn->close();
    }
}

// 4. BERSIHKAN BUFFER!
// Ini langkah krusial: hapus semua teks/spasi/warning yang mungkin muncul dari file include di atas
ob_clean();

// 5. Cetak JSON bersih
echo json_encode($response);
?>