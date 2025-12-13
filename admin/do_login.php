<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php'; // ini bisa akses $conn

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login-admin');
}

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    redirect('login-admin');
}

// --- LOGIKA LOGIN BARU (Menggunakan Database) ---
// 1. Ambil data admin dari tabel 'admins' berdasarkan username
$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// 2. Verifikasi password dengan hash yang ada di database
if ($admin && password_verify($password, $admin['password'])) {
    // --- Login Berhasil ---

    // PENTING: Set session 'admin_logged_in' agar lolos dari middleware/auth.php
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $admin['username']; // Opsional: Simpan username di session

    // Redirect ke halaman tujuan
    $target = $_SESSION['intended_url'] ?? 'dashboard-admin';
    unset($_SESSION['intended_url']);

    // Validasi keamanan target redirect
    if (strpos($target, '/admin/') === false && strpos($target, 'dashboard-admin') === false) {
        $target = 'dashboard-admin';
    }

    header('Location: ' . $target);
    exit();
}

// --- Login Gagal ---
$_SESSION['login_error'] = 'Username atau password salah.';
redirect('login-admin');
?>