<?php
session_start();

// Load Library Midtrans (Hasil install composer tadi)
require_once __DIR__ . '/vendor/autoload.php';

// --- KONFIGURASI MIDTRANS ---
// Ganti dengan Key dari Dashboard Midtrans (Mode Sandbox)
\Midtrans\Config::$serverKey = ''; // <-- GANTI INI
\Midtrans\Config::$isProduction = false; // Set true jika nanti sudah live
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// --- PENYESUAIAN YML BARU ---
$DB_HOST = 'mysql'; 
$DB_NAME = 'db_marketplace';
$DB_USER = 'toko'; 
$DB_PASSWORD = '250507';
// -----------------------------

$charset = 'utf8mb4';
$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASSWORD, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ===============================================
// === FUNGSI HELPER YANG HILANG (SEKARANG ADA) ===
// ===============================================

// Generate CSRF Token untuk setiap sesi form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="'.$_SESSION['csrf_token'].'">';
}

function validate_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Validation Failed! Aksi ilegal terdeteksi.");
    }
}

// Sanitasi Output (Mencegah XSS)
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Format Rupiah
function formatRupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}
?>